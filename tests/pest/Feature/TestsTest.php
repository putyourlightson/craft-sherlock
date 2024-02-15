<?php

use craft\models\Updates;
use putyourlightson\sherlock\Sherlock;

beforeEach(function() {
    Sherlock::$plugin->settings->highSecurityLevel = true;

    // Set to non-null value so a Guzzle request will not be made.
    Sherlock::$plugin->tests->updates = new Updates();

    // Testing using a local URL fails in Docker.
    Craft::setAlias('@web', 'https://putyourlightson.com');
    Sherlock::$plugin->tests->siteUrl = 'https://putyourlightson.com';
});

test('Passes `https` control panel test', function() {
    $testModel = Sherlock::$plugin->tests->runTest('httpsControlPanel');

    expect($testModel->pass)
        ->toBeTrue();
});

test('Passes `https` front-end test', function() {
    $testModel = Sherlock::$plugin->tests->runTest('httpsFrontEnd');

    expect($testModel->pass)
        ->toBeTrue();
});

test('Passes content security policy header test', function() {
    Sherlock::$plugin->tests->siteUrlResponse['headers']['Content-Security-Policy'] = "default-src 'unsafe-inline'";
    $testModel = Sherlock::$plugin->tests->runTest('contentSecurityPolicy');

    expect($testModel->pass)
        ->toBeTrue()
        ->and($testModel->warning)
        ->toBeTrue();
});

test('Passes content security policy meta tag test', function() {
    Sherlock::$plugin->tests->siteUrlResponse['body'] = '
        <html lang="en">
            <head>
                <title></title>
                <meta http-equiv="Content-Security-Policy" content="
                    default-src \'unsafe-inline\'
                ">
            </head>
        </html>
    ';
    $testModel = Sherlock::$plugin->tests->runTest('contentSecurityPolicy');

    expect($testModel->pass)
        ->toBeTrue()
        ->and($testModel->warning)
        ->toBeTrue();
});

test('Passes web alias in site base URL test', function() {
    $testModel = Sherlock::$plugin->tests->runTest('webAliasInSiteBaseUrl');

    expect($testModel->pass)
        ->toBeTrue();

    Craft::$app->getRequest()->isWebAliasSetDynamically = true;
    Craft::$app->getSites()->getCurrentSite()->setBaseUrl('@web');
    $testModel = Sherlock::$plugin->tests->runTest('webAliasInSiteBaseUrl');

    expect($testModel->pass)
        ->toBeFalse();
});
