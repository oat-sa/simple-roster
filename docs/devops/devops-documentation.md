# DevOps Documentation

DevOps related information for setting up / debug / maintain the application.

## Configuration file

The main configuration file is `.env`, located in root folder.

## Cache configuration

The redis TTL value can be set in `.env`, with `USER_CACHE_TTL` value.

This value should be greater than the duration on the test campaign (so it never expires during the campaign).

## Application setup steps

- Install dependencies with Composer

```bash
$ composer install --optimize-autoloader
```

- Create database with Doctrine

```bash
$ bin/console doctrine:database:create
``` 

- Create database schema with Doctrine

```bash
$ bin/console doctrine:schema:update --force
```

- If needed, drop database schema with Doctrine

```bash
$ bin/console doctrine:schema:drop --force
```
    
## Cache management commands

- Clear application cache

```bash
$ bin/console cache:clear [--env=dev|prod]
```

- Warm-up Doctrine cache

```bash
$ bin/console roster:doctrine-result-cache:warmup
```

- Refresh Doctrine metadata cache

```bash
$ bin/console doctrine:cache:clear-metadata
```

- Refresh Doctrine query cache

```bash
$ bin/console doctrine:cache:clear-query
```

- Refresh Doctrine result cache

```bash
$ bin/console doctrine:cache:clear-result
```

## Maintenance related commands

- Assignment garbage collector (to collect stuck assignments)

```bash
$ bin/console roster:garbage-collector:assignment --force
```

## Full list of available commands

```bash
$ bin/console
```

## Application logs file

Application logs are populated in `var/log/[dev|prod].log`