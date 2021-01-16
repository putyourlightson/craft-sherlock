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
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * Test all tests together, otherwise each test will result in new Guzzle calls.
     */
    public function testAllTests()
    {
        Craft::$app->getSites()->getCurrentSite()->setBaseUrl('http://craft3/');

        $testModel = Sherlock::$plugin->tests->runTest('httpsControlPanel');
        $this->assertFalse($testModel->pass);

        $testModel = Sherlock::$plugin->tests->runTest('httpsFrontEnd');
        $this->assertFalse($testModel->pass);

        $testModel = Sherlock::$plugin->tests->runTest('webAliasInSiteBaseUrl');
        $this->assertTrue($testModel->pass);
        Craft::$app->getRequest()->isWebAliasSetDynamically = true;
        Craft::$app->getSites()->getCurrentSite()->setBaseUrl('@web');
        $testModel = Sherlock::$plugin->tests->runTest('webAliasInSiteBaseUrl');
        $this->assertFalse($testModel->pass);
    }
}
