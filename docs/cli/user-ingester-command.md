# User ingester command

[UserIngesterCommand](../../src/Command/Ingester/UserIngesterCommand.php) is responsible for ingesting `user` data into the application.

- [Usage](#usage)
    - [Main arguments](#main-arguments)
    - [Main options](#main-options)
- [CSV file format](#csv-file-format)
- [Examples](#examples)

## Usage
```shell script
$ sudo -u www-data bin/console roster:ingest:user <path> [--storage=local] [--delimiter=1000] [--batch=1000]
```

### Main arguments

| Argument | Description                                          |
| ---------|:-----------------------------------------------------|
| path     | Relative path to the file [example: `var/users.csv`] |

### Main options

| Option          | Description                                                                                                 |
| ----------------|:------------------------------------------------------------------------------------------------------------|
| -s, --storage   | Filesystem storage identifier [default: `local`] ([Storage registry documentation](../storage-registry.md)) |
| -d, --delimiter | CSV delimiter [default: `,`]                                                                                |
| -b, --batch     | Batch size [default: `1000`]                                                                                |
| -f, --force     | To apply database modifications or not [default: `false`]                                                   |

For the full list of options please refer to the helper option:
```shell script
$ sudo -u www-data bin/console roster:ingest:user -h
```

## CSV file format

Here is an example csv structure: 

```csv
username,password,groupId
user_1,password_1,group_1
user_2,password_2,group_2
user_3,password_3,group_2
user_4,password_4,group_3
```

| Column | Description |
|--------|-------------|
| `username` | Unique identifier of the user. |
| `password` | Plain password of the user (will be encoded during ingestion). |
| `groupId` | For logical grouping of the users (optional, depends on chosen [LTI load balancing strategy](../devops-documentation.md#lti-load-balancing-strategy).) |

## Examples

Ingesting users from default (`local`) storage with custom batch size:
```shell script
$ sudo -u www-data bin/console roster:ingest:user /path/to/file.csv --batch=10000 --force
```

Ingesting users from default (`local`) storage with custom column delimiter (`;`):
```shell script
$ sudo -u www-data bin/console roster:ingest:user /path/to/file.csv --delimiter=; --force
```

Ingesting users from a custom storage:
```shell script
$ sudo -u www-data bin/console roster:ingest:user /path/to/file.csv --storage=myCustomStorage --force
```

> For configuring custom storages, please check the [Storage registry documentation](../storage-registry.md).
