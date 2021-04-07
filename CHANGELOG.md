# Changelog

## 2.1.0 - To be released

### Added
- Added `status` column to `line_items` database table.
- Added `status` column to `assignments` database table.

### Changed
- Changed availability logic of line items: From now on it is possible to set date restrictions by specifying only starting or ending date.
- Changed identifier generation strategy of all entities from `auto increment` to `uuid`.

### Removed
- Removed `isActive` column from `line_items` database table.
- Removed `state` column from `assignments` database table.
- Removed synchronous parallel cache warmup feature from user cache warmer command.

## 2.0.7 - 2021-03-27

### Fixed
- Fixed memory leak in user cache warmup process.

## 2.0.6 - 2021-03-12

### Fixed
- Renamed webhook event name from `RemoteDeliveryPublicationFinished` to `oat\\taoPublishing\\model\\publishing\\event\\RemoteDeliveryCreatedEvent`.
- Fixed [roster:modify-entity:line-item:change-dates](docs/cli/modify-entity-line-item-change-dates-command.md) command to allow proper use of timezone offset.
  We now convert the input date(s) to UTC before persisting it.

## 2.0.5 - 2021-03-03

### Fixed
- Renamed webhook event payload attribute name from `deliveryURI` to `remoteDeliveryId`.

## 2.0.4 - 2021-02-25

### Fixed
- Fixed missing cache invalidation when creating new assignments through the bulk create assignments API endpoint.

## 2.0.3 - 2021-02-23

### Fixed
- Fixed LTI 1.1 Outcome signature validation logic to be able to handle use case when multiple LTI instances share the same LTI key.

## 2.0.2 - 2021-02-22

### Fixed
- Fixed cache warmup logic to trigger on `postFlush` event instead of `onFlush` to guarantee database modifications being done before refreshing the cache.
- Fixed JWT token authentication to not support requests with `Authorization` header but no bearer token.

## 2.0.1 - 2021-02-12

### Fixed
- Used proper `lti_version` string for LTI 1.1 requests. We were trying to send it as `1.1.1` while TAO does support only `LTI-1p0` for LTI 1.x.

## 2.0.0 - 2021-01-21

### Added
- Added static code analysis with PHPStan, PHP Mess Detector and PHP CodeSniffer to pull request CI pipeline.
- Added [roster:ingest:lti-instance](docs/cli/lti-instance-ingester-command.md) command.
- Added [roster:ingest:line-item](docs/cli/line-item-ingester-command.md) command.
- Added [roster:ingest:user](docs/cli/user-ingester-command.md) command.
- Added [roster:ingest:assignment](docs/cli/assignment-ingester-command.md) command.
- Added [roster:cache-warmup:lti-instance](docs/cli/lti-instance-cache-warmer-command.md) command.
- Added [roster:cache-warmup:line-item](docs/cli/line-item-cache-warmer-command.md) command.
- Added [roster:cache-warmup:user](docs/cli/user-cache-warmer-command.md) command.
- Added [roster:modify-entity:line-item:change-dates](docs/cli/modify-entity-line-item-change-dates-command.md) command.
- Added [roster:modify-entity:line-item:change-state](docs/cli/modify-entity-line-item-change-state-command.md) command.
- Added possibility to use multiple filesystem instances with the help of [Storage registry](docs/storage-registry.md).
- Added possibility to launch assignments with [LTI 1.3](http://www.imsglobal.org/spec/lti/v1p3/).
- Added possibility to process a `basic outcome replaceResult` request using LTI 1.3 flow.
- Added `LTI_VERSION` environment variable to control version we are working (1.1.1 or 1.3.0).
- Added `CACHE_TTL_LINE_ITEM` environment variable.
- Added environment variables specific for [LTI 1.3](docs/devops-documentation.md).
- Added `WEBHOOK_BASIC_AUTH_USERNAME` and `WEBHOOK_BASIC_AUTH_PASSWORD` environment variables.
- Added possibility to profile CLI commands and HTTP calls with [Blackfire](docs/blackfire.md).
- Added static code analysis with PHPStan, PHP Mess Detector and PHP CodeSniffer to pull request CI pipeline.
- Added possibility to update line items via WebHook Endpoint: `/v1/web-hooks/update-line-items`

### Changed
- Raised minimum required PHP version from `7.2` to `7.3`.
- Upgraded Symfony framework version from `4` to `5`.
- `REDIS_DOCTRINE_USER_CACHE_TTL` environment variable has been renamed to `CACHE_TTL_GET_USER_WITH_ASSIGNMENTS`.
- Renamed `.env.dist` to `.env` based on [Symfony recommendations](https://symfony.com/doc/current/configuration/dot-env-changes.html).
- Merged `simple-roster-doctrine-redis` and `simple-roster-session-redis` docker containers to ease development.
- Application namespace has been changed from `App\` to `OAT\SimpleRoster\`.
- Changed `APP_ROUTE_PREFIX` variable to exclude API version from it. Corresponding changes made to the `routes.yaml`/`security.yaml`
- Changed security flow to use JWT for api endpoints. Everything under `^%app.route_prefix%/v1/` except `healthcheck` and `bulk` endpoints is affected
- Changed login endpoint for JWT auth. Now it is not `^%app.route_prefix%/v1/auth/login`, but `^%app.route_prefix%/v1/auth/token`
- Renamed `UpdateLtiOutcomeAction` to `UpdateLti1p1OutcomeAction` for consistency.
- Changed security settings to use basic authentication on webhook endpoint.
- Moved health check endpoint to API root.

### Removed
- Removed `roster:ingest` command.
- Removed `roster:native-ingest:user` command.
- Removed `roster:cache:warmup` command.
- Removed `roster:assignments:bulk-create` command.
- Removed `roster:assignments:bulk-cancel` command.
- Removed `lti_instances.yaml` configuration file.
- Removed `LTI_KEY`, `LTI_SECRET` and `LTI_ENABLE_INSTANCES_LOAD_BALANCER` environment variables.

### Fixed
- Fixed HTTP code returned in case assignment exists but unavailable for `getUserAssignmentLtiLink` endpoint.
- Fixed response on `UpdateLti1p1OutcomeAction` to return valid XML on success.

## 1.8.1 - 2020-10-27

### Changed
- Switched from offset based pagination to cursor based in `roster:cache:warmup` command for better performance.
- Switched from ORM to native queries in [roster:ingest:user](docs/cli/user-ingester-command.md) command for better performance.

### Fixed
- Fixed bug in `roster:cache:warmup` command where lack of order by clause caused wrong pagination with PostgreSQL.
- Fixed `OAuthSignatureValidationSubscriber` to read LTI credentials from configuration instead of database.

## 1.8.0 - 2020-10-08

### Added
- Added possibility to specify LTI key and secret through environment variables.
- Added `modulo` and `remainder` options to `roster:cache:warmup` command for parallelized cache warmups. More info [here](docs/cli/cache-warmer-command.md#advanced-usage).
- Added separate log channel for cache warmup for better trackability of failed cache warmups.
- Added possibility to ingest multiple assignments per user with [roster:ingest:user](docs/cli/user-ingester-command.md) command. More details [here](docs/cli/user-ingester-command.md#user-ingestion-with-multiple-assignments).

### Changed
- Performance improvement of [roster:ingest:user](docs/cli/user-ingester-command.md) command by counting the number of users by using process component.

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
- Fixed missing User `groupId` handling in [roster:ingest:user](docs/cli/user-ingester-command.md) command.
- Fixed PHPUnit version to `<8.3` because of https://github.com/symfony/symfony/issues/32879.

## 1.4.0 - 2019-07-23

### Added
- Added `force` parameter to [roster:ingest:user](docs/cli/user-ingester-command.md) command.
- Added possibility to determine the `contextId` LTI request parameter based on LTI load balancing strategy.

### Fixed
- Fixed fatal error during composer install caused by https://bugs.php.net/bug.php?id=76980 on php `7.2.20`.

## 1.3.0 - 2019-07-10

### Added
- Added `groupId` property to `User` entity for logical grouping of users.
- Added possibility to warm up result cache by line item ids or user ids in `roster:cache:warmup` command.
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
