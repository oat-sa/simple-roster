# Changelog

## 1.3.0 - 2019-07-09

### Added
- Added `groupId` property to `User` entity for logical grouping of users.
- Added possibility to warm up result cache by line item ids or user ids in `DoctrineResultCacheWarmerCommand`.

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
