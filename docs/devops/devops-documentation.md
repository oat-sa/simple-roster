# DevOps Documentation

> DevOps related information for setting up / debug / maintain the application.

## Table of Contents
- [Environment variables](#environment-variables)
    - [Application related environment variables](#application-related-environment-variables)
    - [AWS related environment variables](#aws-related-environment-variables)
    - [LTI related environment variables](#lti-related-environment-variables)
    - [Blackfire related environment variables](#blackfire-related-environment-variables)
- [Application setup steps](#application-setup-steps)
- [LTI](#lti)
    - [LTI load balancer configuration](#lti-load-balancer-configuration)
    - [LTI load balancing strategy](#lti-load-balancing-strategy)
- [Applying custom route prefix](#applying-custom-route-prefix)
- [Useful commands](#useful-commands)
- [Application logs](#application-logs)
- [Production environment - checklist](https://symfony.com/doc/current/performance.html)

## Environment variables

The main configuration file is `.env`, located in root folder.

#### Application related environment variables

| Parameter | Description |
| ------------- |:-------------|
| APP_ENV | Application environment, `dev`, `prod` or `test` [default: `prod`] |
| APP_DEBUG | Application debug mode, [default: `false`] |
| APP_SECRET | Application secret |
| APP_API_KEY | Application API Key |
| APP_ROUTE_PREFIX | Application route prefix, [default: `/api/v1` ]. For details, follow: [Applying custom route prefix](#applying-custom-route-prefix)
| DATABASE_URL | Database url |
| REDIS_DOCTRINE_USER_CACHE_TTL | Doctrine User entity cache storage TTL [default: `3600`] |
| REDIS_DOCTRINE_CACHE_HOST | Redis host for doctrine cache storage |
| REDIS_DOCTRINE_CACHE_PORT | Redis port for doctrine cache storage |
| REDIS_SESSION_CACHE_HOST | Redis host for sessions cache storage |
| REDIS_SESSION_CACHE_PORT | Redis port for sessions cache storage |
| CORS_ALLOW_ORIGIN | Allowed CORS origin |
| ASSIGNMENT_STATE_INTERVAL_THRESHOLD | Threshold for assignment garbage collection [default: `P1D`] |
  
#### AWS related environment variables

| Parameter | Description |
| ------------- |:-------------|
| AWS_REGION | AWS Region [default: `eu-west-1`] |
| AWS_VERSION | AWS Version [default: `latest`] |
| AWS_KEY | AWS Key |
| AWS_SECRET | AWS Secret |
| AWS_S3_INGEST_BUCKET | AWS S3 bucket used for ingestion |
     
#### LTI related environment variables

| Parameter | Description |
| ------------- |:-------------|
| LTI_ENABLE_INSTANCES_LOAD_BALANCER | Whether the LTI link should be load balanced or not [default: `false`] |
| LTI_LAUNCH_PRESENTATION_RETURN_URL | Frontend LTI return link |
| LTI_LAUNCH_PRESENTATION_LOCALE | Defines the localisation of TAO instance [default: `en-EN`] |
| LTI_INSTANCE_LOAD_BALANCING_STRATEGY | Defines the [LTI load balancing strategy](#lti-load-balancing-strategy) [default: `username`] |
| LTI_OUTCOME_XML_NAMESPACE | Defines the LTI outcome XML namespace [default: `http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0`] |

#### Blackfire related environment variables

If you need to use blackfire, you can simply edit the `.env` file settings with your blackfire credentials.

```dotenv
BLACKFIRE_SERVER_ID=<your_backfire_id>
BLACKFIRE_SERVER_TOKEN=<your_backfire_secret>
```

## Application setup steps

- Install dependencies with Composer

```shell script
$ composer install --optimize-autoloader
```

- Optimize composer autoloader

```shell script
$ composer dump-autoload --optimize --no-dev --classmap-authoritative
```

- Create database with Doctrine

```shell script
$ sudo -u www-data bin/console doctrine:database:create
``` 

- Create database schema with Doctrine

```shell script
$ sudo -u www-data bin/console doctrine:schema:update --force
```

- If needed, drop database schema with Doctrine

```shell script
$ sudo -u www-data bin/console doctrine:schema:drop --force
```

## LTI

#### LTI load balancer configuration

The default map of load balancer for LTI instances is located in `config/packages/lti_instances.yaml`.

It can be overridden per instance (dev, prod) by dropping this file in `config/packages/<env>/lti_instances.yaml`.

The list of related environment variables can be found [here](#lti-related-environment-variables).

#### LTI load balancing strategy

There are two different load balancing strategies that can be applied. It's configurable through the 
`LTI_INSTANCE_LOAD_BALANCING_STRATEGY` environment variable in the `.env` file.

| Strategy | Description | LTI context id |
| -------------|-------------|-----------|
| username | Username based strategy (default)| `id` of `LineItem` of current `Assignment`|
| userGroupId | User group ID based strategy | `groupId` of `User` |

> **Note:** In order to apply the `userGroupId` strategy, the users must be ingested with `groupId` column specified, 
otherwise the ingestion will fail.

> **Note 2:** The `contextId` LTI request parameter is automatically adjusted based on the active load balancing strategy.

## Applying custom route prefix

Custom route prefix can be defined via `APP_ROUTE_PREFIX` application environment. 
If you do so, please make sure to include the leading slash character, but *NO* trailing slash.

Example:

```dotenv
APP_ROUTE_PREFIX=/api/v1
```

To apply the changes, you need to clear the application cache:

```shell script
$ sudo -u www-data bin/console cache:clear [--env=dev|prod]
```

To verify the changes:

```shell script
$ sudo -u www-data bin/console debug:router [--env=dev|prod]
```

## Useful commands

- Clear application cache

```shell script
$ sudo -u www-data bin/console cache:clear [--env=dev|prod]
```

- Warm-up Doctrine cache

```shell script
$ sudo -u www-data bin/console roster:doctrine-result-cache:warmup
```

- Refresh Doctrine metadata cache

```shell script
$ sudo -u www-data bin/console doctrine:cache:clear-metadata
```

- Refresh Doctrine query cache

```shell script
$ sudo -u www-data bin/console doctrine:cache:clear-query
```

- Refresh Doctrine result cache

```shell script
$ sudo -u www-data bin/console doctrine:cache:clear-result
```

## Application logs

Application logs can be found in `var/log/` folder.
