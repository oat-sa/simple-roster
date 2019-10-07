# Simple-Roster

>REST back-end service that handles authentication and eligibilities.

## Table of Contents

- [Installation](#installation)
- [API documentation](#api-documentation)
- [CLI documentation](#cli-documentation)
- [DevOps documentation](docs/devops/devops-documentation.md)
- [Blackfire usage](#blackfire-usage)
- [Testing](#testing)
- [Static code analysis with PHPStan](#static-code-analysis-with-phpstan)

## Development environment

The application comes with a built-in containerized development environment built on top of [OAT Docker Stack](https://github.com/oat-sa/docker-stack). 
In order to install it, follow the installation steps in it's [README](https://github.com/oat-sa/docker-stack#installation) file.

Set up docker containers:

```bash
$ docker-compose up -d
```

## Code quality standards

todo

## REST documentation

todo

## CLI documentation

todo

## Deployment

todo

## Contributing

todo

## Installation

#### Build in server usage

To run the application using PHP's built-in web server (or [Configure your Web Server](https://symfony.com/doc/current/setup/web_server_configuration.html)):

```bash
 $ bin/console server:start
```

#### Docker usage

You must have [docker](https://docs.docker.com/) and [docker-compose](https://docs.docker.com/compose/install/) installed.

This project is built on top of **OAT Docker Stack**. In order to install it, follow the installation steps in it's README file: 

[https://github.com/oat-sa/docker-stack#installation](https://github.com/oat-sa/docker-stack#installation)

Set up docker containers:

```bash
$ docker-compose up -d
```

This project provides a ready to use docker stack with:
- php fpm 7.2
- nginx
- postgres (container persistent storage)
- redis (containers persistent storage)
    - for doctrine data
    - and sessions storage
- blackfire

## API documentation

You can:
- find the **openapi v3** documentation in [openapi/api_v1.yml](openapi/api_v1.yml) file,
- use [https://editor.swagger.io/](https://editor.swagger.io/) to visualize it.

## CLI documentation

Available commands:

| Command | Description | Documentation |
| ------------- |:-------------|:-------|
| `roster:ingest` | Data ingestion (infrastructures, line items, users) | [docs/cli/ingester-command.md](docs/cli/ingester-command.md) |
| `roster:native-ingest:user` | Native user ingestion | [docs/cli/native-user-ingester-command.md](docs/cli/native-user-ingester-command.md) |
| `roster:garbage-collector:assignment` | Assignment garbage collection | [docs/cli/assignment-garbage-collector-command.md](docs/cli/assignment-garbage-collector-command.md) |
| `roster:doctrine-result-cache:warmup` | Doctrine result cache warmer | [docs/cli/doctrine-result-cache-warmer-command.md](docs/cli/doctrine-result-cache-warmer-command.md) | 
| `roster:assignments:bulk-cancel` | Assignment bulk cancellation | [docs/cli/assignment-bulk-cancellation-command.md](docs/cli/assignment-bulk-cancellation-command.md) |
| `roster:assignments:bulk-create` | Assignment bulk creation | [docs/cli/assignment-bulk-creation-command.md](docs/cli/assignment-bulk-creation-command.md) |

## Blackfire usage

If you need to use blackfire, you can simply edit the `.env` file settings with your blackfire credentials.

```yaml
BLACKFIRE_SERVER_ID=<your_backfire_id>
BLACKFIRE_SERVER_TOKEN=<your_backfire_secret>
```

## Testing

#### Test suites

You can run all tests suites with:

 ```bash
 $ bin/phpunit [--coverage-html=coverage]
 ```
 
 #### Mutation - Infection
 
 You can run test mutations with:
 
 ```bash
 $ vendor/bin/infection
 ```
 
## Static code analysis with PHPStan

```bash
$ vendor/bin/phpstan analyse --level=max
```
