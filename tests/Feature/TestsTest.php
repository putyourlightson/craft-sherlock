<?php

use craft\models\Updates;
use putyourlightson\sherlock\services\TestsService;
use putyourlightson\sherlock\Sherlock;

class SherlockTestsServiceWithPhpVersion extends TestsService
{
    public string $version;

    protected function getPhpVersion(): string
    {
        return $this->version;
    }
}

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

test('Checks PHP security update versions', function(string $version, bool $pass) {
    Sherlock::$plugin->settings->highSecurityLevel = false;
    $service = new SherlockTestsServiceWithPhpVersion([
        'version' => $version,
        'client' => new \GuzzleHttp\Client(),
    ]);
    $testModel = $service->runTest('phpSecurityUpdates');

    expect($testModel->pass)
        ->toBe($pass)
        ->and($testModel->warning)
        ->toBe(!$pass)
        ->and($testModel->value)
        ->toContain($version);
})->with([
    '8.2 affected' => ['8.2.34', false],
    '8.2 fixed' => ['8.2.35', true],
    '8.2 later' => ['8.2.36', true],
    '8.3 affected' => ['8.3.35', false],
    '8.3 fixed' => ['8.3.36', true],
    '8.3 later' => ['8.3.37', true],
    '8.4 affected' => ['8.4.26', false],
    '8.4 fixed' => ['8.4.27', true],
    '8.4 later' => ['8.4.28', true],
    '8.5 affected' => ['8.5.11', false],
    '8.5 fixed' => ['8.5.12', true],
    '8.5 later' => ['8.5.13', true],
    'unsupported branch' => ['8.1.34', false],
    'unknown branch' => ['8.6.0', false],
    'prerelease' => ['8.5.12RC1', false],
    'vendor package needs verification' => ['8.3.6-0ubuntu0.24.04.13', false],
]);

test('Checks PHP support deadlines', function(string $version, array $thresholds, bool $pass) {
    $settings = Sherlock::$plugin->settings;
    $originalSettings = $settings->phpVersion;
    $settings->phpVersion['thresholds'] = $thresholds;

    try {
        $service = new SherlockTestsServiceWithPhpVersion([
            'version' => $version,
            'client' => new \GuzzleHttp\Client(),
        ]);
        $testModel = $service->runTest('phpVersion');

        expect($testModel->pass)
            ->toBe($pass)
            ->and($testModel->warning)
            ->toBe(!$pass);
    } finally {
        $settings->phpVersion = $originalSettings;
    }
})->with([
    'supported 8.4' => ['8.4.27', ['8.4' => '2999-01-01'], true],
    'supported 8.5' => ['8.5.12', ['8.5' => '2999-01-01'], true],
    'expired branch' => ['8.2.35', ['8.2' => '2000-01-01'], false],
    'unknown branch' => ['8.6.0', ['8.5' => '2999-01-01'], false],
    'two-digit minor version' => ['8.10.0', ['8.10' => '2999-01-01'], true],
]);

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

test('Checks effective CSP script restrictions', function(array $headers, string $body, bool $pass) {
    $service = new TestsService([
        'client' => new \GuzzleHttp\Client(),
        'siteUrlResponse' => ['headers' => $headers, 'body' => $body],
    ]);
    $testModel = $service->runTest('contentSecurityPolicy');

    expect($testModel->pass)->toBe($pass)
        ->and($testModel->warning)->toBe(!$pass);
})->with([
    'missing policy' => [[], '', false],
    'images only' => [['Content-Security-Policy' => "img-src 'self'"], '', false],
    'default source' => [['Content-Security-Policy' => "default-src 'self'"], '', true],
    'no scripts' => [['Content-Security-Policy' => "script-src 'none'"], '', true],
    'empty script list' => [['Content-Security-Policy' => 'script-src'], '', true],
    'unsafe inline scripts' => [['Content-Security-Policy' => "default-src 'unsafe-inline'"], '', false],
    'case insensitive unsafe inline' => [['Content-Security-Policy' => "SCRIPT-SRC 'UNSAFE-INLINE'"], '', false],
    'unsafe eval' => [['Content-Security-Policy' => "script-src 'self' 'unsafe-eval'"], '', false],
    'case insensitive unsafe eval' => [['Content-Security-Policy' => "script-src 'self' 'UNSAFE-EVAL'"], '', false],
    'unsafe styles only' => [['Content-Security-Policy' => "script-src 'self'; style-src 'unsafe-inline'"], '', true],
    'unrestricted script override' => [['Content-Security-Policy' => "default-src 'self'; script-src *"], '', false],
    'broad scheme' => [['Content-Security-Policy' => 'script-src https:'], '', false],
    'broad data source' => [['Content-Security-Policy' => 'script-src data:'], '', false],
    'wildcard host' => [['Content-Security-Policy' => 'script-src https://*'], '', false],
    'element override' => [['Content-Security-Policy' => "script-src 'self'; script-src-elem *"], '', false],
    'attribute override' => [['Content-Security-Policy' => "script-src 'self'; script-src-attr 'unsafe-inline'"], '', false],
    'element restriction only' => [['Content-Security-Policy' => "script-src-elem 'self'"], '', false],
    'duplicate directive keeps first' => [['Content-Security-Policy' => "script-src *; script-src 'none'"], '', false],
    'nonce overrides unsafe-inline' => [['Content-Security-Policy' => "script-src 'nonce-YWJjZA==' 'unsafe-inline'"], '', true],
    'strict dynamic ignores broad sources' => [['Content-Security-Policy' => "script-src 'nonce-YWJjZA==' 'strict-dynamic' https: 'unsafe-inline'"], '', true],
    'combined headers' => [['Content-Security-Policy' => ["script-src-elem 'self'; script-src-attr 'none'", "script-src 'self' 'unsafe-inline'"]], '', true],
    'combined comma-separated policies' => [['Content-Security-Policy' => "script-src * 'unsafe-inline', script-src 'self'"], '', true],
    'header names are case insensitive' => [['CONTENT-SECURITY-POLICY' => "script-src 'self'"], '', true],
    'report-only does not enforce' => [['Content-Security-Policy-Report-Only' => "script-src 'none'"], '', false],
    'meta policy' => [[], '<html><head><meta content="script-src &apos;self&apos;" http-equiv="Content-Security-Policy"></head></html>', true],
    'meta combines with header' => [['Content-Security-Policy' => 'img-src https:'], '<html><head><meta http-equiv="Content-Security-Policy" content="script-src &apos;self&apos;"></head></html>', true],
    'meta in body is ignored' => [[], '<html><head></head><body><meta http-equiv="Content-Security-Policy" content="script-src &apos;none&apos;"></body></html>', false],
    'commented meta is ignored' => [[], '<html><head><!-- <meta http-equiv="Content-Security-Policy" content="script-src &apos;none&apos;"> --></head></html>', false],
]);

test('Checks CSP framing protection', function(array $headers, string $body, bool $pass) {
    $service = new TestsService([
        'client' => new \GuzzleHttp\Client(),
        'siteUrlResponse' => ['headers' => $headers, 'body' => $body],
    ]);
    $testModel = $service->runTest('xFrameOptions');

    expect($testModel->pass)->toBe($pass)
        ->and($testModel->warning)->toBe(!$pass);
})->with([
    'no protection' => [[], '', false],
    'deny framing' => [['X-Frame-Options' => 'DENY'], '', true],
    'same origin framing' => [['X-Frame-Options' => 'sameorigin'], '', true],
    'CSP none' => [['Content-Security-Policy' => "frame-ancestors 'none'"], '', true],
    'CSP self' => [['Content-Security-Policy' => "frame-ancestors 'self'"], '', true],
    'CSP trusted origin' => [['Content-Security-Policy' => "frame-ancestors 'self' https://partner.example"], '', true],
    'CSP wildcard' => [['Content-Security-Policy' => 'frame-ancestors *'], '', false],
    'CSP broad scheme' => [['Content-Security-Policy' => 'frame-ancestors https:'], '', false],
    'CSP overrides XFO' => [['Content-Security-Policy' => 'frame-ancestors *', 'X-Frame-Options' => 'DENY'], '', false],
    'default-src does not protect framing' => [['Content-Security-Policy' => "default-src 'none'"], '', false],
    'report-only does not protect framing' => [['Content-Security-Policy-Report-Only' => "frame-ancestors 'none'"], '', false],
    'meta does not protect framing' => [[], '<html><head><meta http-equiv="Content-Security-Policy" content="frame-ancestors &apos;none&apos;"></head></html>', false],
    'combined framing policies' => [['Content-Security-Policy' => ['frame-ancestors *', "frame-ancestors 'self'"]], '', true],
    'duplicate directive keeps first' => [['Content-Security-Policy' => "frame-ancestors *; frame-ancestors 'none'"], '', false],
]);

test('Emits report-only CSP as a header when meta delivery is selected', function() {
    $settings = Sherlock::$plugin->settings;
    $originalSettings = $settings->contentSecurityPolicySettings;
    $settings->contentSecurityPolicySettings = [
        'enabled' => true,
        'enforce' => false,
        'header' => false,
        'directives' => [[true, 'default-src', "'self'"]],
    ];
    $view = Craft::$app->getView();
    $originalMetaTags = $view->metaTags;
    $headers = Craft::$app->getResponse()->getHeaders();
    $originalHeaders = $headers->toArray();

    try {
        $view->metaTags = [];
        Sherlock::$plugin->security->applyContentSecurityPolicy();

        expect($headers->get('Content-Security-Policy-Report-Only'))->toBe("default-src 'self';")
            ->and($view->metaTags)->toBe([]);
    } finally {
        $settings->contentSecurityPolicySettings = $originalSettings;
        $view->metaTags = $originalMetaTags;
        $headers->fromArray($originalHeaders);
    }
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
