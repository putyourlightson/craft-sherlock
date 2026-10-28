# Release Notes for Sherlock

## 5.3.0 - Unreleased

- Added PHP 8.4 and 8.5 security support thresholds and a critical PHP security update test for PHP2Shell.
- Improved CSP checks to assess effective script restrictions and recognise enforced `frame-ancestors` headers as framing protection.
- Changed CORS findings to review warnings instead of failing scans for wildcard access, which may be intentional for public resources.
- Changed the default `Referrer-Policy` header to `strict-origin-when-cross-origin` and updated its security test to warn about invalid or less restrictive policies.
- Changed the default `Strict-Transport-Security` header and security test threshold to one year, and fixed incorrect parsing of `max-age` values when other directives are present.
- Fixed HTTPS checks incorrectly passing on HTTP error pages and connection failures.
- Removed the obsolete `X-XSS-Protection` header test and default header ([#54](https://github.com/putyourlightson/craft-sherlock/issues/54)).

## 5.2.1 - 2026-04-14

- Added the ability to view read-only settings in environments where admin changes are disallowed ([#53](https://github.com/putyourlightson/craft-sherlock/issues/53)).
- Fixed a bug in which an error could be thrown if the latest release of a plugin did not have an associated date ([#49](https://github.com/putyourlightson/craft-sherlock/issues/49)).

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
