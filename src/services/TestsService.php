<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sherlock\services;

use Craft;
use craft\base\Component;
use craft\base\Plugin;
use craft\enums\PluginUpdateStatus;
use craft\enums\VersionUpdateStatus;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\TransferStats;
use putyourlightson\sherlock\models\TestModel;
use putyourlightson\sherlock\Sherlock;

/**
 * Tests Service
 *
 * @property array $testNames
 */
class TestsService extends Component
{
    // Properties
    // =========================================================================

    private $_settings;
    private $_headers;
    private $_effectiveUrl;
    private $_updateModel;

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
        $tests = ['criticalCraftUpdates', 'criticalPluginUpdates', 'pluginVulnerabilities', 'httpsControlPanel', 'httpsFrontEnd', 'cors', 'xFrameOptions', 'xContentTypeOptions', 'xXssProtection', 'strictTransportSecurity', 'purifyHtml', 'craftFoldersAboveWebRoot', 'craftFolderPermissions', 'craftFilePermissions', 'phpVersion', 'craftUpdates', 'pluginUpdates', 'requireEmailVerification', 'devMode', 'translationDebugOutput', 'defaultFileMode', 'defaultDirMode', 'defaultTokenDuration', 'enableCsrfProtection', 'useSecureCookies', 'securityKey', 'cpTrigger', 'blowfishHashCost', 'cooldownDuration', 'invalidLoginWindowDuration', 'maxInvalidLogins', 'rememberedUserSessionDuration', 'requireMatchingUserAgentForSession', 'requireUserAgentAndIpForSession', 'testToEmailAddress', 'userSessionDuration', 'verificationCodeDuration'];

        // Remove disabled tests
        $disabledTests = Sherlock::$plugin->getSettings()->disabledTests;
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
     */
    public function runTest($test): TestModel
    {
        // get settings
        if (empty($this->_settings))
        {
            $this->_settings = Sherlock::$plugin->getSettings();
        }

        // get site headers of insecure front-end site url
        if (empty($this->_headers))
        {
            $url = str_replace('https://', 'http://', UrlHelper::baseSiteUrl());
    		$client = Craft::createGuzzleClient();
    		$response = $client->get($url, [
    			'timeout' => 10,
    			'connect_timeout' => 5,
                'on_stats' => function (TransferStats $stats) {
    		        $this->_effectiveUrl = $stats->getEffectiveUri();
                },
    		]);

            $this->_headers = $response->getHeaders();
        }

        // get updates, forcing a refresh
        if (empty($this->_updateModel))
        {
            $this->_updateModel = Craft::$app->getUpdates()->getUpdates(true);
        }

        $testModel = new TestModel();
        $testModel->highSecurityLevel = $this->_settings->highSecurityLevel;

        switch ($test)
        {
            case 'pluginVulnerabilities':
                if (!empty($this->_settings->pluginVulnerabilitiesFeedUrl) AND stripos($this->_settings->pluginVulnerabilitiesFeedUrl, 'https://') === 0)
                {
                    $pluginVulnerabilities = [];

                    // Create new guzzle client
                    $client = Craft::createGuzzleClient();

                    try
                    {
                        $response = $client->get($this->_settings->pluginVulnerabilitiesFeedUrl, [
                            'timeout' => 10,
                            'connect_timeout' => 5,
                        ]);

                        $responseBody = $response->getBody();
                        $vulnerabilities = Json::decode($responseBody);

                        if ($vulnerabilities)
                        {
                            $installedPlugins = Craft::$app->getPlugins()->getAllPlugins();

                            foreach ($vulnerabilities as $vulnerability)
                            {
                                if (isset($installedPlugins[$vulnerability['handle']]))
                                {
                                    /** @var Plugin $plugin */
                                    $plugin = Craft::$app->getPlugins()->getPlugin($vulnerability['handle']);

                                    if (empty($vulnerability['fixedVersion']) OR version_compare($plugin->getVersion(), $vulnerability['fixedVersion'], '<'))
                                    {
                                        $pluginVulnerabilities[] = '<a href="'.$vulnerability['url'].'" target="_blank">'.$plugin->name.' '.$vulnerability['version'].'</a> <span class="info">'.$vulnerability['description'].(isset($vulnerability['fixedVersion']) ? ' (fixed in version '.$vulnerability['fixedVersion'].')' : '').'</span>';
                                    }
                                }
                            }
                        }

                        else
                        {
                            $testModel->warning = true;
                        }
                    }
                    catch (ConnectException $e) { $testModel->warning = true; }

                    if (!empty($pluginVulnerabilities))
                    {
                        $testModel->failTest();
                        $testModel->value = implode(' , ', $pluginVulnerabilities);
                    }
                }

                else
                {
                    $testModel->warning = true;
                }

                break;

            case 'httpsControlPanel':
                if (strpos(UrlHelper::cpUrl(), 'https') !== 0)
                {
                    $testModel->failTest();
                }

                break;

            case 'httpsFrontEnd':
                if (strpos($this->_effectiveUrl, 'https') !== 0)
                {
                    $testModel->failTest();
                }

                break;

            case 'cors':
                if (isset($this->_headers['Access-Control-Allow-Origin']))
                {
                    if ($this->_headers['Access-Control-Allow-Origin'] == '*')
                    {
                        $testModel->failTest();
                    }

                    else if ($this->_headers['Access-Control-Allow-Origin'])
                    {
                        if (is_array($this->_headers['Access-Control-Allow-Origin'])) {
                            $this->_headers['Access-Control-Allow-Origin'] = implode(', ', $this->_headers['Access-Control-Allow-Origin']);
                        }

                        $testModel->warning = true;
                        $testModel->value = '"'.$this->_headers['Access-Control-Allow-Origin'].'"';
                    }
                }

                break;

            case 'xFrameOptions':
                if (empty($this->_headers['X-Frame-Options']) OR ($this->_headers['X-Frame-Options'] != 'DENY' AND $this->_headers['X-Frame-Options'] != 'SAMEORIGIN'))
                {
                    $testModel->failTest();
                }

                else
                {
                    $testModel->value = '"'.$this->_headers['X-Frame-Options'].'"';
                }

                break;

            case 'xContentTypeOptions':
                if (empty($this->_headers['X-Content-Type-Options']) OR $this->_headers['X-Content-Type-Options'] != 'nosniff')
                {
                    $testModel->failTest();
                }

                else
                {
                    $testModel->value = '"'.$this->_headers['X-Content-Type-Options'].'"';
                }

                break;

            case 'xXssProtection':
                if (empty($this->_headers['X-Xss-Protection']) OR $this->_headers['X-Xss-Protection'] != '1; mode=block')
                {
                    $testModel->failTest();
                }

                else
                {
                    $testModel->value = '"'.$this->_headers['X-Xss-Protection'].'"';
                }

                break;

            case 'strictTransportSecurity':
                if (strpos($this->_effectiveUrl, 'https') === 0 AND empty($this->_headers['Strict-Transport-Security']))
                {
                    $testModel->failTest();
                }

                break;

            /*
            case 'purifyHtml':
                $fields = Craft::$app->getFields()->getAllFields();
                $fieldsFailed = [];

                foreach ($fields as $field)
            	{
                    $fieldtype = $field->getFieldType();

                    if ($fieldtype AND $fieldtype->model->type == 'RichText' AND !$fieldtype->getSettings()->purifyHtml)
                    {
                        $fieldsFailed[] = '<a href="'.UrlHelper::getCpUrl('settings/fields/edit/'.$field->id).'" target="_blank">'.$field->name.'</a>';
                    }
                }

                if (count($fieldsFailed))
                {
                    $testModel->failTest();

                    $testModel->value = implode(', ', $fieldsFailed);
                }

                break;
            */

            case 'craftFoldersAboveWebRoot':
                $paths = [
                    'root' => Craft::getAlias('@root'),
                    'config' => Craft::getAlias('@config'),
                    'storage' => Craft::getAlias('@storage'),
                    'templates' => Craft::getAlias('@templates'),
                ];
                $pathsFailed = [];
                $cwd = getcwd();

                foreach ($paths as $key => $path)
                {
                    // if the current working directory is a substring of the path
                    if (strpos($path, $cwd) !== false)
                    {
                        $pathsFailed[] = $key;
                    }
                }

                if (count($pathsFailed))
                {
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

                foreach ($paths as $key => $path)
                {
                    // If the path is writable by everyone
                    if (substr(decoct(fileperms($path)), -1) >= 6)
                    {
                        $pathsFailed[] = $key;
                    }
                }

                if (count($pathsFailed))
                {
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

                foreach ($files as $key => $file)
                {
                    // If the file is writable by everyone
                    if (substr(decoct(fileperms($file)), -1) >= 6)
                    {
                        $filesFailed[] = $key;
                    }
                }

                if (count($filesFailed))
                {
                    $testModel->failTest();

                    $testModel->value = implode(', ', $filesFailed);
                }

                break;

            case 'phpVersion':
                $version = PHP_VERSION;
                $value = substr($version, 0, 3);
                $eolDate = '';

                if (isset($testModel->thresholds[$value]))
                {
                    if (strtotime($testModel->thresholds[$value]) < time())
                    {
                        $testModel->failTest();
                    }

                    $eolDate = $testModel->thresholds[$value];
                }

                $testModel->value = $version.' (until '.$eolDate.')';

                break;

            case 'craftUpdates':
                if (!empty($this->_updateModel->app) AND $this->_updateModel->app->versionUpdateStatus == VersionUpdateStatus::UpdateAvailable)
                {
                    $testModel->failTest();
                }

                break;

            case 'criticalCraftUpdates':
                $criticalCraftUpdates = [];

                if (!empty($this->_updateModel->app->releases))
				{
                    foreach ($this->_updateModel->app->releases as $release)
                    {
                        if ($release->critical)
                        {
                            $criticalCraftUpdates[] = '<a href="https://craftcms.com/changelog#'.str_replace('.', '-', $release->version).'" target="_blank">'.$release->version.'</a> <span class="info">Version '.$release->version.' is a critical update, released on '.$release->date.'.</span>';
                        }
                    }
                }

                if (!empty($criticalCraftUpdates))
                {
                    $testModel->failTest();
                    $testModel->value = implode(' , ', $criticalCraftUpdates);
                }

                break;

            case 'pluginUpdates':
                $pluginUpdates = [];

                if (!empty($this->_updateModel->plugins))
                {
					foreach ($this->_updateModel->plugins as $plugin)
					{
						if ($plugin->status == PluginUpdateStatus::UpdateAvailable AND !empty($plugin->releases))
						{
                            $pluginUpdates[] = '<a href="'.$plugin->manualDownloadEndpoint.'" target="_blank">'.$plugin->displayName.'</a> <span class="info">Local version '.$plugin->localVersion.' is '.count($plugin->releases).' release'.(count($plugin->releases) != 1 ? 's' : '').' behind latest version '.$plugin->latestVersion.', released on '.$plugin->latestDate.'.</span>';
						}
					}
                }

                if (!empty($pluginUpdates))
                {
                    $testModel->failTest();
                    $testModel->value = implode(' , ', $pluginUpdates);
                }

                break;

            case 'criticalPluginUpdates':
                $criticalPluginUpdates = [];

                if (!empty($this->_updateModel->plugins))
                {
					foreach ($this->_updateModel->plugins as $plugin)
					{
						if ($plugin->status == PluginUpdateStatus::UpdateAvailable AND !empty($plugin->releases))
						{
                            foreach ($plugin->releases as $release)
            				{
            					if ($release->critical)
            					{
            						$criticalPluginUpdates[] = '<a href="'.$plugin->manualDownloadEndpoint.'" target="_blank">'.$plugin->displayName.'</a> <span class="info">Version '.$release->version.' is a critical update, released on '.$release->date.'.</span>';
            					}
            				}
						}
					}
                }

                if (!empty($criticalPluginUpdates))
                {
                    $testModel->failTest();
                    $testModel->value = implode(' , ', $criticalPluginUpdates);
                }

                break;

            case 'requireEmailVerification':
                if (Craft::$app->getSystemSettings()->getSetting('users', 'requireEmailVerification') === false)
                {
                    $testModel->failTest();
                }

                break;

            case 'devMode':
                if (Craft::$app->getConfig()->getGeneral()->$test)
                {
                    $testModel->pass = $testModel->forceFail ? false : !($testModel->canFail AND $this->_settings->liveMode);
                    $testModel->warning = true;
                }

                break;

            case 'enableCsrfProtection':
            case 'useSecureCookies':
            case 'requireMatchingUserAgentForSession':
            case 'requireUserAgentAndIpForSession':
                if (!Craft::$app->getConfig()->getGeneral()->$test)
                {
                    $testModel->failTest();
                }

                break;

            case 'translationDebugOutput':
            case 'testToEmailAddress':
                if (Craft::$app->getConfig()->getGeneral()->$test)
                {
                    $testModel->failTest();
                }

                break;

            case 'defaultFileMode':
            case 'defaultDirMode':
                $value = Craft::$app->getConfig()->getGeneral()->$test;

                if ($value > $testModel->threshold)
                {
                    $testModel->failTest();
                }

                else
                {
                    $testModel->value = '0'.decoct($value);
                }

                break;

            case 'defaultTokenDuration':
            case 'verificationCodeDuration':
                $value = Craft::$app->getConfig()->getGeneral()->$test;

                $interval = DateTimeHelper::secondsToInterval($value);

                if ($interval->format($testModel->format) > $testModel->threshold)
                {
                    $testModel->failTest();
                }

                else
                {
                    $testModel->value = $value;
                }

                break;

            case 'securityKey4securityKey4':
                $value = Craft::$app->getConfig()->getGeneral()->$test;

                if ($value AND (strlen($value) < 10))
                {
                    $testModel->failTest();
                }

                break;

            case 'cpTrigger':
                $value = Craft::$app->getConfig()->getGeneral()->$test;

                if ($value == 'admin')
                {
                    $testModel->failTest();
                }

                break;

            case 'blowfishHashCost':
                $value = Craft::$app->getConfig()->getGeneral()->$test;

                if ($value < $testModel->threshold)
                {
                    $testModel->failTest();
                }

                else
                {
                    $testModel->value = $value;
                }

                break;

            case 'cooldownDuration':
                $value = Craft::$app->getConfig()->getGeneral()->$test;
                $interval = DateTimeHelper::secondsToInterval($value);

                if ($interval->format($testModel->format) < $testModel->threshold)
                {
                    $testModel->failTest();
                }

                else
                {
                    $testModel->value = $value;
                }

                break;

            case 'invalidLoginWindowDuration':
                $value = Craft::$app->getConfig()->getGeneral()->$test;

                $interval = DateTimeHelper::secondsToInterval($value);

                if ($interval->format($testModel->format) < $testModel->threshold)
                {
                    $testModel->failTest();
                }

                else
                {
                    $testModel->value = $value;
                }

                break;

            case 'maxInvalidLogins':
                $value = Craft::$app->getConfig()->getGeneral()->$test;

                if (!$value)
                {
                    $testModel->failTest();
                }

                else if ($value > $testModel->threshold)
                {
                    $testModel->warning = true;
                }

                else
                {
                    $testModel->value = $value;
                }

                break;

            case 'rememberedUserSessionDuration':
                $value = Craft::$app->getConfig()->getGeneral()->$test;

                if ($value)
                {
                    $interval = DateTimeHelper::secondsToInterval($value);

                    if ($interval->format($testModel->format) > $testModel->threshold)
                    {
                        $testModel->failTest();
                    }

                    else
                    {
                        $testModel->value = $value;
                    }
                }

                break;

            case 'userSessionDuration':
                $value = Craft::$app->getConfig()->getGeneral()->$test;

                if (!$value)
                {
                    $testModel->failTest();
                }

                else
                {
                    $interval = DateTimeHelper::secondsToInterval($value);

                    if ($interval->format($testModel->format) > $testModel->threshold)
                    {
                        $testModel->warning = true;
                    }

                    else
                    {
                        $testModel->value = $value;
                    }
                }

                break;
        }

        return $testModel;
    }
}
