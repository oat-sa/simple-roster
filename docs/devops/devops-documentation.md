# DevOps Documentation

DevOps related information for setting up / debug / maintain the application.

## Configuration

#### Configuration file

The main configuration file is `.env`, located in root folder.

#### Configuration parameters

- Framework:

    | Parameter | Description |
    | ------------- |:-------------|
    | APP_ENV | Application environment, `dev`, `prod` or `test` [default: `prod`] |
    | APP_DEBUG | Application debug mode, [default: `false`] |
    | APP_SECRET | Application secret |
    | APP_API_KEY | Application API Key |
    | APP_ROUTE_PREFIX | Application route prefix, [default: `/api/v1` ]. For details, follow: [Applying custom route prefix](#applying-custom-route-prefix)
    
- AWS:

    | Parameter | Description |
    | ------------- |:-------------|
    | AWS_REGION | AWS Region [default: `eu-west-1`] |
    | AWS_VERSION | AWS Version [default: `latest`] |
    | AWS_KEY | AWS Key |
    | AWS_SECRET | AWS Secret |
    | AWS_S3_INGEST_BUCKET | AWS S3 bucket used for ingestion |
    
- Database:

    | Parameter | Description |
    | ------------- |:-------------|
    | DATABASE_URL | Database url |
    
- Cache:

    | Parameter | Description |
    | ------------- |:-------------|
    | REDIS_DOCTRINE_USER_CACHE_TTL | Doctrine User entity cache storage TTL [default: `3600`] |
    | REDIS_DOCTRINE_CACHE_HOST | Redis host for doctrine cache storage |
    | REDIS_DOCTRINE_CACHE_PORT | Redis port for doctrine cache storage |
    | REDIS_SESSION_CACHE_HOST | Redis host for sessions cache storage |
    | REDIS_SESSION_CACHE_PORT | Redis host for sessions cache storage |
    
- CORS:

    | Parameter | Description |
    | ------------- |:-------------|
    | CORS_ALLOW_ORIGIN | Allowed CORS origin |
    
- Garbage collector:

    | Parameter | Description |
    | ------------- |:-------------|
    | ASSIGNMENT_STATE_INTERVAL_THRESHOLD | Threshold for assignment garbage collection [default: `P1D`] |

- LTI configuration:

    | Parameter | Description |
    | ------------- |:-------------|
    | LTI_ENABLE_INSTANCES_LOAD_BALANCER | Whether the LTI link should be load balanced or not [default: `false`] |
    | LTI_LAUNCH_PRESENTATION_RETURN_URL | Frontend LTI return link |
    | LTI_LAUNCH_PRESENTATION_LOCALE | Defines the localisation of TAO instance [default: `en-EN`] |

- Blackfire:

    | Parameter | Description |
    | ------------- |:-------------|
    | BLACKFIRE_SERVER_ID | Blackfire server id |
    | BLACKFIRE_SERVER_TOKEN | Blackfire server token |

#### LTI load balancer configuration

The default map of load balancer for LTI instances is located in `config/packages/lti_instances.yaml`.

It can be overridden per instance (dev, prod) by dropping this file in `config/packages/<env>/lti_instances.yaml`.

#### Cache configuration

The redis TTL value can be set in `.env`, with `REDIS_DOCTRINE_USER_CACHE_TTL` value.

This value should be greater than the duration on the test campaign (so it never expires during the campaign).

## Application setup steps

- Install dependencies with Composer

```bash
$ composer install --optimize-autoloader
```

- Optimize composer autoloader

```bash
$ composer dump-autoload --optimize --no-dev --classmap-authoritative
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

## Applying custom route prefix

Custom route prefix can be defined via `APP_ROUTE_PREFIX` application environment. 
If you do so, please make sure to include the leading slash character, but *NO* trailing slash.

To apply the changes, you need to clear the application cache:

```bash
$ bin/console cache:clear [--env=dev|prod]
```

To verify the changes:

```bash
$ bin/console debug:router [--env=dev|prod]
```

## Useful commands

#### Cache management commands

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

#### Maintenance related commands

- Assignment garbage collector (to collect stuck assignments)

```bash
$ bin/console roster:garbage-collector:assignment --force
```

#### Full list of available commands

```bash
$ bin/console
```

## Application logs file

Application logs are populated in `var/log/[dev|prod].log`

## Performances checklist

Also, for prod instances, we should follow this [checklist](https://symfony.com/doc/current/performance.html). 