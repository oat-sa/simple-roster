# Changelog

## 2.0.0 - TODO: To be released

### Added
- Added static code analysis with PHPStan, PHP Mess Detector and PHP CodeSniffer to pull request CI pipeline.

### Changed
- Raised minimum required PHP version from `7.2` to `7.3`.
- Upgraded Symfony framework version from `4` to `5`.
- Changed `user-ids` and `line-item-ids` input options of [roster:doctrine-result-cache:warmup](docs/cli/doctrine-result-cache-warmer-command.md) command to `usernames` and `line-item-slugs`.
- Application namespace now is `OAT\SimpleRoster` instead of `App`

## 1.8.1 - 2020-10-27

### Changed
- Switched from offset based pagination to cursor based in `DoctrineResultCacheWarmerCommand` for better performance.
- Switched from ORM to native queries in `NativeUserIngesterCommand` for better performance.

### Fixed
- Fixed bug in `DoctrineResultCacheWarmerCommand` where lack of order by clause caused wrong pagination with PostgreSQL.
- Fixed `OAuthSignatureValidationSubscriber` to read LTI credentials from configuration instead of database.

## 1.8.0 - 2020-10-08

### Added
- Added possibility to specify LTI key and secret through environment variables.
- Added `modulo` and `remainder` options to `DoctrineResultCacheWarmerCommand` for parallelized cache warmups. More info [here](docs/cli/doctrine-result-cache-warmer-command.md#advanced-usage).
- Added separate log channel for cache warmup for better trackability of failed cache warmups.
- Added possibility to ingest multiple assignments per user with `NativeUserIngesterCommand`. More details [here](docs/cli/native-user-ingester-command.md#user-ingestion-with-multiple-assignments).

### Changed
- Performance improvement of `NativeUserIngesterCommand` by counting the number of users by using process component.

## 1.7.0 - 2020-09-16

### Added
- Added xml namespace environment variable for ReplaceResultRequest, used by ReplaceResultSourceIdExtractor, to follow [LTI 1.1 specifications](https://www.imsglobal.org/specs/ltiv1p1p1/implementation-guide#toc-26).

### Fixed
- Fixed [security breach](https://symfony.com/blog/cve-2020-15094-prevent-rce-when-calling-untrusted-remote-with-cachinghttpclient) by updating symfony/http-kernel to version 4.4.13. 
- Fixed issue where attemptCount was always returning same value.

## 1.6.2 - 2020-09-03

### Fixed
- Fixed assignment attempt handling logic in case no maximum attempts count defined.

## 1.6.1 - 2020-08-17

### Fixed
- Fixed circular reference during `composer install` caused by doctrine event subscriber depending on the DBAL connection directly.

## 1.6.0 - 2020-07-23

### Added
- Added new property `maxAttempts` to the `LineItem` entity.
- Added support for ingestion of `maxAttempts` on the `LineItemIngester`.
- Added new property `attemptsCount` to the `Assignment` entity.

### Changed
- Changed LTI outcome state update to `ready` if `Assignment` has additional attempts available.
- Changed garbage collection state update to `ready` if `Assignment` has additional attempts available.
- Increment the `attemptsCount` on upon LTI Launch if state is not started.
- Set default value of `0` for `attemptsCount` for new assignments during user ingestion.
- The `/api/v1/assignments` endpoint now returns all users assignments (available or not).
- The `UserCacheInvalidationSubscriber` now warms up the cache after invalidating it.

## 1.5.0 - 2020-06-17

### Added
- Added dedicated `docker` application environment for development purposes.
- Added Pull Request CI pipeline with Jenkins including:
    - Running PHPUnit test suite
    - Running PHPUnit code coverage checker
    - Running mutation tests with Infection
    - Running static code analysis with PHPStan
- Added package dependency security checker into composer. 

### Changed
- Changed password encoding algorithm from hardcoded Argon2i to [automatic](https://symfony.com/blog/new-in-symfony-4-3-native-password-encoder).
- Increased time cost of password encoding from `1` to `3` following [password hashing guidelines](https://libsodium.gitbook.io/doc/password_hashing/the_argon2i_function#guidelines-for-choosing-the-parameters).
- Changed test suite bootstrapping mechanism to automatically clear cache before executing the test suite.
- Changed Doctrine's ORM naming strategy from `underscore` to `underscore_number_aware`.
- Reworked `README.md` structure for better readability.
- Changed doctrine ORM mapping driver from `yaml` to `xml`.
- Username is now passed in `user_id` LTI parameter

### Fixed
- Reverted temporary PHPUnit fix done in version `1.4.1`.
- Reverted temporary fix caused by a PHP `7.2.20` bug done in version `1.4.0`.
- Moved Symfony deprecation helper from PHPUnit XML configuration file to `.env` file and updated threshold to not break tests by default. 
- Fixed all static code analysis issues, achieving maximum quality strictness level.

## 1.4.4 - 2020-04-14

### Removed
- Removed `oat-sa/phing-tasks` dependency from `composer.json`.

## 1.4.3 - 2020-04-04

### Security
- Fixed [GHSA-g4m9-5hpf-hx72](https://github.com/advisories/GHSA-g4m9-5hpf-hx72) - Firewall configured with unanimous strategy was not actually unanimous in Symfony.
- Fixed [GHSA-mcx4-f5f5-4859](https://github.com/advisories/GHSA-mcx4-f5f5-4859) - Prevent cache poisoning via a Response Content-Type header in Symfony.

## 1.4.2 - 2019-10-11

### Changed
- Updated `oat-sa/phing-tasks` dependency version in `composer.json` from `0.1` to `0.3`.

## 1.4.1 - 2019-08-08

### Fixed
- Fixed missing User `groupId` handling in `NativeUserIngesterCommand`.
- Fixed PHPUnit version to `<8.3` because of https://github.com/symfony/symfony/issues/32879.

## 1.4.0 - 2019-07-23

### Added
- Added `force` parameter to `NativeUserIngesterCommand`
- Added possibility to determine the `contextId` LTI request parameter based on LTI load balancing strategy.

### Fixed
- Fixed fatal error during composer install caused by https://bugs.php.net/bug.php?id=76980 on php `7.2.20`.

## 1.3.0 - 2019-07-10

### Added
- Added `groupId` property to `User` entity for logical grouping of users.
- Added possibility to warm up result cache by line item ids or user ids in `DoctrineResultCacheWarmerCommand`.
- Introduced LTI load balancing interface, implemented `User` group id based LTI load balancing strategy.

### Changed
- Upgraded to Symfony version `4.3`

## 1.2.0 - 2019-04-29

### Added
- Added bulk assignment cancellation CLI command.
- Added bulk assignment creation CLI command.
- Added OAT Docker stack support in development environment.
- Added PHPUnit 8 support.

### Removed
- Removed PHPUnit Bridge.

### Fixed
- Fixed bug in bulk assignment services due to `getAvailableAssignments()` method usage. From now on bulk operations are not time sensitive (`startAt` and `endAt` dates in `LineItem`).

## 1.1.1 - 2019-03-19

### Fixed
- Fixed execution speed and memory consumption of _bulk_ endpoints in case of large operation size.

## 1.1.0 - 2019-03-19

### Added
- Added possibility to specify charset for regular and native ingesters.
- Added possibility to perform integrity checks on ingester dry runs.
