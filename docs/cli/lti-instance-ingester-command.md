# LTI instance ingester command

[LtiInstanceIngesterCommand](../../src/Command/Ingester/LtiInstanceIngesterCommand.php) is responsible for ingesting `lti instance` 
data into the application for LTI 1.1 launches.

- [Usage](#usage)
    - [Main arguments](#main-arguments)
    - [Main options](#main-options)
- [CSV file format](#csv-file-format)
- [Examples](#examples)

## Usage
```shell script
$ sudo -u www-data bin/console roster:ingest:lti-instance <path> [--storage=local] [--delimiter=1000] [--batch=1000]
```

### Main arguments

| Argument | Description                                                  |
| ---------|:-------------------------------------------------------------|
| path     | Relative path to the file [example: `var/lti-instances.csv`] |

### Main options

| Option          | Description                                                                                                 |
| ----------------|:------------------------------------------------------------------------------------------------------------|
| -s, --storage   | Filesystem storage identifier [default: `local`] ([Storage registry documentation](../storage-registry.md)) |
| -d, --delimiter | CSV delimiter [default: `,`]                                                                                |
| -b, --batch     | Batch size [default: `1000`]                                                                                |
| -f, --force     | To apply database modifications or not [default: `false`]                                                   |

For the full list of options please refer to the helper option:
```shell script
$ sudo -u www-data bin/console roster:ingest:lti-instance -h
```

## CSV file format

Here is an example csv structure: 

```csv
label,ltiLink,ltiKey,ltiSecret],
infra_1,http://infra_1.com,key1,secret1
infra_2,http://infra_2.com,key2,secret2
infra_3,http://infra_3.com,key3,secret3
infra_4,http://infra_4.com,key4,secret4
infra_5,http://infra_5.com,key5,secret5
```

| Column | Description |
|--------|-------------|
| `label` | Label of the LTI instance. |
| `ltiLink` | Unique LTI link used by LTI 1.1 launch. |
| `ltiKey` | LTI key used by LTI 1.1 launch. |
| `ltiSecret` | LTI secret used by LTI 1.1 launch. |

## Examples

Ingesting LTI instances from default (`local`) storage with custom batch size:
```shell script
$ sudo -u www-data bin/console roster:ingest:lti-instance /path/to/file.csv --batch=10000 --force
```

Ingesting LTI instances from default (`local`) storage with custom column delimiter (`;`):
```shell script
$ sudo -u www-data bin/console roster:ingest:lti-instance /path/to/file.csv --delimiter=; --force
```

Ingesting LTI instances from a custom storage:
```shell script
$ sudo -u www-data bin/console roster:ingest:lti-instance /path/to/file.csv --storage=myCustomStorage --force
```

> For configuring custom storages, please check the [Storage registry documentation](../storage-registry.md).
