# Assignment ingester command

[AssignmentIngesterCommand](../../src/Command/Ingester/AssignmentIngesterCommand.php) is responsible for ingesting `assignment` data into the application.

> Prerequisites: [user ingestion](user-ingester-command.md) and [line item ingestion](line-item-ingester-command.md) must be done before starting assignment ingestion.

- [Usage](#usage)
    - [Main arguments](#main-arguments)
    - [Main options](#main-options)
- [CSV file format](#csv-file-format)
- [Examples](#examples)

## Usage
```shell script
$ sudo -u www-data bin/console roster:ingest:assignment <path> [--storage=local] [--delimiter=1000] [--batch=1000]
```

### Main arguments

| Argument | Description                                                |
| ---------|:-----------------------------------------------------------|
| path     | Relative path to the file [example: `var/assignments.csv`] |

### Main options

| Option          | Description                                                                                                 |
| ----------------|:------------------------------------------------------------------------------------------------------------|
| -s, --storage   | Filesystem storage identifier [default: `local`] ([Storage registry documentation](../storage-registry.md)) |
| -d, --delimiter | CSV delimiter [default: `,`]                                                                                |
| -b, --batch     | Batch size [default: `1000`]                                                                                |
| -f, --force     | To apply database modifications or not [default: `false`]                                                   |

For the full list of options please refer to the helper option:
```shell script
$ sudo -u www-data bin/console roster:ingest:assignment -h
```

## CSV file format

Here is an example csv structure: 

```csv
username,lineItemSlug
user_1,slug_1
user_2,slug_1
user_3,slug_2
user_4,slug_2
```

| Column | Description |
|--------|-------------|
| `username` | Unique identifier of the user. |
| `lineItemSlug` | Slug (external identifier) of the line item the created assignment will be linked to. |

## Examples

Ingesting assignments from default (`local`) storage with custom batch size:
```shell script
$ sudo -u www-data bin/console roster:ingest:assignment /path/to/file.csv --batch=10000 --force
```

Ingesting assignments from default (`local`) storage with custom column delimiter (`;`):
```shell script
$ sudo -u www-data bin/console roster:ingest:assignment /path/to/file.csv --delimiter=; --force
```

Ingesting assignments from a custom storage:
```shell script
$ sudo -u www-data bin/console roster:ingest:assignment /path/to/file.csv --storage=myCustomStorage --force
```

> For configuring custom storages, please check the [Storage registry documentation](../storage-registry.md).
