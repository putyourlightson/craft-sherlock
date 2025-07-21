# Release Notes for Sherlock

## 5.2.0 - 2025-07-21

- Added basic auth username and password settings ([#48](https://github.com/putyourlightson/craft-sherlock/issues/48)).
- Added non-CSP, browser-supported directives ([#47](https://github.com/putyourlightson/craft-sherlock/issues/47)).

## 5.1.2 - 2025-04-11

- Improved the processing of comma-separated notification email addresses ([#46](https://github.com/putyourlightson/craft-sherlock/issues/46)).

## 5.1.1 - 2025-04-09

- Fixed a bug in which notification emails were not being sent on newly failed scans ([#46](https://github.com/putyourlightson/craft-sherlock/issues/46)).

## 5.1.0 - 2024-05-27

### Added

- Added the `maxScans` config setting that determines the number of scans Sherlock will keep before it starts deleting the oldest scans.

### Changed

- Updated the supported PHP version periods and added 8.3.

## 5.0.0 - 2024-04-08

### Added

- Added compatibility with Craft 5.

### Removed

- Removed the `expectCT` setting.
