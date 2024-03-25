# Changelog

## 4.4.2 - 2024-03-25

### Changed
 
- A custom log target is now only registered if a dispatcher exists.
- The content security policy is now applied as early as possible in the initialisation process.

## 4.4.1 - 2024-01-12

### Fixed

- Fixed an error that could occur if a plugin update had no associated release date ([#45](https://github.com/putyourlightson/craft-sherlock/issues/45)).

## 4.4.0 - 2023-10-09

### Added

- Added the `TestsService::EVENT_BEFORE_RUN_TESTS` event that can be used to override the Guzzle client ([#43](https://github.com/putyourlightson/craft-sherlock/issues/43)).

## 4.3.0 - 2023-01-02

### Added

- Added the `BaseIntegration::BEFORE_RUN_INTEGRATION` event that can be used to modify the configuration or cancel the running of an integration.

## 4.2.3 - 2022-12-22

### Changed

- The front-end HTTPS redirect test no longer results in an error if the web server blocks insecure requests.

## 4.2.2 - 2022-12-15

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
