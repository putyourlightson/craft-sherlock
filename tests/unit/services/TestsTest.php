<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprigtests\unit\services;

use Codeception\Test\Unit;
use Craft;
use craft\models\Updates;
use putyourlightson\sherlock\Sherlock;
use UnitTester;

/**
 * @since 3.0.0
 */

class TestsTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before()
    {
        Sherlock::$plugin->settings->highSecurityLevel = true;

        // Set to non-null value so a Guzzle request will not be made
        Sherlock::$plugin->tests->updates = new Updates();

        Sherlock::$plugin->tests->siteUrl = 'https://putyourlightson.com';
    }

    public function testHttpsControlPanel()
    {
        $testModel = Sherlock::$plugin->tests->runTest('httpsControlPanel');
        $this->assertTrue($testModel->pass);
    }

    public function testHttpsFrontEnd()
    {
        $testModel = Sherlock::$plugin->tests->runTest('httpsFrontEnd');
        $this->assertTrue($testModel->pass);
    }

    public function testContentSecurityPolicyHeader()
    {
        Sherlock::$plugin->tests->siteUrlResponse['headers']['Content-Security-Policy'] = "default-src 'unsafe-inline'";
        $testModel = Sherlock::$plugin->tests->runTest('contentSecurityPolicy');
        $this->assertTrue($testModel->pass);
        $this->assertTrue($testModel->warning);
    }

    public function testContentSecurityPolicyMetaTag()
    {
        Sherlock::$plugin->tests->siteUrlResponse['body'] = '
            <html>
                <head>
                    <meta http-equiv="Content-Security-Policy" content="
                        default-src \'unsafe-inline\'
                    ">
                </head>
            </html>
        ';
        $testModel = Sherlock::$plugin->tests->runTest('contentSecurityPolicy');
        $this->assertTrue($testModel->pass);
        $this->assertTrue($testModel->warning);
    }

    public function testWebAliasInSiteBaseUrl()
    {
        $testModel = Sherlock::$plugin->tests->runTest('webAliasInSiteBaseUrl');
        $this->assertTrue($testModel->pass);

        Craft::$app->getRequest()->isWebAliasSetDynamically = true;
        Craft::$app->getSites()->getCurrentSite()->setBaseUrl('@web');
        $testModel = Sherlock::$plugin->tests->runTest('webAliasInSiteBaseUrl');
        $this->assertFalse($testModel->pass);
    }
}
