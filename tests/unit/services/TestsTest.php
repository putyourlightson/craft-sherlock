<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\sprigtests\unit\services;

use Codeception\Test\Unit;
use Craft;
use putyourlightson\sherlock\Sherlock;
use UnitTester;

/**
 * @since 3.0.0
 */

class TestsTest extends Unit
{
    private $_beforeRunTests = false;

    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before()
    {
        parent::_before();

        Craft::$app->getSites()->getCurrentSite()->setBaseUrl('http://craft3/');
    }

    public function testHttpsControlPanel()
    {
        $testModel = Sherlock::$plugin->tests->runTest('httpsControlPanel');
        $this->assertFalse($testModel->pass);
    }

    public function testHttpsFrontEnd()
    {
        $testModel = Sherlock::$plugin->tests->runTest('httpsFrontEnd');
        $this->assertFalse($testModel->pass);
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
