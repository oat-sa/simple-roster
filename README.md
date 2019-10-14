# Simple-Roster

>REST back-end service that handles authentication and eligibilities.

## Table of Contents

- [Development environment](#development-environment)
- [API documentation](#api-documentation)
- [CLI documentation](#cli-documentation)
- [DevOps documentation](docs/devops/devops-documentation.md)
- [Blackfire usage](#blackfire-usage)
- [Testing](#testing)
- [Static code analysis with PHPStan](#static-code-analysis-with-phpstan)

## Development environment

### Docker installation

The application comes with a built-in containerized development environment built on top of [OAT Docker Stack](https://github.com/oat-sa/docker-stack). 
In order to install it please follow the installation steps in it's [README](https://github.com/oat-sa/docker-stack#installation) file.

The environment is pre-configured with the `.env.docker` file, so all you have to do is to set up the containers:

```bash
$ docker-compose up -d
```

The application will be available on `https://simple-roster.docker.localhost` DNS host.

**Note:** If your system cannot resolve `.docker.localhost` domain, you might want to check [this article](https://github.com/oat-sa/docker-stack#how-to-redirect-dockerlocalhost-dns-queries-to-localhost) about how to redirect `.docker.localhost` DNS queries to localhost.

### Custom installation

If you don't want to use docker, you have to create a local copy of the `.env` file

```bash
$ cp .env .env.local
```

and then to define the environment variables according to your local environment, such as redis DNS, database url, etc. 

To run the application with PHP's built-in web-server just launch:

```bash
 $ bin/console server:start
```

**Note:** If you want to run the application using your own web-server, please refer to Symfony's official [documentation](https://symfony.com/doc/current/setup/web_server_configuration.html).

## Development guidelines

todo

### Coding conventions

todo

### Code quality standards

todo

### Test metrics

todo

## REST documentation

todo

## CLI documentation

todo

## Deployment

todo

## Contributing

todo

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
