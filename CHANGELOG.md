# Changelog

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
