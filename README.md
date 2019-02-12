# Simple-Roster
REST back-end service that handles authentication and eligibilities

Installation
------------

_Useful links_
- [Using Vagrant/Homestead](https://symfony.com/doc/current/setup/homestead.html)
- [Setting up or Fixing File Permissions](https://symfony.com/doc/current/setup/file_permissions.html)

## DEV environment

```bash
 $ composer install
```

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

## Vocabulary

### General
[StorageInterface](src/Storage/StorageInterface.php) communicates with NoSQL storage in terms of raw data rows. For now it can only use simple keys (you cannot use DynamoDB complex primary key). Always puts a new value without checking for existence. The checks should be done outside if necessary.

[Model](src/Model/ModelInterface.php) just represents some business data. It does not know of anything.

[ModelValidator](src/Validation/ModelValidator.php) validates models. It'sSetup with Docker based on Symfony/Validator.

[ItemManager](src/ODM/ItemManager.php) is aware of models and talks to StorageInterface. Uses Symfony/Serializer component (Normalizer interface) to turn Models into arrays before handing over them to the [StorageInterface](src/Storage/StorageInterface.php).

### Ingesting

[AbstractIngester](src/Ingesting/Ingester/AbstractIngester.php) manages the entire ingesting business scenario. It ties together a specific ModelStorage, a RowToModelMapper and a Validator.

[Ingesting Command](src/Command/Ingesting/AbstractIngestCommand.php) is a CLI entry point for ingesting data. It just manages CLI input and output.

Full documentation on how to ingest data can be found here: [docs/ingest.md](docs/ingest.md)

### Garbage collection

[AssignmentGarbageCollectorCommand](src/Command/AssignmentGarbageCollectorCommand.php) is responsible for transitioning all stuck assignments from `started` state to `completed` state.
For further details plese check [docs/assignment-garbage-collector.md](docs/assignment-garbage-collector.md)

Testing
-------

 ```bash
 $ bin/phpunit
 ```