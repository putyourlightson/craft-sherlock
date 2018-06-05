<?php

return array(

    /**
	 * Add tests to disable to the array
	 */
    'disabledTests' => array(),

    /**
	 * Individual test settings
	 */
    'criticalCraftUpdates' => array(
        'forceFail' => true,
    ),
    'criticalPluginUpdates' => array(
        'forceFail' => true,
    ),
    'pluginVulnerabilities' => array(
        'forceFail' => true,
    ),
    'httpsControlPanel' => array(
        'forceFail' => true,
    ),
    'httpsFrontEnd' => array(
        'canFail' => true,
    ),
    'cors' => array(
        'forceFail' => true,
    ),
    'xFrameOptions' => array(
        'canFail' => true,
    ),
    'xContentTypeOptions' => array(
        'canFail' => true,
    ),
    'xXssProtection' => array(
        'canFail' => true,
    ),
    'strictTransportSecurity' => array(
        'canFail' => true,
    ),
    'purifyHtml' => array(
        'canFail' => true,
    ),
    'craftFoldersAboveWebRoot' => array(
        'canFail' => true,
    ),
    'craftFolderPermissions' => array(
        'canFail' => true,
    ),
    'craftFilePermissions' => array(
        'canFail' => true,
    ),
    'phpVersion' => array(
        'canFail' => true,
        'thresholds' => array(
            '5.0' => '2005-09-05',
            '5.1' => '2006-07-24',
            '5.2' => '2011-01-06',
            '5.3' => '2014-07-14',
            '5.4' => '2015-09-03',
            '5.5' => '2016-07-10',
            '5.6' => '2018-12-31',
            '7.0' => '2018-12-03',
        ),
    ),
    'craftUpdates' => array(
        'canFail' => true,
    ),
    'pluginUpdates' => array(
        'canFail' => true,
    ),
    'requireEmailVerification' => array(
        'canFail' => true,
    ),
    'allowPublicRegistration' => array(),
    'devMode' => array(
        'canFail' => true,
    ),
    'translationDebugOutput' => array(
        'canFail' => true,
    ),
    'defaultFilePermissions' => array(
        'canFail' => true,
        'threshold' => 0664,
    ),
    'defaultFolderPermissions' => array(
        'canFail' => true,
        'threshold' => 0775,
    ),
    'defaultTokenDuration' => array(
        'canFail' => true,
        'threshold' => 1,
        'format' => '%d',
    ),
    'enableCsrfProtection' => array(
        'canFail' => true,
    ),
    'useSecureCookies' => array(
        'canFail' => true,
    ),
    'validationKey' => array(
        'canFail' => true,
    ),
    'cpTrigger' => array(),
    'blowfishHashCost' => array(
        'threshold' => 13,
    ),
    'cooldownDuration' => array(
        'threshold' => 5,
        'format' => '%i',
    ),
    'invalidLoginWindowDuration' => array(
        'threshold' => 1,
        'format' => '%h',
    ),
    'maxInvalidLogins' => array(
        'canFail' => true,
        'threshold' => 5,
    ),
    'rememberedUserSessionDuration' => array(
        'threshold' => 14,
        'format' => '%d',
    ),
    'requireMatchingUserAgentForSession' => array(),
    'requireUserAgentAndIpForSession' => array(),
    'testToEmailAddress' => array(
        'canFail' => true,
    ),
    'userSessionDuration' => array(
        'canFail' => true,
        'threshold' => 1,
        'format' => '%h',
    ),
    'verificationCodeDuration' => array(
        'canFail' => true,
        'threshold' => 1,
        'format' => '%d',
    ),
);
