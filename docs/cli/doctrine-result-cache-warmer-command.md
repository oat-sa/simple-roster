# Doctrine Result Cache Warmer

[DoctrineResultCacheWarmerCommand](../../src/Command/Cache/DoctrineResultCacheWarmerCommand.php) is responsible for refreshing the result cache of Doctrine.

Currently we use result cache for `getByUsernameWithAssignments()` method in `UserRepository`.

## Usage:
```bash
$ bin/console roster:doctrine-result-cache:warmup
```
#### Main options:

| Option | Description |
| ------------- |:-------------|
| -b, --batch-size | Number of cache entries to refresh per batch [default: `1000`] |
| -u, --user-ids | User IDs that need to be warmed up. --user-ids=x[,x,[,...]] |
| -l, --line-item-ids | Line item IDs of users that need to be warmed up. --line-item-ids=x[,x,[,...]] |

#### Other options

For the full list of options please refer to the helper option:
```bash
$ bin/console roster:doctrine-result-cache:warmup -h
```