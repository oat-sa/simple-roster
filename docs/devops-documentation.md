# DevOps Documentation

> DevOps related information for setting up / debug / maintain the application.

## Table of Contents
- [Environment variables](#environment-variables)
- [Application setup steps (production)](#application-setup-steps-production)
- [Performance checklist (production)](https://symfony.com/doc/current/performance.html)
- [CLI documentation](cli-documentation.md)
- [LTI configuration](features/lti.md)
- [Generate RSA keypair for JWT authentication flow](#generate-rsa-keypair-for-jwt-authentication-flow)
- [Applying custom route prefix](#applying-custom-route-prefix)
- [Activate/Deactivate line items](cli/modify-entity-line-item-change-state-command.md)
- [Change line item availability dates](cli/modify-entity-line-item-change-dates-command.md)
- [Configuring line item updater webhook](features/update-line-items-webhook.md)
- [Profiling with Blackfire](blackfire.md)

## Environment variables

The main configuration file is `.env`, located in root folder.

| Variable | Description |
| -------- |:------------|
| `APP_ENV` | Application environment [Values: `dev`, `docker`, `test`, `prod`] |
| `APP_DEBUG` | Application debug mode [Values: `true`, `false`] |
| `APP_SECRET` | Application secret (use a secure random value, not a passphrase) |
| `APP_ROUTE_PREFIX` | To apply custom API route prefix [default: `/api` ]. More information [here](#applying-custom-route-prefix). |
| `DATABASE_URL` | Database connection string. Supported formats are described [here](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html#connecting-using-a-url). |
| `JWT_SECRET_KEY` | Path to RSA private key for JWT authentication flow |
| `JWT_PUBLIC_KEY` | Path to RSA public key for JWT authentication flow |
| `JWT_PASSPHRASE` | Passphrase for JWT keypair |
| `JWT_ACCESS_TOKEN_TTL` | TTL for JWT access token in seconds |
| `JWT_REFRESH_TOKEN_TTL` | TTL for JWT refresh token in seconds |
| `CORS_ALLOW_ORIGIN` | Allowed origin domain for cross-origin resource sharing. Example: `^https?://test-taker-portal.com$` |
| `REDIS_DOCTRINE_CACHE_HOST` | Redis host for doctrine cache storage. |
| `REDIS_DOCTRINE_CACHE_PORT` | Redis port for doctrine cache storage. |
| `REDIS_JWT_CACHE_HOST` | Redis host for JWT cache storage. |
| `REDIS_JWT_CACHE_PORT` | Redis port for JWT cache storage. |
| `CACHE_TTL_GET_USER_WITH_ASSIGNMENTS` | Cache TTL (in seconds) for caching individual users with assignments. |
| `CACHE_TTL_LTI_INSTANCES` | Cache TTL (in seconds) for caching entire collection of LTI instances. |
| `CACHE_TTL_LINE_ITEM` | Cache TTL (in seconds) for caching individual line items. |
| `MESSENGER_TRANSPORT_DSN` | Messenger transport DSN for [asynchronous cache warmup](cli/user-cache-warmer-command.md#asynchronous-cache-warmup-with-amazon-sqs). |
| `WEBHOOK_BASIC_AUTH_USERNAME` | Basic auth username for [webhook](features/update-line-items-webhook.md). |
| `WEBHOOK_BASIC_AUTH_PASSWORD` | Basic auth password for [webhook](features/update-line-items-webhook.md). |
| `APP_API_KEY` | API key used by [Lambda Assignment Manager](https://github.com/oat-sa/lambda-assignment-manager) to access bulk API endpoints. |
| `ASSIGNMENT_STATE_INTERVAL_THRESHOLD` | Threshold for assignment garbage collection. [Example: `P1D`] Supported formats can be found [here](http://php.net/manual/en/dateinterval.format.php). |

**Note: LTI specific variables can be found [here](features/lti.md).**
       
## Application setup steps (production)

1. Configure all application related environment variables in `.env` file described [here](#environment-variables).

1. Configure all LTI related environment variables in `.env` file described [here](features/lti.md).

1. Optimize configuration file with [Composer](https://getcomposer.org/):

    ```shell script
    $ sudo -u www-data composer dump-env prod
    ```

1. Install application dependencies:

    ```shell script
    $ sudo -u www-data composer install --no-dev --no-scripts --optimize-autoloader
    ```

1. Clear application cache:

    ```shell script
    $ sudo -u www-data bin/console clear:cache
    ```

1. Generate RSA keys for JWT authentication flow

    Please refer to [Generate RSA keypair for JWT authentication flow](#generate-rsa-keypair-for-jwt-authentication-flow) section of this document. 

1. Verify application and PHP settings:

    ```shell script
    $ sudo -u www-data bin/console about
    ```

1. Clear Doctrine caches:

    ```shell script
    $ sudo -u www-data bin/console doctrine:cache:clear-metadata
    $ sudo -u www-data bin/console doctrine:cache:clear-query
    $ sudo -u www-data bin/console doctrine:cache:clear-result
    ```

1. Ensure production settings:

    ```shell script
    $ sudo -u www-data bin/console doctrine:ensure-production-settings
    ```

1. Create database schema:

    ```shell script
    $ sudo -u www-data bin/console doctrine:database:create
    ``` 

1. Ensure application is healthy by calling the healthcheck API endpoint:

    ```shell script
    $ curl -sb -H https://{APPLICATION_URL}/api/v1
    ```
   
   Response should be something like this:
   
   ```json
    {
       "isDoctrineConnectionAvailable": true,
       "isDoctrineCacheAvailable": true
    }
    ```
   
 1. Execute LTI instance ingestion (Only in case of [LTI 1.1.1](features/lti.md#lti-111))
 
    Documentation: [LTI instance ingester command](cli/lti-instance-ingester-command.md).
    
 1. Execute line item ingestion
 
    Documentation: [Line item ingester command](cli/line-item-ingester-command.md).
    
1. Execute user ingestion

    Documentation: [User ingester command](cli/user-ingester-command.md).
    
1. Execute assignment ingestion

    Documentation: [Assignment ingester command](cli/assignment-ingester-command.md).
 
 1. Warm up LTI instance cache
 
    Documentation: [LTI instance cache warmer command](cli/lti-instance-cache-warmer-command.md).
    
1. Warm up line item cache

    Documentation: [Line item cache warmer command](cli/line-item-cache-warmer-command.md).

1. Warm up user cache

    Documentation: [User cache warmer command](cli/user-cache-warmer-command.md).

## Generate RSA keypair for JWT authentication flow

To generate private key:

```shell script
$ openssl genpkey -aes-256-cbc -algorithm RSA -out config/secrets/prod/jwt_private.pem
```

Make sure you update the `JWT_PASSPHRASE` environment variable with the passphrase of your choice.

To generate public key:

```shell script
$ openssl pkey -in config/secrets/prod/jwt_private.pem -out config/secrets/prod/jwt_public.pem -pubout
```

## Applying custom route prefix

Custom route prefix can be defined via `APP_ROUTE_PREFIX` application environment. 

**Please make sure to include the leading slash character, but _NO_ trailing slash.**

Example:

```dotenv
APP_ROUTE_PREFIX=/api
```

To apply the changes, you need to clear the application cache:

```shell script
$ sudo -u www-data bin/console cache:clear
```

To verify the changes:

```shell script
$ sudo -u www-data bin/console debug:router
```
