# Simple-Roster

REST back-end service that handles authentication and eligibilities.

## Installation

```bash
 $ composer install
```

## Documentation

### Api documentation

You can find an openapi v3 documentation in [openapi/api_v1.yml](openapi/api_v1.yml) file.

### CLI documentation

- **Data ingestion** : see [docs//cli/ingester-command.md](docs/cli/ingester-command.md)
- **Garbage collection** (assignments) : see [docs//cli/assignment-garbage-collector-command.md](docs/cli/assignment-garbage-collector-command.md)

## Development

### Build in server usage

To run the application using PHP's built-in web server (or [Configure your Web Server](https://symfony.com/doc/current/setup/web_server_configuration.html)):

```bash
 $ bin/console server:start
```

### Docker usage

This project provides a ready to use docker stack with:
- php fpm 7.2
- nginx
- postgres (container persistent storage)
- redis (container persistent storage)
- blackfire

You must have [docker](https://docs.docker.com/) and [docker-compose](https://docs.docker.com/compose/install/) installed.

Start up the docker stack, from the root folder:

```bash
 $ docker-compose up -d
```

Resources:
- application is exposed on port **80**
- redis is exposed on port **6379**
- blackfire is exposed on port **8707**

### Blackfire usage

If you need to use blackfire, you can simply edit the `.env` file settings with your blackfire credentials.

```yaml
BLACKFIRE_SERVER_ID=<your_backfire_id>
BLACKFIRE_SERVER_TOKEN=<your_backfire_secret>
```

## Tests
-------

You can run tests with:

 ```bash
 $ bin/phpunit [--coverage-html=coverage]
 ```