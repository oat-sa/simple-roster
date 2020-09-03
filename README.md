# Simple-Roster

>REST back-end service intending to mimic a simplified version of OneRoster IMS specification.

![current version](https://img.shields.io/badge/version-1.5.0-green.svg)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html)
![coverage](https://img.shields.io/badge/coverage-100%25-green.svg)

`OneRoster` solves a school’s need to securely and reliably exchange roster information, course materials and grades between systems. 
OneRoster supports the commonly used .csv mode for exchange and the latest real-time web services mode known as REST.  

To learn more about `OneRoster`, please refer to the official specification at [IMS Global](https://www.imsglobal.org/activity/onerosterlis).

## Table of Contents

- [Development environment](#development-environment)
    - [Docker environment](#docker-environment)
    - [Local installation](#local-installation)
- [Development guidelines](#development-guidelines)
    - [Code quality standards](#code-quality-standards)
- [OpenAPI documentation](#openapi-documentation)
- [CLI documentation](#cli-documentation)
- [Production environment](#production-environment)

## Development environment

### Docker environment

The application comes with a built-in containerized development environment built on top of [OAT Docker Stack](https://github.com/oat-sa/docker-stack). 
In order to install it please follow the installation steps in it's [README](https://github.com/oat-sa/docker-stack#installation) file.

Then copy the `.env.dist` file to `.env` file.

```bash
$ cp .env.dist .env
```

Then add your Composer settings such as path to your `COMPOSER_HOME` and `COMPOSER_AUTH` GitHub credentials.

```dotenv
COMPOSER_AUTH={"github-oauth":{"github.com":"your token here"}}
COMPOSER_HOME=~/.composer
```

The environment is pre-configured with the `.env.docker` file, so all you have to do is to set up the containers:

```bash
$ docker-compose up -d
```

The application will be available at `https://simple-roster.docker.localhost` DNS host.

**Note:** If your system cannot resolve `.docker.localhost` domain, you might want to check [this article](https://github.com/oat-sa/docker-stack#how-to-redirect-dockerlocalhost-dns-queries-to-localhost) about how to redirect `.docker.localhost` DNS queries to your localhost.

### Local installation

If you don't want to use docker, you have to create a local copy of the `.env` file:

```bash
$ cp .env.dist .env
```

and then to define the environment variables according to your local environment, such as redis DNS, database url, etc. 

To see the full list of available environment variables please refer to the [devops documentation](docs/devops/devops-documentation.md).

To run the application with PHP's built-in web-server just launch:

```bash
 $ bin/console server:start
```

**Note:** If you want to run the application using your own web-server, please refer to Symfony's official [documentation](https://symfony.com/doc/current/setup/web_server_configuration.html) about web server configuration.

## Development guidelines

For development workflow we are using [Gitflow Workflow](https://www.atlassian.com/git/tutorials/comparing-workflows/gitflow-workflow).
Please make sure you understand how it works before jumping into any developments.

The application is built on top of the latest version of [Symfony](https://symfony.com/) PHP framework, and intends to follow it's best practices.

The project respects and follows the [PSR-12](https://www.php-fig.org/psr/psr-12/) extended coding style recommendations.
Please make sure you have your [PHP CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) configured properly in your IDE. 

### Code quality standards

The application has strict code quality requirements which must be respected in every Pull Request without exception.

#### General rules

For enforcing general design principles such as clean code, code size, code complexity and so on we are using [PHP Mess Detector](https://phpmd.org/). 
Please make sure you have it configured properly in your IDE.

#### Static code analysis

For static code analysis we are using [PHPStan](https://github.com/phpstan/phpstan). The expected strictness level is `max`.

Please make sure you run the following command every time before you push your changes:

```bash
$ vendor/bin/phpstan analyse --level=max
```

#### Test metrics

The expected level of combined test coverage (unit, integration and functional) is `100%`, without exception.

Please make sure you have full test coverage before you push your changes:

 ```bash
 $ vendor/bin/phpunit --coverage-text
 ```

We are also have minimum mutation score indicator threshold that must be respected.

| Mutation metric                | Threshold |
| -------------------------------| --------- |
| Mutation Score Indicator (MSI) | 85%       |

Please make sure you run the following command every time before you push your changes:

```bash
$ vendor/bin/infection
```

To learn more about mutation testing, please refer to the official documentation of [Infection](https://infection.github.io/) mutation testing framework.

## OpenAPI documentation

The application uses [OpenAPI 3.0](https://swagger.io/specification/) specification to describe it's REST interface.
You can find our OpenAPI documentation [here](openapi/api_v1.yml).

Please use [Swagger editor](https://editor.swagger.io/) to visualize it.

## CLI documentation

The application currently offers the following CLI commands:

| Command | Description | Documentation |
| ------------- |:-------------|:-------|
| `roster:ingest` | Data ingestion (infrastructures, line items, users) | [link](docs/cli/ingester-command.md) |
| `roster:native-ingest:user` | Native user ingestion | [link](docs/cli/native-user-ingester-command.md) |
| `roster:garbage-collector:assignment` | Assignment garbage collection | [link](docs/cli/assignment-garbage-collector-command.md) |
| `roster:doctrine-result-cache:warmup` | Doctrine result cache warmer | [link](docs/cli/doctrine-result-cache-warmer-command.md) | 
| `roster:assignments:bulk-cancel` | Assignment bulk cancellation | [link](docs/cli/assignment-bulk-cancellation-command.md) |
| `roster:assignments:bulk-create` | Assignment bulk creation | [link](docs/cli/assignment-bulk-creation-command.md) |

## Production environment

For detailed application setup tests for production environment, please refer to the [DevOps documentation](docs/devops/devops-documentation.md).

