# Storage registry

> Storage registry is an extra abstraction layer on top of [League/Flysystem](https://flysystem.thephpleague.com/v1/docs/) library
in order to be able to utilize usage of multiple different filesystem instances for example in csv ingestion commands.

- [Default configuration (local storage)](#default-configuration-local-storage)
- [How to configure an AWS S3 bucket storage](#how-to-configure-an-aws-s3-bucket-storage)
- [Storage usage in tests](#storage-usage-in-tests)
- [Other available storage adapters](https://github.com/thephpleague/flysystem-bundle#full-documentation)

## Default configuration (local storage)

By default there is only one storage enabled in `config/packages/flysystem.yaml` file, the `default` storage and it's using
the local filesystem:

```yaml
flysystem:
    storages:
        default.storage:
            adapter: 'local'
            options:
                directory: '%kernel.project_dir%'
```

It is pre-configured to use the project root as base directory.

## How to configure an AWS S3 bucket storage

In order to define an S3 bucket as storage first you need to define an S3 client service in the `config/services.yaml` file:

```yaml
services:
    aws.my_custom_service_id:
        class: Aws\S3\S3Client
        arguments:
            -   version: latest
                region: <your-aws-region>
                credentials:
                    key: <your-aws-access-key-id>
                    secret: <your-aws-secret-access-key>
        calls:
            - registerStreamWrapper: []
```

Then you need to define an s3 storage in `config/packages/flysystem.yaml` file:

```yaml
flysystem:
    storages:
        my_custom_storage_id.storage:
            adapter: 'aws'
            options:
                client: 'aws.my_custom_service_id'
                bucket: '<your-bucket-name>'
                prefix: '<your-optional-bucket-prefix>'
```

Then you can retrieve an S3 filesystem instance by using:

```php
<?php

declare(strict_types=1);

use OAT\SimpleRoster\Storage\StorageRegistry;

class MyService
{
    public function __construct(StorageRegistry $storageRegistry)
    {
        /** @var League\Flysystem\Filesystem $filesystem */
        $filesystem = $storageRegistry->getFilesystem('my_custom_storage_id');
    }
}
```

## Storage usage in tests

In functional / integration tests it should be avoided to use the local filesystem whenever it's possible by utilizing the `test` 
in-memory storage configured in `config/packages/test/flysystem.yaml` file:

```yaml
flysystem:
    storages:
        test.storage:
            adapter: 'memory'
```
