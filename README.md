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

### DynamoDB settings

- First download the local version of DynamoDB: [DynamoDB (Downloadable Version)](https://docs.aws.amazon.com/amazondynamodb/latest/developerguide/DynamoDBLocal.DownloadingAndRunning.html).
- Configure the AWS variables and any other env variables: copy `.env` into `.env.local`

```dotenv
    AWS_REGION=eu-west-1
    AWS_VERSION=latest
    AWS_KEY=
    AWS_SECRET=
    
    DYNAMODB_ENDPOINT=http://localhost:8000
```

- Deploy DynamoDB schema:

```bash
 $ bin/console roster:deploy:schema
```

### Server settings

To run the application using PHP's built-in web server (or [Configure your Web Server](https://symfony.com/doc/current/setup/web_server_configuration.html)):

```bash
 $ bin/console server:start
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

Testing
-------
 ```bash
 $ bin/phpunit
 ```