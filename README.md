# Simple-Roster

REST back-end service that handles authentication and eligibilities.

## Table of Contents
- [Installation](#installation)
- [Documentation](#documentation)
    - [API documentation](#api-documentation)
    - [CLI documentation](#cli-documentation)
    - [DevOps documentation](#devops-documentation)
- [Development](#development)
    - [Build in server usage](#build-in-server-usage)
    - [Docker usage](#docker-usage) 
    - [Blackfire usage](#blackfire-usage)
- [Tests](#tests)

## Installation

```bash
 $ composer install
```

## Documentation

#### API documentation

You can:
- find the **openapi v3** documentation in [openapi/api_v1.yml](openapi/api_v1.yml) file,
- use [https://editor.swagger.io/](https://editor.swagger.io/) to visualize it.

#### CLI documentation

Available commands:
- **Data ingestion** : see [docs/cli/ingester-command.md](docs/cli/ingester-command.md)
- **Native user data ingestion** : see [docs/cli/native-user-ingester-command.md](docs/cli/native-user-ingester-command.md)
- **Garbage collection** (assignments) : see [docs/cli/assignment-garbage-collector-command.md](docs/cli/assignment-garbage-collector-command.md)
- **Doctrine result cache warming** : see [docs/cli/doctrine-result-cache-warmer-command.md](docs/cli/doctrine-result-cache-warmer-command.md)

#### DevOps documentation

You can:
- find the **DevOps** documentation in [docs/devops/devops-documentation.md](docs/devops/devops-documentation.md)

## Development

#### Build in server usage

To run the application using PHP's built-in web server (or [Configure your Web Server](https://symfony.com/doc/current/setup/web_server_configuration.html)):

```bash
 $ bin/console server:start
```

#### Docker usage

This project provides a ready to use docker stack with:
- php fpm 7.2
- nginx
- postgres (container persistent storage)
- redis (containers persistent storage)
    - for doctrine data
    - and sessions storage
- blackfire

You must have [docker](https://docs.docker.com/) and [docker-compose](https://docs.docker.com/compose/install/) installed.

Start up the docker stack, from the root folder:

```bash
 $ docker-compose up -d
```

Resources:
- application is exposed on port **80**
- postgres is exposed on port **5432**
- redis for doctrine data is exposed on port **6379**
- redis for session data is exposed on port **6380**
- blackfire is exposed on port **8707**

#### Blackfire usage

If you need to use blackfire, you can simply edit the `.env` file settings with your blackfire credentials.

```yaml
BLACKFIRE_SERVER_ID=<your_backfire_id>
BLACKFIRE_SERVER_TOKEN=<your_backfire_secret>
```

#### Tests

You can run all tests suites with:

 ```bash
 $ bin/phpunit [--coverage-html=coverage]
 ```
 
 #### Mutation tests
 
 You can run test mutations with `Infection`:
 
 ```bash
 $ vendor/bin/infection
 ```
 
 
#### Static analysis with PHPStan
```bash
$ vendor/bin/phpstan analyse --level=max
```