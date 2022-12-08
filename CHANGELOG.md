# Changelog

## 4.2.2 - Unreleased
### Changed
- Updated the supported PHP version test to include 8.2.

## 4.2.1 - 2022-12-07
### Changed
- The Control Panel test no longer results in an error if the web server blocks insecure requests.
- Updated the supported PHP version test to list the most recent 8.x versions.
- Changed the Rollbar integration to reference the config service environment instead of the environment constant.

### Fixed
- Fixed a broken changelog link in the Craft updates test.

## 4.2.0 - 2022-09-01
### Changed
- Changed the HTTP error code from `503` to `403` when access to the site is denied ([#36](https://github.com/putyourlightson/craft-sherlock/issues/36)).

### Removed
- Removed the test for `Expect-CT` headers, since it became obsolete in June 2021 ([source](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Expect-CT#browser_compatibility)).

## 4.1.0 - 2022-06-03
### Added
- Added a test for the `Send Powered By Header` config setting.

### Changed
- Improved the `Strict-Transport-Security Header` test.

## 4.0.1 - 2022-05-09
### Changed
- Element queries are now deferred, avoiding potential issues with element queries being executed before Craft has fully initialised.

## 4.0.0 - 2022-05-04
### Added
- Added compatibility with Craft 4.
- Added `Referrer-Policy` to default headers.

## 3.1.4 - 2022-01-13
### Fixed
- Fixed a bug that was throwing an exception on the settings page in versions of Craft less than 3.6.0 ([#33](https://github.com/putyourlightson/craft-sherlock/issues/33)).

## 3.1.3 - 2021-06-08
### Fixed
- Fixed a bug in which the plugin migration that adds the site ID column could be ignored in rare cases ([#31](https://github.com/putyourlightson/craft-sherlock/issues/31)).

## 3.1.2 - 2021-03-30
### Changed
- Changed the PHP Composer Version test to only compare the minor version and not the patch version.

### Fixed
- Fixed a bug in which control panel alerts were being overwritten instead of merged ([#27](https://github.com/putyourlightson/craft-sherlock/issues/27)).

## 3.1.1 - 2021-02-03
### Fixed
- Fixed a bug in which the Content Security Policy meta tag was not being recognised if it contained line breaks ([#23](https://github.com/putyourlightson/craft-sherlock/issues/23)).
- Fixed a bug in which the `sherlock/scans/run-scan` action was requiring the user to be logged in ([#24](https://github.com/putyourlightson/craft-sherlock/issues/24)).

## 3.1.0 - 2021-01-26
### Added
- Added the PHP Composer Version test.

### Fixed
- Fixed an exception that was being thrown when one of the files being checked did not exist ([#21](https://github.com/putyourlightson/craft-sherlock/issues/21)).

## 3.0.0 - 2021-01-20
### Added
- Added Lite, Plus and Pro editions.
- Added integration with [Bugsnag](https://bugsnag.com/). 
- Added integration with [Rollbar](https://rollbar.com/). 
- Added integration with [Sentry](https://sentry.io/). 
- Added multi site functionality for security scans.
- Added the ability to add a Content Security Policy in the plugin settings.
- Added the ability to add HTTP Headers in the plugin settings.
- Added the `sherlock/scans/run` console command.
- Added the `Content-Security-Policy` header and meta tag test.
- Added the `Expect-CT` header test.
- Added the `Referrer-Policy` header test.
- Added the Admin Username test.
- Added the Defer Public Registration Password test.
- Added the Elevated Session Duration test.
- Added the Web Alias In Base Site URL test.
- Added the Web Alias In Base Volume URL test.
- Added PHP version support thresholds up until PHP 8.0 ([supported versions](https://www.php.net/supported-versions.php)).
- Added logging to a dedicated `sherlock.log` file.
- Added unit tests.

### Changed
- Changed the HTTPS tests to ensure that an encrypted HTTPS connection is required.
- Changed the file and folder permissions test criteria.
- Non-critical Craft and plugin updates now display warnings instead of failures in high security mode.
- The `X-XSS-Protection` header now only display a warning instead of a failure ([reasoning](https://scotthelme.co.uk/security-headers-updates/#removing-the-x-xss-protection-header)). 
- Renamed "Live Mode" to "Monitoring".
- Improved test icons, explanations, thresholds and documentation links.

### Fixed
- Fixed wording of user session duration test.
- Fixed output of default file permissions test.

### Removed
- Removed the plugin vulnerabilities JSON feed.
- Removed the secret key.

## 2.3.0 - 2020-12-26
### Added
- Added prevent user enumeration test.
- Added sanitize SVG uploads test.

### Changed
- Minor UI improvements.
- Removed security key test.

## 2.2.5 - 2020-08-24
### Fixed
- Fixed `X-XSS-Protection` case issue ([#16](https://github.com/putyourlightson/craft-sherlock/issues/16)).

## 2.2.4 - 2020-07-02
### Changed
- Minor UI improvements.

### Fixed
- Fixed `X-XSS-Protection` test.

## 2.2.3 - 2020-07-02
### Changed
- Headers are now correctly detected regardless of whether in normal or lower case.
- Headers are now stripped of tags to ensure they are safe to output to the browser.

## 2.2.2 - 2020-03-31
### Fixed
- Fixed a bug in which scans could throw an error with recent versions of Craft ([#13](https://github.com/putyourlightson/craft-sherlock/issues/13)).

## 2.2.1 - 2020-03-26
### Fixed
- Fixed a bug when running a scan and using Postgres ([#12](https://github.com/putyourlightson/craft-sherlock/issues/12)).

## 2.2.0 - 2019-09-30
### Added
- Added the ability to add `*` and `?` wildcards to restricted IP addresses ([#11](https://github.com/putyourlightson/craft-sherlock/issues/11)).

### Fixed
- Fixed a bug in the restriction of IP addresses on the front-end.

## 2.1.3 - 2019-09-02
### Fixed
- Fixed an error that could occur when running a scan using the API key ([#10](https://github.com/putyourlightson/craft-sherlock/issues/10)).

## 2.1.2 - 2019-06-19
### Fixed
- Fixed migration issue that could happen with project config ([#7](https://github.com/putyourlightson/craft-sherlock/issues/7)).
- Fixed `defaultTokenDuration` test that was failing incorrectly ([#8](https://github.com/putyourlightson/craft-sherlock/issues/8)).

## 2.1.1 - 2019-06-14
### Changed
- Improved spacing and info tooltip sizing.
- Changed duration settings from intervals to seconds. 

### Fixed
- Fixed duration tests that were failing incorrectly  ([#7](https://github.com/putyourlightson/craft-sherlock/issues/7)).

## 2.1.0 - 2019-02-11
### Added
- Added welcome screen after the plugin is installed.
- Added system email to default plugin settings.
- Added environment variables to API settings.
- Added config warnings to settings.

### Changed
- Changed minimum requirement of Craft to version 3.1.0.

### Fixed
- Fixed redirect to settings screen after the plugin is installed.

## 2.0.4 - 2019-02-07
### Fixed
- Fixed check for redirect of insecure front-end URL.

## 2.0.3 - 2019-02-07
### Changed
- Improved feedback for insecure front-end URL connection errors. 
- Improved formatting of test results.

## 2.0.2 - 2019-01-03
### Fixed
- Fixed CMS and plugin update detection.
- Fixed a bug where restricted IP addresses were not being parsed correctly in some server environments.

## 2.0.1 - 2018-07-12
### Changed
- Changed plugin icon.
- Plugin does not interfere with console requests.

### Fixed
- Fixed a bug where restricted IP addresses were not being checked correctly on servers that use carriage returns in new lines.
