<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

/**
 * Sherlock config.php
 *
 * This file exists only as a template for the Sherlock settings.
 * It does nothing on its own.
 *
 * Don't edit this file, instead copy it to 'craft/config' as 'sherlock.php'
 * and make your changes there to override default settings.
 *
 * Once copied to 'craft/config', this file will be multi-environment aware as
 * well, so you can have different settings groups for each environment, just as
 * you do for 'general.php'
 */

return [

    /**
	 * Add tests to disable to the array
	 *
    'disabledTests' => [],
	 */

    /**
	 * Individual test settings
	 *
    'criticalCraftUpdates' => [
        'forceFail' => true,
    ],
    'criticalPluginUpdates' => [
        'forceFail' => true,
    ],
    'pluginVulnerabilities' => [
        'forceFail' => true,
    ],
    'httpsControlPanel' => [
        'forceFail' => true,
    ],
    'httpsFrontEnd' => [
        'canFail' => true,
    ],
    'cors' => [
        'forceFail' => true,
    ],
    'xFrameOptions' => [
        'canFail' => true,
    ],
    'xContentTypeOptions' => [
        'canFail' => true,
    ],
    'xXssProtection' => [
        'canFail' => true,
    ],
    'strictTransportSecurity' => [
        'canFail' => true,
    ],
    'craftFoldersAboveWebRoot' => [
        'canFail' => true,
    ],
    'craftFolderPermissions' => [
        'canFail' => true,
    ],
    'craftFilePermissions' => [
        'canFail' => true,
    ],
    'phpVersion' => [
        'canFail' => true,
        'thresholds' => [
            '5.0' => '2005-09-05',
            '5.1' => '2006-07-24',
            '5.2' => '2011-01-06',
            '5.3' => '2014-07-14',
            '5.4' => '2015-09-03',
            '5.5' => '2016-07-10',
            '5.6' => '2018-12-31',
            '7.0' => '2018-12-03',
            '7.1' => '2019-12-01',
            '7.2' => '2020-11-30',
        ],
    ],
    'craftUpdates' => [
        'canFail' => true,
    ],
    'pluginUpdates' => [
        'canFail' => true,
    ],
    'requireEmailVerification' => [
        'canFail' => true,
    ],
    'devMode' => [
        'canFail' => true,
    ],
    'translationDebugOutput' => [
        'canFail' => true,
    ],
    'defaultFileMode' => [
        'canFail' => true,
        'threshold' => 0664,
    ],
    'defaultDirMode' => [
        'canFail' => true,
        'threshold' => 0775,
    ],
    'defaultTokenDuration' => [
        'canFail' => true,
        'threshold' => 1,
        'format' => '%d',
    ],
    'enableCsrfProtection' => [
        'canFail' => true,
    ],
    'useSecureCookies' => [
        'canFail' => true,
    ],
    'securityKey' => [
        'canFail' => true,
    ],
    'cpTrigger' => [],
    'blowfishHashCost' => [
        'threshold' => 13,
    ],
    'cooldownDuration' => [
        'threshold' => 5,
        'format' => '%i',
    ],
    'invalidLoginWindowDuration' => [
        'threshold' => 1,
        'format' => '%h',
    ],
    'maxInvalidLogins' => [
        'canFail' => true,
        'threshold' => 5,
    ],
    'rememberedUserSessionDuration' => [
        'threshold' => 14,
        'format' => '%d',
    ],
    'requireMatchingUserAgentForSession' => [],
    'requireUserAgentAndIpForSession' => [],
    'testToEmailAddress' => [
        'canFail' => true,
    ],
    'userSessionDuration' => [
        'canFail' => true,
        'threshold' => 1,
        'format' => '%h',
    ],
    'verificationCodeDuration' => [
        'canFail' => true,
        'threshold' => 1,
        'format' => '%d',
    ],
     */
];
