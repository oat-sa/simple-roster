# Data ingestion

[IngesterCommand](../../src/Command/Ingester/IngesterCommand.php) is responsible for ingesting `lti-instance`, `line-item` and `user` data.

- [Usage](#usage)
    - [Main arguments](#main-arguments)
    - [Main options](#main-options)
- [Examples](#examples)

## Usage:
```shell script
$ sudo -u www-data bin/console roster:ingest <type> <path> <source> [--force]
```

#### Main arguments

| Argument | Description |
| ------------- |:-------------|
| type | Can be **lti-instance**, **line-item** or **user** |
| path | Local or S3 path to ingest data from |
| source | Can be **local**, or **s3** [default: `local`] |

#### Main options

| Option | Description |
| ------------- |:-------------|
| -d, --delimiter | CSV delimiter [default: `,`] |
| -c, --charset | CSV source charset [default: `UTF-8`] |
| -f, --force | To involve actual database modifications or not [default: `false`] |

For the full list of options please refer to the helper option:
```shell script
$  sudo -u www-data bin/console roster:ingest -h
```

## Examples

Dry run ingesting LTI instances from a local CSV file:
```shell script
$ sudo -u www-data bin/console roster:ingest lti-instance /path/to/file.csv
```

Ingesting LTI instances from a local CSV file:
```shell script
$ sudo -u www-data bin/console roster:ingest lti-instance /path/to/file.csv --force
```

Ingesting LTI instances from a local UTF-16LE encoded CSV file:
```shell script
$ sudo -u www-data bin/console roster:ingest lti-instance /path/to/file.csv --charset="UTF-16LE" --force
```

Ingesting line-items from a local CSV file:
```shell script
$ sudo -u www-data bin/console roster:ingest line-item /path/to/file.csv --force
```

Ingesting users from a S3 bucket CSV file:
```shell script
$ sudo -u www-data bin/console roster:ingest user bucket/path/to/file.csv s3 --force
```
