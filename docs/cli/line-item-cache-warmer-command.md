# Line Item cache warmer command

[LtiInstanceCacheWarmerCommand](../../src/Command/Cache/LineItemCacheWarmerCommand.php) is responsible for warming up 
the cache for `Line Items` after [ingesting](line-item-ingester-command.md) them.
    
## Usage
```shell script
$ sudo -u www-data bin/console roster:cache-warmup:line-item
```

For the full list of options please refer to the helper option:
```shell script
$ sudo -u www-data bin/console roster:cache-warmup:line-item -h
```