# Doctrine Result Cache Warmer

[DoctrineResultCacheWarmerCommand](../../src/Command/Cache/DoctrineResultCacheWarmerCommand.php) is responsible for refreshing the result cache of Doctrine.

Currently we use result cache for `getByUsernameWithAssignments()` method in `UserRepository`.

## Usage:
```bash
$ bin/console roster:doctrine-result-cache:warm-up
```
#### Main options:

| Option | Description |
| ------------- |:-------------|
| -b, --batch-size | Number of cache entries to refresh per batch [default: `1000`] |

#### Other options

For the full list of options please refer to the helper option:
```bash
$ bin/console roster:doctrine-result-cache:warm-up -h
```