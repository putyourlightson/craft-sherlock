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
use DateTime;
use DOMDocument;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\UriInterface;
use putyourlightson\sherlock\events\RunTestsEvent;
use putyourlightson\sherlock\models\TestModel;
use putyourlightson\sherlock\Sherlock;
use yii\base\Event;
use yii\web\NotFoundHttpException;

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

        $config = ['timeout' => 10];
        if (!Sherlock::$plugin->getIsLite() && Sherlock::$plugin->settings->basicAuthEnabled) {
            $config['auth'] = [
                Sherlock::$plugin->settings->basicAuthUsername,
                Sherlock::$plugin->settings->basicAuthPassword,
            ];
        }

        $this->client = Craft::createGuzzleClient($config);

        // Get updates, forcing a refresh
        $this->updates = Craft::$app->getUpdates()->getUpdates(true);

        // Get the current site’s base URL if not already set (by unit tests)
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
            $message = Craft::t('sherlock', 'Unable to connect to "{url}". Please ensure that the site is reachable and that the system is turned on.', ['url' => $this->siteUrl]);

            Sherlock::$plugin->log($message);
            Sherlock::$plugin->log($exception->getMessage());

            throw new NotFoundHttpException($message);
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
                    foreach ($this->updates->plugins as $handle => $update) {
                        if ($update->getHasCritical()) {
                            /** @var Plugin $plugin */
                            $plugin = Craft::$app->getPlugins()->getPlugin($handle);

                            foreach ($update->releases as $release) {
                                if ($release->critical) {
                                    $criticalPluginUpdates[] = Html::a(
                                            $plugin->name,
                                            $plugin->changelogUrl,
                                            ['target' => '_blank'],
                                        ) . Html::tag(
                                            'span',
                                            'Version ' . $release->version . ' is a critical update, released on ' . $this->formatDate($release->date) . '.',
                                            ['class' => 'info']
                                        );
                                }
                            }
                        }
                    }
                }

                if (!empty($criticalPluginUpdates)) {
                    $testModel->failTest();
                    $testModel->value = implode(' , ', $criticalPluginUpdates);
                }

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

                $this->checkHttps($url, $testModel);

                break;

            case 'httpsFrontEnd':
                $this->checkHttps($this->siteUrl, $testModel);

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
                $eolDate = $testModel->thresholds[$branch] ?? null;

                if ($eolDate === null || strtotime($eolDate) <= time()) {
                    $testModel->failTest();
                }

                $testModel->value = $version;

                break;

            case 'phpSecurityUpdates':
                $version = $this->getPhpVersion();
                $branch = implode('.', array_slice(explode('.', $version), 0, 2));
                $minimumVersion = $testModel->thresholds[$branch] ?? null;

                if ($minimumVersion === null || version_compare($version, $minimumVersion, '<')) {
                    $testModel->failTest();
                }

                $testModel->value = $version . ($minimumVersion === null
                        ? ' (no security update threshold is configured for this PHP branch)'
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
                $policies = $this->getContentSecurityPolicies();
                $restrictedElements = false;
                $restrictedAttributes = false;
                $restrictedEval = false;

                foreach ($policies as $directives) {
                    $scriptSources = $directives['script-src'] ?? $directives['default-src'] ?? null;
                    $elementSources = $directives['script-src-elem'] ?? $scriptSources;
                    $attributeSources = $directives['script-src-attr'] ?? $scriptSources;

                    // Each enforced policy adds restrictions, so separate policies can protect different contexts.
                    $restrictedElements = $restrictedElements || $this->restrictsScripts($elementSources);
                    $restrictedAttributes = $restrictedAttributes || ($attributeSources !== null && !$this->allowsInlineScripts($attributeSources));
                    $restrictedEval = $restrictedEval || ($scriptSources !== null && !in_array("'unsafe-eval'", $scriptSources, true));
                }

                $unrestricted = [];
                if (!$restrictedElements) {
                    $unrestricted[] = 'script elements';
                }
                if (!$restrictedAttributes) {
                    $unrestricted[] = 'inline event handlers';
                }
                if (!$restrictedEval) {
                    $unrestricted[] = 'eval';
                }

                if ($policies === []) {
                    $testModel->failTest();
                    $testModel->value = 'No enforced Content-Security-Policy header or meta tag is set';
                } elseif ($unrestricted !== []) {
                    $testModel->failTest();
                    $testModel->value = 'Content-Security-Policy is set but does not sufficiently restrict ' . implode(', ', $unrestricted);
                } else {
                    $testModel->value = 'Content-Security-Policy restricts script elements, inline event handlers and eval';
                }

                break;

            case 'cors':
                $value = $this->getHeaderValue('Access-Control-Allow-Origin');

                if ($value) {
                    // Public resources may intentionally allow cross-origin access.
                    $testModel->warning = true;
                    $testModel->value = '"' . $value . '"';
                }

                break;

            case 'referrerPolicy':
                $value = $this->getHeaderValue('Referrer-Policy', true);
                $policy = '';
                $validPolicies = [
                    'no-referrer',
                    'no-referrer-when-downgrade',
                    'origin',
                    'origin-when-cross-origin',
                    'same-origin',
                    'strict-origin',
                    'strict-origin-when-cross-origin',
                    'unsafe-url',
                ];

                // Browsers use the last recognised policy in a comma-separated list.
                foreach (explode(',', $value) as $candidate) {
                    $candidate = trim($candidate);

                    if (in_array($candidate, $validPolicies, true)) {
                        $policy = $candidate;
                    }
                }

                if (!in_array($policy, ['no-referrer', 'same-origin', 'strict-origin', 'strict-origin-when-cross-origin'], true)) {
                    $testModel->failTest();
                }

                if ($value !== '') {
                    $testModel->value = '"' . $value . '"';
                }

                break;

            case 'strictTransportSecurity':
                $value = $this->getHeaderValue('Strict-Transport-Security');
                $seconds = null;

                foreach (explode(';', $value) as $directive) {
                    $parts = explode('=', $directive, 2);

                    if (strcasecmp(trim($parts[0]), 'max-age') !== 0) {
                        continue;
                    }

                    $maxAge = trim($parts[1] ?? '');

                    // Reject duplicate directives and values other than (optionally quoted) digits.
                    if ($seconds !== null || !preg_match('/^(?:[0-9]+|"[0-9]+")$/D', $maxAge)) {
                        $seconds = null;
                        break;
                    }

                    $seconds = (int)trim($maxAge, '"');
                }

                if ($seconds === null || $seconds === 0 || $seconds < $testModel->threshold) {
                    $testModel->failTest();
                }

                if ($value !== '') {
                    $testModel->value = '"' . $value . '"';
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
                $frameAncestorsSet = false;
                $restrictiveFrameAncestors = false;

                // Only enforced headers support frame-ancestors; meta tags and report-only policies do not.
                foreach ($this->getContentSecurityPolicies(false) as $directives) {
                    if (isset($directives['frame-ancestors'])) {
                        $frameAncestorsSet = true;
                        $restrictiveFrameAncestors = $restrictiveFrameAncestors || $this->restrictsFraming($directives['frame-ancestors']);
                    }
                }

                if ($frameAncestorsSet) {
                    // An enforced frame-ancestors directive takes precedence over X-Frame-Options.
                    if (!$restrictiveFrameAncestors) {
                        $testModel->failTest();
                    }
                    $testModel->value = $restrictiveFrameAncestors
                        ? 'Content-Security-Policy frame-ancestors restricts framing'
                        : 'Content-Security-Policy frame-ancestors does not sufficiently restrict framing';
                } elseif (!in_array(strtoupper(trim($value)), ['DENY', 'SAMEORIGIN'], true)) {
                    $testModel->failTest();
                } else {
                    $testModel->value = '"' . $value . '"';
                }

                break;

            case 'enableCsrfProtection':
            case 'useSecureCookies':
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
                    $testModel->value = (string)$value;
                }

                break;

            case 'cooldownDuration':
            case 'invalidLoginWindowDuration':
                $value = Craft::$app->getConfig()->getGeneral()->{$test};
                $seconds = ConfigHelper::durationInSeconds($value);

                if ($seconds < $testModel->threshold) {
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
                    $testModel->value = (string)$value;
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
     * Returns parsed enforced CSP policies, optionally including meta tags in the document head.
     *
     * @return array<string, string[]>[]
     */
    private function getContentSecurityPolicies(bool $includeMeta = true): array
    {
        $values = [];
        foreach ($this->siteUrlResponse['headers'] ?? [] as $name => $headers) {
            if (strcasecmp($name, 'Content-Security-Policy') === 0) {
                foreach ((array)$headers as $header) {
                    // Multiple header fields and comma-separated policies are enforced together.
                    array_push($values, ...explode(',', $header));
                }
            }
        }

        $body = $this->siteUrlResponse['body'] ?? '';
        if ($includeMeta && $body !== '') {
            $document = new DOMDocument();
            $document->loadHTML($body, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
            foreach ($document->getElementsByTagName('head') as $head) {
                foreach ($head->getElementsByTagName('meta') as $meta) {
                    if (strcasecmp($meta->getAttribute('http-equiv'), 'Content-Security-Policy') === 0) {
                        $values[] = $meta->getAttribute('content');
                    }
                }
            }
        }

        $policies = [];
        foreach ($values as $value) {
            $directives = [];
            foreach (explode(';', $value) as $directive) {
                $tokens = preg_split('/[\t\n\f\r ]+/', trim($directive), -1, PREG_SPLIT_NO_EMPTY);
                if ($tokens === []) {
                    continue;
                }
                $name = strtolower(array_shift($tokens));
                foreach ($tokens as &$token) {
                    if (in_array(strtolower($token), ["'self'", "'none'", "'unsafe-inline'", "'unsafe-eval'", "'strict-dynamic'"], true)) {
                        $token = strtolower($token);
                    }
                }
                unset($token);
                // Browsers ignore subsequent occurrences of a directive within one policy.
                if (!array_key_exists($name, $directives)) {
                    $directives[$name] = $tokens;
                }
            }
            if ($directives !== []) {
                $policies[] = $directives;
            }
        }

        return $policies;
    }

    /**
     * Returns whether the source list allows arbitrary inline scripts.
     *
     * @param string[] $sources
     */
    private function allowsInlineScripts(array $sources): bool
    {
        if (!in_array("'unsafe-inline'", $sources, true)) {
            return false;
        }

        foreach ($sources as $source) {
            // Nonces and hashes cause modern browsers to ignore unsafe-inline.
            if (preg_match("/^'(?:nonce-|sha(?:256|384|512)-)[A-Za-z0-9+\/_-]+=*'$/iD", $source)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns whether script elements have a restrictive source list.
     *
     * @param string[]|null $sources
     */
    private function restrictsScripts(?array $sources): bool
    {
        if ($sources === null || $this->allowsInlineScripts($sources)) {
            return false;
        }

        if (in_array("'strict-dynamic'", $sources, true)) {
            foreach ($sources as $source) {
                if (preg_match("/^'(?:nonce-|sha(?:256|384|512)-)[A-Za-z0-9+\/_-]+=*'$/iD", $source)) {
                    return true;
                }
            }
        }

        foreach ($sources as $source) {
            if (str_contains($source, '*') || in_array(strtolower($source), ['http:', 'https:', 'data:', 'blob:', 'filesystem:'], true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns whether framing is limited to an explicit list of origins or disallowed.
     *
     * @param string[] $sources
     */
    private function restrictsFraming(array $sources): bool
    {
        if ($sources === [] || $sources === ["'none'"]) {
            return true;
        }

        foreach ($sources as $source) {
            if ($source !== "'self'" && !preg_match('~^https?://[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?(?::[0-9]+)?/?$~iD', $source)) {
                return false;
            }
        }

        return true;
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
    private function getHeaderValue(string $name, bool $combineValues = false): string
    {
        // Use lower-case name if it exists in the header
        if (!empty($this->siteUrlResponse['headers'][strtolower($name)])) {
            $name = strtolower($name);
        }

        $value = $this->siteUrlResponse['headers'][$name] ?? '';

        if (is_array($value)) {
            $value = $combineValues ? implode(', ', $value) : ($value[0] ?? '');
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
        if ($date === null) {
            return '';
        }

        return Craft::$app->getFormatter()->asDate($date, 'long');
    }

    /**
     * Checks HTTPS enforcement without treating unrelated request failures as proof of protection.
     */
    private function checkHttps(string $url, TestModel $testModel): void
    {
        $uri = new Uri($url);
        $responseReceived = false;

        try {
            $scheme = $this->getResponseScheme($uri->withScheme('http'), $responseReceived);

            if ($scheme === 'https') {
                $testModel->value = 'HTTP redirects to HTTPS';

                return;
            }

            $testModel->value = 'HTTP responded without a verified HTTPS redirect';
        } catch (ConnectException $exception) {
            $context = $exception->getHandlerContext();
            $testModel->value = 'Connection failed; HTTPS enforcement could not be verified';

            // cURL error 7 means the connection could not be established. DNS failures and timeouts
            // are inconclusive. Only accept unavailable HTTP if HTTPS can also be verified.
            if (($context['errno'] ?? null) === 7 && !$responseReceived && $exception->getRequest()->getUri()->getScheme() === 'http') {
                try {
                    $scheme = $this->getResponseScheme($uri->withScheme('https'), $responseReceived);

                    if ($scheme === 'https') {
                        $testModel->value = 'HTTP connection is unavailable; HTTPS response verified';

                        return;
                    }
                } catch (GuzzleException) {
                    // An unavailable HTTP endpoint alone does not confirm working HTTPS.
                }
                $testModel->value = 'HTTP connection is unavailable; HTTPS could not be verified';
            }
        } catch (GuzzleException) {
            $testModel->value = 'Request failed; HTTPS enforcement could not be verified';
        }

        $testModel->failTest();
    }

    /**
     * Requests a URL and returns its final scheme, recording any responses received along the way.
     */
    private function getResponseScheme(UriInterface $uri, bool &$responseReceived): ?string
    {
        $scheme = null;
        $this->client->get($uri, [
            // An HTTP error page is still an HTTP response, not evidence of blocked access.
            'http_errors' => false,
            'on_stats' => function(TransferStats $stats) use (&$scheme, &$responseReceived) {
                $scheme = $stats->getEffectiveUri()->getScheme();
                $responseReceived = $responseReceived || $stats->hasResponse();
            },
        ]);

        return $scheme;
    }
}
