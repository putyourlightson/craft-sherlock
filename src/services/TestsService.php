<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\services;

use Craft;
use craft\base\Component;
use craft\base\Plugin;
use craft\helpers\ConfigHelper;
use craft\helpers\UrlHelper;
use craft\models\Updates;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\UriInterface;
use putyourlightson\sherlock\models\TestModel;
use putyourlightson\sherlock\Sherlock;
use yii\web\HttpException;

/**
 * Tests Service
 *
 * @property array $testNames
 */
class TestsService extends Component
{
    // Properties
    // =========================================================================

    /**
     * @var Client
     */
    private $_client;

    /**
     * @var array
     */
    private $_headers;

    /**
     * @var UriInterface
     */
    private $_effectiveUrl;

    /**
     * @var Updates
     */
    private $_updates;

    // Public Methods
    // =========================================================================

    /**
     * Get test names
     *
     * @return array
     */
    public function getTestNames(): array
    {
        // Default tests
        $tests = [
            'criticalCraftUpdates',
            'criticalPluginUpdates',
            'httpsControlPanel',
            'httpsFrontEnd',
            'cors',
            'xFrameOptions',
            'xContentTypeOptions',
            'xXssProtection',
            'strictTransportSecurity',
            'craftFoldersAboveWebRoot',
            'craftFolderPermissions',
            'craftFilePermissions',
            'phpVersion',
            'craftUpdates',
            'pluginUpdates',
            'requireEmailVerification',
            'devMode',
            'translationDebugOutput',
            'defaultFileMode',
            'defaultDirMode',
            'defaultTokenDuration',
            'enableCsrfProtection',
            'useSecureCookies',
            'cpTrigger',
            'blowfishHashCost',
            'cooldownDuration',
            'deferPublicRegistrationPassword',
            'elevatedSessionDuration',
            'invalidLoginWindowDuration',
            'maxInvalidLogins',
            'preventUserEnumeration',
            'rememberedUserSessionDuration',
            'requireMatchingUserAgentForSession',
            'requireUserAgentAndIpForSession',
            'sanitizeSvgUploads',
            'testToEmailAddress',
            'userSessionDuration',
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
     * Run test
     *
     * @param $test
     *
     * @return TestModel
     * @throws HttpException
     */
    public function runTest($test): TestModel
    {
        // Get client
        if (empty($this->_client)) {
            $this->_client = Craft::createGuzzleClient([
                'timeout' => 10,
            ]);
        }

        // Get site headers
        if (empty($this->_headers)) {
            $url = UrlHelper::baseSiteUrl();

            try {
                $this->_headers = $this->_client->get($url)->getHeaders();

                // Get effective URL of insecure front-end site url
                if (strpos($url, 'https://') === 0) {
                    $this->_client->get(str_replace('https://', 'http://', $url), [
                        'on_stats' => function(TransferStats $stats) {
                            $this->_effectiveUrl = $stats->getEffectiveUri();
                        },
                    ]);
                }
            }
            catch (ConnectException $e) {
            }
            catch (ServerException $e) {
            }

            if (empty($this->_headers)) {
                throw new HttpException(500, Craft::t('sherlock', 'unable to connect to {url}. Please ensure that the site is reachable and that the system is turned on.', ['url' => $url]));
            }
        }

        // Get updates, forcing a refresh
        if (empty($this->_updates)) {
            $this->_updates = Craft::$app->getUpdates()->getUpdates(true);
        }

        $testModel = new TestModel(Sherlock::$plugin->settings->{$test});
        $testModel->highSecurityLevel = Sherlock::$plugin->settings->highSecurityLevel;

        switch ($test) {
            case 'httpsControlPanel':
                if (strpos(UrlHelper::cpUrl(), 'https') !== 0) {
                    $testModel->failTest();
                }

                break;

            case 'httpsFrontEnd':
                if (strpos($this->_effectiveUrl, 'https') !== 0) {
                    $testModel->failTest();
                }

                break;

            case 'cors':
                $value = $this->_getHeaderValue('Access-Control-Allow-Origin');

                if ($value) {
                    if ($value == '*') {
                        $testModel->failTest();
                    }
                    else {
                        if (is_array($value)) {
                            $value = implode(', ', $value);
                        }

                        $testModel->warning = true;
                    }

                    $testModel->value = '"'.$value.'"';
                }

                break;

            case 'xFrameOptions':
                $value = $this->_getHeaderValue('X-Frame-Options');

                if ($value != 'DENY' && $value != 'SAMEORIGIN') {
                    $testModel->failTest();
                }
                else {
                    $testModel->value = '"'.$value.'"';
                }

                break;

            case 'xContentTypeOptions':
                $value = $this->_getHeaderValue('X-Content-Type-Options');

                if ($value != 'nosniff') {
                    $testModel->failTest();
                }
                else {
                    $testModel->value = '"'.$value.'"';
                }

                break;

            case 'xXssProtection':
                $value = $this->_getHeaderValue('X-Xss-Protection');

                // If not set then check alternative case
                $value = $value ?: $this->_getHeaderValue('X-XSS-Protection');

                // Remove spaces and convert to lower case for comparison
                $compareValue = strtolower(str_replace(' ', '', $value));

                if ($compareValue != '1;mode=block') {
                    $testModel->failTest();
                }
                else {
                    $testModel->value = '"'.$value.'"';
                }

                break;

            case 'strictTransportSecurity':
                $value = $this->_getHeaderValue('Strict-Transport-Security');

                if (strpos($this->_effectiveUrl, 'https') === 0 && empty($value)) {
                    $testModel->failTest();
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
                $cwd = getcwd();

                foreach ($paths as $key => $path) {
                    // if the current working directory is a substring of the path
                    if (strpos($path, $cwd) !== false) {
                        $pathsFailed[] = $key;
                    }
                }

                if (count($pathsFailed)) {
                    $testModel->failTest();

                    $testModel->value = implode(', ', $pathsFailed);
                }

                break;

            case 'craftFolderPermissions':
                $paths = [
                    'root' => Craft::getAlias('@root'),
                    'config' => Craft::getAlias('@config'),
                    'storage' => Craft::getAlias('@storage'),
                ];
                $pathsFailed = [];

                foreach ($paths as $key => $path) {
                    // If the path is writable by everyone
                    if (substr(decoct(fileperms($path)), -1) >= 6) {
                        $pathsFailed[] = $key;
                    }
                }

                if (count($pathsFailed)) {
                    $testModel->failTest();

                    $testModel->value = implode(', ', $pathsFailed);
                }

                break;

            case 'craftFilePermissions':
                $configPath = Craft::getAlias('@config');
                $files = [
                    'config/db.php' => $configPath.'/db.php',
                    'config/general.php' => $configPath.'/general.php',
                    'config/license.key' => $configPath.'/license.key',
                ];
                $filesFailed = [];

                foreach ($files as $key => $file) {
                    // If the file is writable by everyone
                    if (substr(decoct(fileperms($file)), -1) >= 6) {
                        $filesFailed[] = $key;
                    }
                }

                if (count($filesFailed)) {
                    $testModel->failTest();

                    $testModel->value = implode(', ', $filesFailed);
                }

                break;

            case 'phpVersion':
                $version = PHP_VERSION;
                $value = substr($version, 0, 3);
                $eolDate = '';

                if (isset($testModel->thresholds[$value])) {
                    if (strtotime($testModel->thresholds[$value]) < time()) {
                        $testModel->failTest();
                    }

                    $eolDate = $testModel->thresholds[$value];
                }

                $testModel->value = $version.($eolDate ? ' (until '.$eolDate.')' : '');

                break;

            case 'craftUpdates':
                if ($this->_updates->cms->getHasReleases()) {
                    $testModel->failTest();
                }

                break;

            case 'criticalCraftUpdates':
                if ($this->_updates->cms->getHasCritical()) {
                    $criticalCraftUpdates = [];

                    foreach ($this->_updates->cms->releases as $release) {
                        if ($release->critical) {
                            $criticalCraftUpdates[] = '<a href="https://github.com/craftcms/cms/blob/master/CHANGELOG-v3.md#'.str_replace('.', '-', $release->version).'" target="_blank">'.$release->version.'</a> <span class="info">Version '.$release->version.' is a critical update, released on '.Craft::$app->getFormatter()->asDate($release->date).'.</span>';
                        }
                    }

                    $testModel->failTest();
                    $testModel->value = implode(' , ', $criticalCraftUpdates);
                }

                break;

            case 'pluginUpdates':
                $pluginUpdates = [];

                if (!empty($this->_updates->plugins)) {
                    foreach ($this->_updates->plugins as $handle => $update) {
                        if (!empty($update->releases)) {
                            $latestRelease = $update->getLatest();

                            /** @var Plugin $plugin */
                            $plugin = Craft::$app->getPlugins()->getPlugin($handle);

                            if ($plugin !== null) {
                                $pluginUpdates[] = '
                                <a href="'.$plugin->changelogUrl.'" target="_blank">'.$plugin->name.'</a> 
                                <span class="info">Local version '.$plugin->version.' is '.count($update->releases).' release'.(count($update->releases) != 1 ? 's' : '').' behind latest version '.$latestRelease->version.', released on '.Craft::$app->getFormatter()->asDate($latestRelease->date).'.</span>';
                            }
                        }
                    }
                }

                if (!empty($pluginUpdates)) {
                    $testModel->failTest();
                    $testModel->value = implode(' , ', $pluginUpdates);
                }

                break;

            case 'criticalPluginUpdates':
                $criticalPluginUpdates = [];

                if (!empty($this->_updates->plugins)) {
                    foreach ($this->_updates->plugins as $handle => $update) {
                        if ($update->getHasCritical()) {
                            /** @var Plugin $plugin */
                            $plugin = Craft::$app->getPlugins()->getPlugin($handle);

                            foreach ($update->releases as $release) {
                                if ($release->critical) {
                                    $criticalPluginUpdates[] = '<a href="'.$plugin->changelogUrl.'" target="_blank">'.$plugin->name.'</a> <span class="info">Version '.$release->version.' is a critical update, released on '.Craft::$app->getFormatter()->asDate($release->date).'.</span>';
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

            case 'requireEmailVerification':
                if (Craft::$app->getSystemSettings()->getSetting('users', 'requireEmailVerification') === false) {
                    $testModel->failTest();
                }

                break;

            case 'devMode':
                if (Craft::$app->getConfig()->getGeneral()->{$test}) {
                    $testModel->pass = $testModel->forceFail ? false : !($testModel->canFail && Sherlock::$plugin->settings->liveMode);
                    $testModel->warning = true;
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

            case 'translationDebugOutput':
            case 'testToEmailAddress':
            case 'deferPublicRegistrationPassword':
                if (Craft::$app->getConfig()->getGeneral()->{$test}) {
                    $testModel->failTest();
                }

                break;

            case 'defaultFileMode':
            case 'defaultDirMode':
                $value = Craft::$app->getConfig()->getGeneral()->{$test};

                if ($value > $testModel->threshold) {
                    $testModel->failTest();
                }

                else {
                    $testModel->value = $value ? '0'.decoct($value) : 'null';
                }

                break;

            case 'defaultTokenDuration':
            case 'verificationCodeDuration':
                $value = Craft::$app->getConfig()->getGeneral()->{$test};
                $seconds = ConfigHelper::durationInSeconds($value);

                if ($seconds > $testModel->threshold) {
                    $testModel->failTest();
                }

                else {
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
                }

                else {
                    $testModel->value = $value;
                }

                break;

            case 'cooldownDuration':
            case 'invalidLoginWindowDuration':
                $value = Craft::$app->getConfig()->getGeneral()->{$test};
                $seconds = ConfigHelper::durationInSeconds($value);

                if ($seconds < $testModel->threshold) {
                    $testModel->failTest();
                }

                else {
                    $testModel->value = $value;
                }

                break;

            case 'maxInvalidLogins':
                $value = Craft::$app->getConfig()->getGeneral()->{$test};

                if (!$value) {
                    $testModel->failTest();
                }

                else if ($value > $testModel->threshold) {
                    $testModel->warning = true;
                }

                else {
                    $testModel->value = $value;
                }

                break;

            case 'rememberedUserSessionDuration':
                $value = Craft::$app->getConfig()->getGeneral()->{$test};

                if ($value) {
                    $seconds = ConfigHelper::durationInSeconds($value);

                    if ($seconds > $testModel->threshold) {
                        $testModel->failTest();
                    }

                    else {
                        $testModel->value = $value;
                    }
                }

                break;

            case 'userSessionDuration':
            case 'elevatedSessionDuration':
                $value = Craft::$app->getConfig()->getGeneral()->{$test};

                if (!$value) {
                    $testModel->failTest();
                }

                else {
                    $seconds = ConfigHelper::durationInSeconds($value);

                    if ($seconds > $testModel->threshold) {
                        $testModel->warning = true;
                    }

                    else {
                        $testModel->value = $value;
                    }
                }

                break;
        }

        return $testModel;
    }

    /**
     * Returns a header value
     *
     * @param string $name
     *
     * @return string
     */
    public function _getHeaderValue($name): string
    {
        // Use lower-case name if it exists in the header
        if (!empty($this->_headers[strtolower($name)])) {
            $name = strtolower($name);
        }

        $value = $this->_headers[$name] ?? '';

        if (is_array($value)) {
            $value = $value[0] ?? '';
        }

        // URL decode and strip tags to make it safe to output raw
        $value = strip_tags(urldecode($value));

        return $value;
    }
}
