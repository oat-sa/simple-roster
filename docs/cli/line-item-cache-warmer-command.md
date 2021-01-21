# Line Item cache warmer command

[LineItemCacheWarmerCommand](../../src/Command/Cache/LineItemCacheWarmerCommand.php) is responsible for warming up 
the cache for `Line Items` after [ingesting](line-item-ingester-command.md) them.
    
- [Usage](#usage)
- [Related environment variables](#related-environment-variables)
    
## Usage
```shell script
$ sudo -u www-data bin/console roster:cache-warmup:line-item
```

For the full list of options please refer to the helper option:
```shell script
$ sudo -u www-data bin/console roster:cache-warmup:line-item -h
```

## Related environment variables

| Variable | Description |
|----------|-------------|
| `DATABASE_URL` | Database connection string. Supported formats are described [here](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html#connecting-using-a-url). |
| `REDIS_DOCTRINE_CACHE_HOST` | Redis host for doctrine cache storage. |
| `REDIS_DOCTRINE_CACHE_PORT` | Redis port for doctrine cache storage. |
| `CACHE_TTL_LINE_ITEM` | Cache TTL (in seconds) for caching individual line items. |
