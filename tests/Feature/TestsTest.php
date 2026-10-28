<?php

use craft\models\Updates;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
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

test('Checks HTTPS enforcement without accepting unrelated request failures', function(array $responses, bool $pass, string $message, string $test) {
    $queue = [];
    foreach ($responses as $response) {
        if (is_int($response)) {
            $queue[] = new Response($response);
        } elseif (is_int($response[0])) {
            $queue[] = new Response($response[0], ['Location' => $response[1]]);
        } else {
            $request = new Request('GET', $response[2] . '://example.test/');
            $context = ['errno' => $response[1]];
            $queue[] = $response[0] === 'connect'
                ? new ConnectException('Connection failed', $request, null, $context)
                : new RequestException('Request failed', $request, null, null, $context);
        }
    }
    $mock = new MockHandler($queue);
    $service = new TestsService([
        'siteUrl' => 'https://example.test/',
        'client' => new Client(['handler' => HandlerStack::create($mock)]),
    ]);
    $testModel = $service->runTest($test);

    expect($testModel->pass)->toBe($pass)
        ->and($testModel->warning)->toBe(!$pass)
        ->and($testModel->value)->toContain($message);
})->with([
    'HTTP success without redirect' => [[200], false, 'HTTP responded'],
    'HTTP forbidden' => [[403], false, 'HTTP responded'],
    'HTTP not found' => [[404], false, 'HTTP responded'],
    'HTTP server error' => [[500], false, 'HTTP responded'],
    'HTTPS redirect' => [[[301, 'https://example.test/'], 200], true, 'HTTP redirects to HTTPS'],
    'HTTPS redirect to error page' => [[[301, 'https://example.test/'], 500], true, 'HTTP redirects to HTTPS'],
    'HTTP redirect stays insecure' => [[[302, 'http://example.test/other'], 200], false, 'HTTP responded'],
    'HTTPS redirect downgrades' => [[[301, 'https://example.test/'], [302, 'http://example.test/'], 200], false, 'HTTP responded'],
    'unavailable HTTP and working HTTPS' => [[['connect', 7, 'http'], 200], true, 'HTTP connection is unavailable; HTTPS response verified'],
    'unavailable HTTP and HTTPS error page' => [[['connect', 7, 'http'], 404], true, 'HTTP connection is unavailable; HTTPS response verified'],
    'unavailable HTTP and unavailable HTTPS' => [[['connect', 7, 'http'], ['connect', 7, 'https']], false, 'HTTPS could not be verified'],
    'unavailable HTTP and HTTPS downgrade' => [[['connect', 7, 'http'], [302, 'http://example.test/'], 200], false, 'HTTPS could not be verified'],
    'DNS failure' => [[['connect', 6, 'http']], false, 'Connection failed'],
    'timeout' => [[['connect', 28, 'http']], false, 'Connection failed'],
    'unclassified connection error' => [[['connect', 0, 'http']], false, 'Connection failed'],
    'TLS verification failure after redirect' => [[[301, 'https://example.test/'], ['request', 60, 'https']], false, 'Request failed'],
    'HTTPS connection fails after redirect' => [[[301, 'https://example.test/'], ['connect', 7, 'https']], false, 'Connection failed'],
    'HTTP connection fails after HTTP redirect' => [[[301, 'http://example.test/other'], ['connect', 7, 'http']], false, 'Connection failed'],
    'redirect loop' => [array_fill(0, 7, [301, 'http://example.test/loop']), false, 'Request failed'],
])->with(['httpsControlPanel', 'httpsFrontEnd']);

test('Checks deferred passwords in the registration workflow', function(bool $deferred, array $userSettings, bool $warning, bool $highSecurityLevel) {
    $generalConfig = Craft::$app->getConfig()->getGeneral();
    $originalDeferred = $generalConfig->deferPublicRegistrationPassword;
    $originalProjectConfig = Craft::$app->getProjectConfig();
    $projectConfig = $this->createMock(\craft\services\ProjectConfig::class);
    $projectConfig->method('get')->with('users')->willReturn($userSettings);
    $generalConfig->deferPublicRegistrationPassword = $deferred;
    Craft::$app->set('projectConfig', $projectConfig);
    Sherlock::$plugin->settings->highSecurityLevel = $highSecurityLevel;

    try {
        $service = new TestsService(['client' => new \GuzzleHttp\Client()]);
        $testModel = $service->runTest('deferPublicRegistrationPassword');

        expect($testModel->pass)->toBeTrue()
            ->and($testModel->warning)->toBe($warning)
            ->and($testModel->value)->not->toBeEmpty();
    } finally {
        $generalConfig->deferPublicRegistrationPassword = $originalDeferred;
        Craft::$app->set('projectConfig', $originalProjectConfig);
    }
})->with([
    'password required during registration' => [false, ['allowPublicRegistration' => true, 'requireEmailVerification' => false], false],
    'public registration disabled' => [true, ['allowPublicRegistration' => false, 'requireEmailVerification' => false], false],
    'email verification enabled' => [true, ['allowPublicRegistration' => true, 'requireEmailVerification' => true], false],
    'email verification defaults to enabled' => [true, ['allowPublicRegistration' => true], false],
    'moderator activation enabled' => [true, ['allowPublicRegistration' => true, 'requireEmailVerification' => false, 'deactivateByDefault' => true], false],
    'public registration needs review' => [true, ['allowPublicRegistration' => true, 'requireEmailVerification' => false], true],
    'explicitly unmoderated registration needs review' => [true, ['allowPublicRegistration' => true, 'requireEmailVerification' => false, 'deactivateByDefault' => false], true],
    'default user settings disable public registration' => [true, [], false],
])->with([false, true]);

test('Checks login lockout and session durations', function(string $test, int|string|bool $value, bool $warning) {
    $generalConfig = Craft::$app->getConfig()->getGeneral();
    $originalValue = $generalConfig->{$test};
    $generalConfig->{$test} = $value;

    try {
        $service = new TestsService(['client' => new Client()]);
        $testModel = $service->runTest($test);

        expect($testModel->pass)->toBeTrue()
            ->and($testModel->warning)->toBe($warning);
    } finally {
        $generalConfig->{$test} = $originalValue;
    }
})->with([
    'indefinite lockout' => ['cooldownDuration', 0, false],
    'zero interval lockout' => ['cooldownDuration', 'PT0S', false],
    'legacy indefinite lockout' => ['cooldownDuration', false, false],
    'short lockout' => ['cooldownDuration', 299, true],
    'five minute lockout' => ['cooldownDuration', 300, false],
    'five minute interval' => ['cooldownDuration', 'PT5M', false],
    'negative lockout' => ['cooldownDuration', -1, true],
    'zero login window still warns' => ['invalidLoginWindowDuration', 0, true],
    'remember me disabled' => ['rememberedUserSessionDuration', 0, false],
    '14 day session' => ['rememberedUserSessionDuration', 1209600, false],
    '14 day interval' => ['rememberedUserSessionDuration', 'P14D', false],
    'session just over 14 days' => ['rememberedUserSessionDuration', 1209601, true],
    'old incorrect session threshold' => ['rememberedUserSessionDuration', 12096004, true],
]);

test('Checks secure cookie HTTPS detection', function(bool|string $value, ?bool $https, bool $pass) {
    $generalConfig = Craft::$app->getConfig()->getGeneral();
    $originalValue = $generalConfig->useSecureCookies;
    $originalRequest = Craft::$app->getRequest();
    $request = $https === null
        ? $this->createMock(\craft\console\Request::class)
        : $this->createMock(\craft\web\Request::class);
    if ($https !== null) {
        $request->method('getIsSecureConnection')->willReturn($https);
    }
    $generalConfig->useSecureCookies = $value;
    Craft::$app->set('request', $request);

    try {
        $service = new TestsService(['client' => new Client()]);
        $testModel = $service->runTest('useSecureCookies');

        expect($testModel->pass)->toBe($pass)
            ->and($testModel->warning)->toBe(!$pass)
            ->and($generalConfig->useSecureCookies)->toBe($value);
    } finally {
        $generalConfig->useSecureCookies = $originalValue;
        Craft::$app->set('request', $originalRequest);
    }
})->with([
    'explicit true on HTTPS' => [true, true, true],
    'explicit true on HTTP' => [true, false, true],
    'explicit true in console' => [true, null, true],
    'disabled on HTTPS' => [false, true, false],
    'auto detects HTTPS' => ['auto', true, true],
    'auto detects HTTP' => ['auto', false, false],
    'auto cannot verify in console' => ['auto', null, false],
    'unsupported truthy value' => ['invalid', true, false],
]);

test('Checks PHP security update versions', function(string $version, bool $pass) {
    Sherlock::$plugin->settings->highSecurityLevel = false;
    $service = new SherlockTestsServiceWithPhpVersion([
        'version' => $version,
        'client' => new Client(),
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
            'client' => new Client(),
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

test('Reports CORS access for review without failing the scan', function(array $headers, bool $warning, bool $highSecurityLevel) {
    Sherlock::$plugin->settings->highSecurityLevel = $highSecurityLevel;
    $service = new TestsService([
        'client' => new Client(),
        'siteUrlResponse' => ['headers' => $headers],
    ]);
    $testModel = $service->runTest('cors');

    expect($testModel->pass)->toBeTrue()
        ->and($testModel->warning)->toBe($warning);
})->with([
    'no CORS header' => [[], false],
    'public wildcard' => [['Access-Control-Allow-Origin' => '*'], true],
    'explicit origin' => [['Access-Control-Allow-Origin' => 'https://partner.example'], true],
    'null origin' => [['Access-Control-Allow-Origin' => 'null'], true],
    'wildcard with credentials' => [['Access-Control-Allow-Origin' => '*', 'Access-Control-Allow-Credentials' => 'true'], true],
    'lowercase header' => [['access-control-allow-origin' => '*'], true],
])->with([false, true]);

test('Validates the effective referrer policy', function(string|array $value, bool $pass) {
    Sherlock::$plugin->tests->client = new Client();
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
    Sherlock::$plugin->tests->client = new Client();
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
        'client' => new Client(),
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
        'client' => new Client(),
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
