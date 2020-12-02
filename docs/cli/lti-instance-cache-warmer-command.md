# LTI instance cache warmer command

[LtiInstanceCacheWarmerCommand](../../src/Command/Cache/LtiInstanceCacheWarmerCommand.php) is responsible for warming up 
the cache for `LTI instances` after [ingesting](lti-instance-ingester-command.md) them.
    
## Usage
```shell script
$ sudo -u www-data bin/console roster:cache-warmup:lti-instance
```

For the full list of options please refer to the helper option:
```shell script
$ sudo -u www-data bin/console roster:cache-warmup:lti-instance -h
```
