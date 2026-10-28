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

test('Validates the effective referrer policy', function(string|array $value, bool $pass) {
    Sherlock::$plugin->tests->client = new \GuzzleHttp\Client();
    Sherlock::$plugin->tests->siteUrlResponse['headers']['Referrer-Policy'] = $value;
    $testModel = Sherlock::$plugin->tests->runTest('referrerPolicy');

    expect($testModel->pass)
        ->toBe($pass)
        ->and($testModel->warning)
        ->toBe(!$pass);
})->with([
    'recommended policy' => ['strict-origin-when-cross-origin', true],
    'no referrer' => ['no-referrer', true],
    'same origin' => ['same-origin', true],
    'strict origin' => ['strict-origin', true],
    'missing policy' => ['', false],
    'invalid policy' => ['invalid', false],
    'old default' => ['no-referrer-when-downgrade', false],
    'unsafe policy' => ['unsafe-url', false],
    'origin on downgrade' => ['origin', false],
    'origin on cross origin and downgrade' => ['origin-when-cross-origin', false],
    'safe fallback' => ['no-referrer-when-downgrade, strict-origin-when-cross-origin', true],
    'unsafe fallback' => ['no-referrer, unsafe-url', false],
    'unknown fallback' => ['no-referrer, invalid', true],
    'whitespace' => ["\t strict-origin-when-cross-origin ", true],
    'multiple safe headers' => [['unsafe-url', 'no-referrer'], true],
    'multiple unsafe headers' => [['no-referrer', 'unsafe-url'], false],
]);

test('Validates the HSTS max-age', function(string|array $value, bool $pass) {
    Sherlock::$plugin->tests->client = new \GuzzleHttp\Client();
    Sherlock::$plugin->tests->siteUrlResponse['headers']['Strict-Transport-Security'] = $value;
    $testModel = Sherlock::$plugin->tests->runTest('strictTransportSecurity');

    expect($testModel->pass)
        ->toBe($pass)
        ->and($testModel->warning)
        ->toBe(!$pass);
})->with([
    'one year' => ['max-age=31536000', true],
    'longer duration' => ['max-age=63072000', true],
    'below threshold' => ['max-age=31535999', false],
    'short duration with subdomains' => ['max-age=60; includeSubDomains', false],
    'one year with subdomains' => ['max-age=31536000; includeSubDomains', true],
    'max-age after other directives' => ['includeSubDomains; max-age=31536000; preload', true],
    'case and whitespace' => [" MAX-AGE = 31536000 \t; includeSubDomains", true],
    'quoted duration' => ['max-age="31536000"', true],
    'leading zeros' => ['max-age=00031536000', true],
    'large duration' => ['max-age=999999999999999999999999', true],
    'missing header' => ['', false],
    'missing max-age' => ['includeSubDomains', false],
    'disabled HSTS' => ['max-age=0; includeSubDomains', false],
    'negative duration' => ['max-age=-1', false],
    'decimal duration' => ['max-age=31536000.5', false],
    'exponential duration' => ['max-age=4e7', false],
    'nonnumeric duration' => ['max-age=invalid', false],
    'trailing text' => ['max-age=31536000invalid', false],
    'missing value' => ['max-age=', false],
    'missing equals' => ['max-age', false],
    'unclosed quotes' => ['max-age="31536000', false],
    'duplicate max-age' => ['max-age=31536000; max-age=31536000', false],
    'malformed duplicate max-age' => ['max-age=invalid; max-age=31536000', false],
    'first header wins' => [['max-age=0', 'max-age=31536000'], false],
]);

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
