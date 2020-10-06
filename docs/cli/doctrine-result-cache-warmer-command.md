# Doctrine Result Cache Warmer

[DoctrineResultCacheWarmerCommand](../../src/Command/Cache/DoctrineResultCacheWarmerCommand.php) is responsible for refreshing the result cache of Doctrine.

Currently we use result cache for `getByUsernameWithAssignments()` method in `UserRepository`.

## Basic usage
```bash
$ sudo -u www-data bin/console roster:doctrine-result-cache:warmup
```
### Main options

| Option | Description |
| ------------- |:-------------|
| -b, --batch-size | Number of cache entries to refresh per batch [default: `1000`] |
| -u, --user-ids | List of comma separated user IDs to warm up. |
| -l, --line-item-ids | List of comma separated Line item IDs to warm up. |

For the full list of options please refer to the helper option:
```bash
$ sudo -u www-data bin/console roster:doctrine-result-cache:warmup -h
```

### Examples

Warming up all result cache entries in batch of 10.000:

```shell script
$ sudo -u www-data bin/console roster:doctrine-result-cache:warmup --batch-size=10000
```

Warming up result cache entries for specific users:
```shell script
$ sudo -u www-data bin/console roster:doctrine-result-cache:warmup --user-ids=1,2,3,4
```

Warming up result cache entries for specific line items:
```shell script
$ sudo -u www-data bin/console roster:doctrine-result-cache:warmup --line-item-ids=1,5,10
```

## Advanced usage

Sometimes it can be necessary to parallelize the cache warmup process due to the huge amount of users in the system. This
can be done by applying an Euclidean division (`modulo` option) on the primary key of the users (ID) and by launching 
multiple commands in parallel with different `remainder` option. 

### Advanced options

| Option | Description |
| ------------- |:---------------|
| -m, --modulo | Modulo (M) of Euclidean division A = M*Q + R (0 ≤ R < M), where A = user id, Q = quotient, R = 'remainder' option. |
| -r, --remainder | Remainder (R) of Euclidean division A = M*Q + R (0 ≤ R < M), where A = user id, Q = quotient, M = 'modulo' option. |

### Example

Let's assume we would like to parallelize the cache warmup by launching `4` instances of the command in separate screens:

First let's warmup the cache for all users where `ID % 4 === 0`.

```shell script
$ screen -S cache-warmup-0
$ sudo -u www-data bin/console roster:doctrine-result-cache:warmup --modulo=4 --remainder=0
```

Exit from screen `cache-warmup-0` by pressing `CTRL+A` then `CTRL+D`.

Now let's warmup the cache for all users where `ID % 4 === 1`.

```shell script
$ screen -S cache-warmup-1
$ sudo -u www-data bin/console roster:doctrine-result-cache:warmup --modulo=4 --remainder=1
```

Exit from screen `cache-warmup-1` by pressing `CTRL+A` then `CTRL+D`.

Now let's warmup the cache for all users where `ID % 4 === 2`.

```shell script
$ screen -S cache-warmup-2
$ sudo -u www-data bin/console roster:doctrine-result-cache:warmup --modulo=4 --remainder=2
```

Exit from screen `cache-warmup-2` by pressing `CTRL+A` then `CTRL+D`.

Now let's warmup the cache for all users where `ID % 4 === 3`.

```shell script
$ screen -S cache-warmup-3
$ sudo -u www-data bin/console roster:doctrine-result-cache:warmup --modulo=4 --remainder=3
```

Exit from screen `cache-warmup-3` by pressing `CTRL+A` then `CTRL+D`.

Once all the commands have finished, the result cache should be warmed up for all the users in the system.

> **Improtant** - Always take into account the physical limitations of your web server, database instance and cache server 
> before deciding how many command instances to launch in parallel.
  