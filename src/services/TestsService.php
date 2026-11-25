<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\services;

use Craft;
use craft\base\Component;
use craft\base\Plugin;
use craft\helpers\ConfigHelper;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\models\Updates;
use craft\web\Request;
use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TooManyRedirectsException;
use GuzzleHttp\TransferStats;
use putyourlightson\sherlock\events\RunTestsEvent;
use putyourlightson\sherlock\models\TestModel;
use putyourlightson\sherlock\Sherlock;
use yii\base\Event;
use yii\web\HttpException;

/**
 * Tests Service
 *
 * @property array $testNames
 */
class TestsService extends Component
{
    /**
     * @event RunTestsEvent
     */
    public const EVENT_BEFORE_RUN_TESTS = 'beforeRunTests';

    /**
     * First day after each branch’s upstream security support ends.
     * @link https://www.php.net/supported-versions.php
     */
    private const PHP_EOL_DATES = [
        '8.0' => '2023-11-26',
        '8.1' => '2026-01-01',
        '8.2' => '2027-01-01',
        '8.3' => '2028-01-01',
        '8.4' => '2029-01-01',
        '8.5' => '2030-01-01',
        '8.6' => '2031-01-01',
    ];

    /**
     * @link https://www.php.net/archive/2026.php
     */
    private const PHP_SECURITY_UPDATE_THRESHOLDS = [
        '8.2' => '8.2.36',
        '8.3' => '8.3.37',
        '8.4' => '8.4.28',
        '8.5' => '8.5.13',
        '8.6' => '8.6.0',
    ];

    /**
     * @var Client|null
     */
    public ?Client $client = null;

    /**
     * @var Updates|null
     */
    public ?Updates $updates = null;

    /**
     * @var string|null
     */
    public ?string $siteUrl = null;

    /**
     * @var array
     */
    public array $siteUrlResponse = [];

    /**
     * Get test names
     *
     * @return string[]
     */
    public function getTestNames(): array
    {
        $tests = [
            // Updates
            'criticalCraftUpdates',
            'criticalPluginUpdates',
            'craftUpdates',
            'pluginUpdates',

            // HTTPS
            'httpsControlPanel',
            'httpsFrontEnd',

            // System
            'craftFilePermissions',
            'craftFolderPermissions',
            'craftFoldersAboveWebRoot',
            'phpVersion',
            'phpSecurityUpdates',
            'phpComposerVersion',

            // Setup
            'adminUsername',
            'requireEmailVerification',
            'webAliasInSiteBaseUrl',
            'webAliasInVolumeBaseUrl',

            // Headers
            'contentSecurityPolicy',
            'cors',
            'referrerPolicy',
            'strictTransportSecurity',
            'xContentTypeOptions',
            'xFrameOptions',

            // General config settings
            'blowfishHashCost',
            'cooldownDuration',
            'cpTrigger',
            'defaultDirMode',
            'defaultFileMode',
            'defaultTokenDuration',
            'deferPublicRegistrationPassword',
            'devMode',
            'elevatedSessionDuration',
            'enableCsrfProtection',
            'invalidLoginWindowDuration',
            'maxInvalidLogins',
            'preventUserEnumeration',
            'rememberedUserSessionDuration',
            'requireMatchingUserAgentForSession',
            'requireUserAgentAndIpForSession',
            'sanitizeSvgUploads',
            'sendPoweredByHeader',
            'testToEmailAddress',
            'translationDebugOutput',
            'userSessionDuration',
            'useSecureCookies',
            'verificationCodeDuration',
        ];

        // Remove disabled tests
        $disabledTests = Sherlock::$plugin->settings->disabledTests;

        if (is_array($disabledTests)) {
            $tests = array_values(array_diff($tests, $disabledTests));
        }

        return $tests;
    }

    /**
     * Performs preps before running tests.
     */
    public function beforeRunTests(): void
    {
        // Ensure we only run this method once
        if ($this->client !== null) {
            return;
        }

        $this->client = Craft::createGuzzleClient([
            'timeout' => 10,
        ]);

        // Get updates, forcing a refresh
        $this->updates = Craft::$app->getUpdates()->getUpdates(true);

        // Get the current site's base URL if not already set (by unit tests)
        $this->siteUrl = $this->siteUrl ?? Craft::$app->getSites()->getCurrentSite()->getBaseUrl();

        $event = new RunTestsEvent([
            'client' => $this->client,
        ]);
        Event::trigger(static::class, self::EVENT_BEFORE_RUN_TESTS, $event);

        try {
            $response = $this->client->get($this->siteUrl);
            $this->siteUrlResponse['headers'] = $response->getHeaders();
            $this->siteUrlResponse['body'] = $response->getBody()->getContents();
        } catch (GuzzleException $exception) {
            $status = $exception instanceof RequestException ? $exception->getResponse()?->getStatusCode() : null;
            $context = $exception instanceof RequestException || $exception instanceof ConnectException
                ? $exception->getHandlerContext()
                : [];
            $reason = match (true) {
                $exception instanceof TooManyRedirectsException => Craft::t('sherlock', 'The request followed too many redirects.'),
                $status !== null && $status >= 400 => Craft::t('sherlock', 'The request returned HTTP {status}.', ['status' => $status]),
                default => match ($context['errno'] ?? null) {
                    5, 6 => Craft::t('sherlock', 'A DNS lookup failed.'),
                    7 => Craft::t('sherlock', 'The connection could not be established.'),
                    28 => Craft::t('sherlock', 'The request timed out.'),
                    35, 60 => Craft::t('sherlock', 'The TLS connection failed.'),
                    default => Craft::t('sherlock', 'The request failed.'),
                },
            };
            $message = Craft::t('sherlock', 'Sherlock could not load the site’s base URL. {reason} See the Sherlock log for details.', ['reason' => $reason]);

            Sherlock::$plugin->log($message);
            Sherlock::$plugin->log($exception->getMessage());

            throw new HttpException(502, $message);
        }
    }

    /**
     * Runs a test.
     */
    public function runTest(string $test): TestModel
    {
        $this->beforeRunTests();

        $testModel = new TestModel(Sherlock::$plugin->settings->{$test});
        $testModel->highSecurityLevel = Sherlock::$plugin->settings->highSecurityLevel;

        switch ($test) {
            case 'criticalCraftUpdates':
                if ($this->updates->cms->getHasCritical()) {
                    $criticalCraftUpdates = [];

                    foreach ($this->updates->cms->releases as $release) {
                        if ($release->critical) {
                            $criticalCraftUpdates[] = Html::a(
                                    $release->version,
                                    'https://github.com/craftcms/cms/blob/develop/CHANGELOG.md#' . str_replace('.', '-', $release->version)
                                ) . Html::tag(
                                    'span',
                                    'Version ' . $release->version . ' is a critical update, released on ' . $this->formatDate($release->date) . '.',
                                    ['class' => 'info']
                                );
                        }
                    }

                    $testModel->failTest();
                    $testModel->value = implode(' , ', $criticalCraftUpdates);
                }

                break;

            case 'criticalPluginUpdates':
                $criticalPluginUpdates = [];

                if (!empty($this->updates->plugins)) {
                    $plugins = Craft::$app->getPlugins();
                    foreach ($this->updates->plugins as $handle => $update) {
                        if ($update->getHasCritical()) {
                            /** @var Plugin|null $plugin */
                            $plugin = $plugins->getPlugin($handle);
                            $disabled = $plugin === null && $plugins->isPluginDisabled($handle);
                            $pluginLabel = $plugin === null
                                ? Html::encode($handle)
                                : Html::a($plugin->name, $plugin->changelogUrl, ['target' => '_blank']);

                            if ($disabled) {
                                $name = $plugins->getComposerPluginInfo($handle)['name'] ?? $handle;
                                $pluginLabel = Html::encode(Craft::t('sherlock', '{plugin} (disabled)', ['plugin' => $name]));
                            }

                            foreach ($update->releases as $release) {
                                if ($release->critical) {
                                    $criticalPluginUpdates[] = $pluginLabel . Html::tag(
                                            'span',
                                            'Version ' . $release->version . ' is a critical update, released on ' . $this->formatDate($release->date) . '.',
                                            ['class' => 'info']
                                        );

                                    if ($disabled) {
                                        $testModel->warning = true;
                                    } else {
                                        $testModel->failTest();
                                    }
                                }
                            }
                        }
                    }
                }

                $testModel->value = implode(' , ', $criticalPluginUpdates);

                break;

            case 'craftUpdates':
                if ($this->updates->cms->getHasReleases()) {
                    $testModel->failTest();
                }

                break;

            case 'pluginUpdates':
                $pluginUpdates = [];

                if (!empty($this->updates->plugins)) {
                    foreach ($this->updates->plugins as $handle => $update) {
                        if (!empty($update->releases)) {
                            $latestRelease = $update->getLatest();

                            /** @var Plugin $plugin */
                            $plugin = Craft::$app->getPlugins()->getPlugin($handle);

                            if ($plugin !== null) {
                                $pluginUpdates[] = Html::a(
                                        $plugin->name,
                                        $plugin->changelogUrl,
                                        ['target' => '_blank'],
                                    ) . Html::tag(
                                        'span',
                                        'Local version ' . $plugin->version . ' is ' . count($update->releases) . ' release' . (count($update->releases) != 1 ? 's' : '') . ' behind latest version ' . $latestRelease->version . ', released on ' . $this->formatDate($latestRelease->date) . '.',
                                        ['class' => 'info']
                                    );
                            }
                        }
                    }
                }

                if (!empty($pluginUpdates)) {
                    $testModel->failTest();
                    $testModel->value = implode(' , ', $pluginUpdates);
                }

                break;

            case 'httpsControlPanel':
                $url = UrlHelper::baseCpUrl();

                if (!str_starts_with($url, 'http')) {
                    $url = trim($this->siteUrl, '/') . '/' . Craft::$app->getConfig()->getGeneral()->cpTrigger;
                }

                if (!$this->redirectsToHttps($url)) {
                    $testModel->failTest();
                }

                break;

            case 'httpsFrontEnd':
                if (!$this->redirectsToHttps($this->siteUrl)) {
                    $testModel->failTest();
                }

                break;

            case 'craftFilePermissions':
                $paths = [
                    '.env' => Craft::getAlias('@root/.env'),
                    '.env.php' => Craft::getAlias('@root/.env.php'),
                    'composer.json' => Craft::getAlias('@root/composer.json'),
                    'composer.lock' => Craft::getAlias('@root/composer.lock'),
                    'config/license.key' => Craft::getAlias('@config/license.key'),
                ];

                $pathsFailed = $this->getPathsWritableByEveryone($paths);

                if (!empty($pathsFailed)) {
                    $testModel->failTest();

                    $testModel->value = implode(', ', array_keys($pathsFailed));
                }

                break;

            case 'craftFolderPermissions':
                $paths = [
                    'config/project' => Craft::getAlias('@config/project'),
                    'storage' => Craft::getAlias('@storage'),
                    'vendor' => Craft::getAlias('@vendor'),
                    'webroot/cpresources' => Craft::getAlias('@webroot/cpresources'),
                ];

                $pathsFailed = $this->getPathsWritableByEveryone($paths);

                if (!empty($pathsFailed)) {
                    $testModel->failTest();

                    $testModel->value = implode(', ', array_keys($pathsFailed));
                }

                break;

            case 'craftFoldersAboveWebRoot':
                $paths = [
                    'root' => Craft::getAlias('@root'),
                    'config' => Craft::getAlias('@config'),
                    'storage' => Craft::getAlias('@storage'),
                    'templates' => Craft::getAlias('@templates'),
                ];
                $pathsFailed = [];

                $webroot = Craft::getAlias('@webroot');

                foreach ($paths as $key => $path) {
                    // If the webroot is a substring of the path
                    if (str_contains($path, $webroot)) {
                        $pathsFailed[] = $key;
                    }
                }

                if (!empty($pathsFailed)) {
                    $testModel->failTest();

                    $testModel->value = implode(', ', $pathsFailed);
                }

                break;

            case 'phpVersion':
                $version = $this->getPhpVersion();
                $branch = implode('.', array_slice(explode('.', $version), 0, 2));
                $eolDate = self::PHP_EOL_DATES[$branch] ?? null;

                if ($eolDate === null || strtotime($eolDate) <= time()) {
                    $testModel->failTest();
                }

                $testModel->value = $version;

                break;

            case 'phpSecurityUpdates':
                $version = $this->getPhpVersion();
                $branch = implode('.', array_slice(explode('.', $version), 0, 2));
                $minimumVersion = self::PHP_SECURITY_UPDATE_THRESHOLDS[$branch] ?? null;

                if ($minimumVersion === null || version_compare($version, $minimumVersion, '<')) {
                    $testModel->failTest();
                }

                $testModel->value = $version . ($minimumVersion === null
                        ? ' (this PHP branch has no known minimum security patch version)'
                        : ' (requires ' . $minimumVersion . ' or later)');

                break;

            case 'phpComposerVersion':
                $version = PHP_VERSION;
                $json = json_decode(file_get_contents(Craft::getAlias('@root/composer.json')));
                $requiredVersion = $json->config->platform->php ?? null;

                if (empty($requiredVersion)) {
                    break;
                }

                $versionParts = explode('.', $version);
                $versionMinor = $versionParts[0] . '.' . $versionParts[1];
                $requiredVersionParts = explode('.', $requiredVersion);
                $requiredVersionMinor = $requiredVersionParts[0] . '.' . $requiredVersionParts[1];

                // Only compare minor version
                if (version_compare($requiredVersionMinor, $versionMinor, '<')) {
                    $testModel->failTest();
                    $testModel->value = PHP_VERSION;
                }

                break;

            case 'adminUsername':
                $user = Craft::$app->getUsers()->getUserByUsernameOrEmail('admin');

                if ($user && $user->admin) {
                    $testModel->failTest();
                }

                break;

            case 'requireEmailVerification':
                if (Craft::$app->getProjectConfig()->get('users.requireEmailVerification') === false) {
                    $testModel->failTest();
                }

                break;

            case 'webAliasInSiteBaseUrl':
                if (Craft::$app->getRequest()->isWebAliasSetDynamically) {
                    $currentSite = Craft::$app->getSites()->getCurrentSite();
                    $unparsedBaseUrl = $currentSite->getBaseUrl(false);

                    if (str_contains($unparsedBaseUrl, '@web')) {
                        $testModel->failTest();
                    }
                }

                break;

            case 'webAliasInVolumeBaseUrl':
                if (Craft::$app->getRequest()->isWebAliasSetDynamically) {
                    $volumes = Craft::$app->getVolumes()->getAllVolumes();
                    $volumesFailed = [];

                    foreach ($volumes as $volume) {
                        $filesystem = $volume->getFs();
                        if ($filesystem->hasUrls && str_contains($filesystem->url, '@web')) {
                            $volumesFailed[] = $volume->name;
                        }
                    }

                    if (!empty($volumesFailed)) {
                        $testModel->failTest();
                        $testModel->value = implode(' , ', $volumesFailed);
                    }
                }

                break;

            case 'contentSecurityPolicy':
                $value = $this->getHeaderValue('Content-Security-Policy');
                $headerSet = !empty($value);

                if (!$headerSet) {
                    // Look for meta tag
                    preg_match('/<meta http-equiv="Content-Security-Policy" content="(.*?)"/si', $this->siteUrlResponse['body'], $matches);
                    $value = $matches[1] ?? '';
                }

                if (empty($value)) {
                    $testModel->failTest();
                    $testModel->value = 'Neither Content-Security-Policy header nor meta tag are set';
                } else {
                    $testModel->value = 'Content-Security-Policy ' . ($headerSet ? 'header' : 'meta tag') . ' ';

                    if (str_contains($value, 'unsafe-inline') || str_contains($value, 'unsafe-eval')) {
                        $testModel->warning = true;
                        $testModel->value .= 'contains "unsafe" values';
                    } else {
                        $testModel->value .= 'is set';
                    }
                }

                break;

            case 'cors':
                $value = $this->getHeaderValue('Access-Control-Allow-Origin');

                if ($value) {
                    if ($value == '*') {
                        $testModel->failTest();
                    } else {
                        $testModel->warning = true;
                    }

                    $testModel->value = '"' . $value . '"';
                }

                break;

            case 'referrerPolicy':
                $value = $this->getHeaderValue('Referrer-Policy');

                if (empty($value)) {
                    $testModel->failTest();
                } else {
                    $testModel->value = '"' . $value . '"';
                }

                break;

            case 'strictTransportSecurity':
                $value = $this->getHeaderValue('Strict-Transport-Security');

                if (empty($value)) {
                    $testModel->failTest();
                } else {
                    $seconds = explode('=', $value)[1] ?? 0;

                    if ($seconds < $testModel->threshold) {
                        $testModel->warning = true;
                    } else {
                        $testModel->value = '"' . $value . '"';
                    }
                }

                break;

            case 'xContentTypeOptions':
                $value = $this->getHeaderValue('X-Content-Type-Options');

                if ($value != 'nosniff') {
                    $testModel->failTest();
                } else {
                    $testModel->value = '"' . $value . '"';
                }

                break;

            case 'xFrameOptions':
                $value = $this->getHeaderValue('X-Frame-Options');

                if ($value != 'DENY' && $value != 'SAMEORIGIN') {
                    $testModel->failTest();
                } else {
                    $testModel->value = '"' . $value . '"';
                }

                break;

            case 'useSecureCookies':
                $value = Craft::$app->getConfig()->getGeneral()->useSecureCookies;

                if ($value === true) {
                    $testModel->value = 'The Secure cookie flag is enabled';
                } elseif ($value === 'auto') {
                    $request = Craft::$app->getRequest();

                    if (!$request instanceof Request) {
                        $testModel->failTest();
                        $testModel->value = 'auto (HTTPS detection cannot be verified in a console request)';
                    } elseif (!$request->getIsSecureConnection()) {
                        $testModel->failTest();
                        $testModel->value = 'auto (the current request is not detected as HTTPS)';
                    } else {
                        $testModel->value = 'auto (the current request is detected as HTTPS)';
                    }
                } else {
                    $testModel->failTest();
                    $testModel->value = $value === false ? 'The Secure cookie flag is disabled' : 'Unsupported secure cookie setting';
                }

                break;

            case 'enableCsrfProtection':
            case 'requireMatchingUserAgentForSession':
            case 'requireUserAgentAndIpForSession':
            case 'preventUserEnumeration':
            case 'sanitizeSvgUploads':
                if (!Craft::$app->getConfig()->getGeneral()->{$test}) {
                    $testModel->failTest();
                }

                break;

            case 'deferPublicRegistrationPassword':
            case 'devMode':
            case 'sendPoweredByHeader':
            case 'testToEmailAddress':
            case 'translationDebugOutput':
                if (Craft::$app->getConfig()->getGeneral()->{$test}) {
                    $testModel->failTest();
                }

                break;

            case 'defaultDirMode':
            case 'defaultFileMode':
                $value = Craft::$app->getConfig()->getGeneral()->{$test};

                if ($value > $testModel->threshold) {
                    $testModel->failTest();
                } else {
                    $testModel->value = $value ? '0' . decoct($value) : 'null';
                }

                break;

            case 'defaultTokenDuration':
            case 'verificationCodeDuration':
                $value = Craft::$app->getConfig()->getGeneral()->{$test};
                $seconds = ConfigHelper::durationInSeconds($value);

                if ($seconds > $testModel->threshold) {
                    $testModel->failTest();
                } else {
                    $testModel->value = $value;
                }

                break;

            case 'cpTrigger':
                $value = Craft::$app->getConfig()->getGeneral()->{$test};

                if ($value == 'admin') {
                    $testModel->failTest();
                }

                break;

            case 'blowfishHashCost':
                $value = Craft::$app->getConfig()->getGeneral()->{$test};

                if ($value < $testModel->threshold) {
                    $testModel->failTest();
                } else {
                    $testModel->value = $value;
                }

                break;

            case 'cooldownDuration':
            case 'invalidLoginWindowDuration':
                $value = Craft::$app->getConfig()->getGeneral()->{$test};
                $seconds = ConfigHelper::durationInSeconds($value);

                if ($test === 'cooldownDuration' && $seconds === 0) {
                    $testModel->value = '0 (indefinite lockout; an administrator must unlock the account)';
                } elseif ($seconds < $testModel->threshold) {
                    $testModel->failTest();
                } else {
                    $testModel->value = $value;
                }

                break;

            case 'maxInvalidLogins':
                $value = Craft::$app->getConfig()->getGeneral()->{$test};

                if (!$value) {
                    $testModel->failTest();
                } elseif ($value > $testModel->threshold) {
                    $testModel->warning = true;
                } else {
                    $testModel->value = $value;
                }

                break;

            case 'rememberedUserSessionDuration':
                $value = Craft::$app->getConfig()->getGeneral()->{$test};

                if ($value) {
                    $seconds = ConfigHelper::durationInSeconds($value);

                    if ($seconds > $testModel->threshold) {
                        $testModel->failTest();
                    } else {
                        $testModel->value = $value;
                    }
                }

                break;

            case 'userSessionDuration':
            case 'elevatedSessionDuration':
                $value = Craft::$app->getConfig()->getGeneral()->{$test};

                if (!$value) {
                    $testModel->failTest();
                } else {
                    $seconds = ConfigHelper::durationInSeconds($value);

                    if ($seconds > $testModel->threshold) {
                        $testModel->warning = true;
                    } else {
                        $testModel->value = $value;
                    }
                }

                break;
        }

        return $testModel;
    }

    /**
     * Returns the PHP version used by the current process.
     */
    protected function getPhpVersion(): string
    {
        return PHP_VERSION;
    }

    /**
     * Returns a header value.
     */
    private function getHeaderValue(string $name): string
    {
        // Use lower-case name if it exists in the header
        if (!empty($this->siteUrlResponse['headers'][strtolower($name)])) {
            $name = strtolower($name);
        }

        $value = $this->siteUrlResponse['headers'][$name] ?? '';

        if (is_array($value)) {
            $value = $value[0] ?? '';
        }

        // URL decode and strip tags to make it safe to output raw
        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $value = strip_tags(urldecode($value));

        return $value;
    }

    /**
     * Returns paths that are writable by everyone.
     *
     * @param array $paths
     * @return array
     */
    private function getPathsWritableByEveryone(array $paths): array
    {
        $writablePaths = [];

        foreach ($paths as $key => $path) {
            // If the path exists and is writable by everyone
            if ((is_file($path) || is_dir($path)) && substr(decoct(fileperms($path)), -1) >= 6) {
                $writablePaths[$key] = $path;
            }
        }

        return $writablePaths;
    }

    /**
     * Returns a formatted date.
     */
    private function formatDate(DateTime|int|string|null $date): string
    {
        return Craft::$app->getFormatter()->asDate($date, 'long');
    }

    /**
     * Returns whether an insecure URL redirects to HTTPS or errors.
     */
    private function redirectsToHttps(string $url): bool
    {
        /** @noinspection HttpUrlsUsage */
        $url = str_replace('https://', 'http://', $url);
        $scheme = null;

        try {
            // Get redirect URL scheme of insecure URL
            $this->client->get($url, [
                'on_stats' => function(TransferStats $stats) use (&$scheme) {
                    $scheme = $stats->getEffectiveUri()->getScheme();
                },
            ]);

            if ($scheme != 'https') {
                return false;
            }
        } catch (GuzzleException) {
            // An error indicates that insecure requests are blocked, so allow to pass
        }

        return true;
    }
}
