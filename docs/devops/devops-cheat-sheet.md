# DevOps Cheat Sheet

This document contains the list of main commands required for setting up / debug the application.

### Application setup

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
    
### Useful commands

- Clear application cache

```bash
$ bin/console cache:clear
```

- Warm-up Doctrine cache (of user assignments)

```bash
$ bin/console roster:doctrine-result-cache:warmup
```

- Assignment garbage collector (to handle stuck assignments)

```bash
$ bin/console roster:garbage-collector:assignment --force
```

### Full list of available commands

```bash
$ bin/console
```