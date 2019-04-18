# Changelog

## 1.2.0 - 2019-04-18

### Added
- Bulk assignment cancellation CLI command.
- Bulk assignment creation CLI command.
- OAT Docker stack support in development environment.
- PHPUnit 8 support.

### Removed
- PHPUnit Bridge

### Fixed
- Bug in bulk assignment services due to `getAvailableAssignments()` method usage. From now on bulk operations are not time sensitive (`startAt` and `endAt` dates in `LineItem`).

## 1.1.1 - 2019-03-19

### Fixed
- Execution speed and memory consumption of _bulk_ endpoints in case of large operation size.

## 1.1.0 - 2019-03-19

### Added
- Possibility to specify charset for regular and native ingesters.
- Possibility to perform integrity checks on ingester dry runs.
