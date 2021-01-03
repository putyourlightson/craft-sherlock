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
    '*' => [
        // Whether the site is live – if enabled then control panel alerts will be shown to all users that have access to the Sherlock plugin and notification emails will be sent if the site scan status changes from pass to fail and if any critical updates are detected.
        //'liveMode' => false,

        // Enter the email addresses (separated by commas) that should be notified of security issues.
        //'notificationEmailAddresses' => '',

        // Whether Sherlock should be extra critical of security issues and the resulting warnings.
        //'highSecurityLevel' => false,

        // Protects your site by setting HTTP response headers that provide added security.
        //'headerProtectionSettings' => [
        //    'enabled' => true,
        //    'headers' => [
        //        [true, 'Strict-Transport-Security', 'max-age=31536000'],
        //        [true, 'X-Content-Type-Options', 'nosniff'],
        //        [true, 'X-Frame-Options', 'SAMEORIGIN'],
        //        [true, 'X-Xss-Protection', '1; mode=block'],
        //    ]
        //],

        // Enables a content security policy on the front-end of your site.
        // https://content-security-policy.com/
        //'contentSecurityPolicySettings' => [
        //    'enabled' => false,
        //    'reportOnly' => false,
        //    'header' => false,
        //    'directives' => [[]],
        //],

        // Restrict access to the control panel to the following IP addresses (logged in admins always have access).
        //'restrictControlPanelIpAddresses' =>  = [],

        // Restrict access to the front-end to the following IP addresses (logged in admins always have access).
        //'restrictFrontEndIpAddresses' =>  = [],

        // Add tests to disable to the array.
        //'disabledTests' => [],

        /**
         * Individual test settings.
         *
        'criticalCraftUpdates' => [
            'forceFail' => true,
        ],
        'criticalPluginUpdates' => [
            'forceFail' => true,
        ],
        'httpsControlPanel' => [
            'forceFail' => true,
        ],
        'httpsFrontEnd' => [
            'canFail' => true,
        ],
        'contentSecurityPolicy' => [
            'canFail' => true,
        ],
        'cors' => [
            'forceFail' => true,
        ],
        'expectCT' => [
            'canFail' => true,
        ],
        'permissionsPolicy' => [],
        'referrerPolicy' => [
            'canFail' => true,
        ],
        'strictTransportSecurity' => [
            'canFail' => true,
        ],
        'xContentTypeOptions' => [
            'canFail' => true,
        ],
        'xFrameOptions' => [
            'canFail' => true,
        ],
        'xXssProtection' => [],
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
                '7.3' => '2021-12-06',
                '7.4' => '2022-11-28',
                '8.0' => '2023-11-26',
            ],
        ],
        'craftUpdates' => [],
        'pluginUpdates' => [],
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
            'threshold' => 86400, // 1 day
        ],
        'enableCsrfProtection' => [
            'canFail' => true,
        ],
        'useSecureCookies' => [
            'canFail' => true,
        ],
        'cpTrigger' => [],
        'blowfishHashCost' => [
            'threshold' => 13,
        ],
        'cooldownDuration' => [
            'threshold' => 300, // 5 minutes
        ],
        'deferPublicRegistrationPassword' => [],
        'elevatedSessionDuration' => [
            'canFail' => true,
            'threshold' => 300, // 5 minutes
        ],
        'invalidLoginWindowDuration' => [
            'threshold' => 3600, // 1 hour
        ],
        'maxInvalidLogins' => [
            'canFail' => true,
            'threshold' => 5,
        ],
        'preventUserEnumeration' => [
            'canFail' => true,
        ],
        'sanitizeSvgUploads' => [
            'canFail' => true,
        ],
        'rememberedUserSessionDuration' => [
            'threshold' => 1209600, // 14 days
        ],
        'requireMatchingUserAgentForSession' => [],
        'requireUserAgentAndIpForSession' => [],
        'testToEmailAddress' => [
            'canFail' => true,
        ],
        'userSessionDuration' => [
            'canFail' => true,
            'threshold' => 3600, // 1 hour
        ],
        'verificationCodeDuration' => [
            'canFail' => true,
            'threshold' => 86400, // 1 day
        ],
        'preventUserEnumeration' => [
            'canFail' => true,
        ],
        'sanitizeSvgUploads' => [
            'canFail' => true,
        ],
         */
    ],
];
