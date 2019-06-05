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
| -u, --user-ids | List of comma separated user IDs to warm up. |
| -l, --line-item-ids | List of comma separated Line item IDs to warm up. |

#### Other options

For the full list of options please refer to the helper option:
```bash
$ bin/console roster:doctrine-result-cache:warmup -h
```

## Examples:

Warming up all result cache entries:

```bash
$ bin/console roster:doctrine-result-cache:warmup
```

Warming up result cache entries for specific users:
```bash
$ bin/console roster:doctrine-result-cache:warmup --user-ids=1,2,3,4
```

Warming up result cache entries for specific line items:
```bash
$ bin/console roster:doctrine-result-cache:warmup --line-item-ids=1,5,10
```
