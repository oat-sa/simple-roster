# Line item ingester command

[LineItemIngesterCommand](../../src/Command/Ingester/LineItemIngesterCommand.php) is responsible for ingesting `line item` data into the application.

- [Usage](#usage)
    - [Main arguments](#main-arguments)
    - [Main options](#main-options)
- [CSV file format](#csv-file-format)
- [Examples](#examples)

## Usage
```shell script
$ sudo -u www-data bin/console roster:ingest:line-item <path> [--storage=local] [--delimiter=1000] [--batch=1000]
```

### Main arguments

| Argument | Description                                               |
| ---------|:----------------------------------------------------------|
| path     | Relative path to the file [example: `var/line-items.csv`] |

### Main options

| Option          | Description                                                                                                   |
| ----------------|:--------------------------------------------------------------------------------------------------------------|
| -s, --storage   | Filesystem storage identifier [default: `default`] ([Storage registry documentation](../storage-registry.md)) |
| -d, --delimiter | CSV delimiter [default: `,`]                                                                                  |
| -b, --batch     | Batch size [default: `1000`]                                                                                  |
| -f, --force     | To apply database modifications or not [default: `false`]                                                     |

For the full list of options please refer to the helper option:
```shell script
$ sudo -u www-data bin/console roster:ingest:line-item -h
```

## CSV file format

Here is an example csv structure: 

```csv
uri,label,slug,startTimestamp,endTimestamp,maxAttempts
http://taoplatform.loc/delivery_1.rdf,label1,gra13_ita_1,1546682400,1546713000,1
http://taoplatform.loc/delivery_2.rdf,label2,gra13_ita_2,1546682400,1546713000,2
http://taoplatform.loc/delivery_3.rdf,label2,gra13_ita_3,1546682400,1546713000,1
http://taoplatform.loc/delivery_4.rdf,label4,gra13_ita_4,1546682400,1546713000,2
http://taoplatform.loc/delivery_5.rdf,label5,gra13_ita_5,1546682400,1546713000,1
http://taoplatform.loc/delivery_6.rdf,label6,gra13_ita_6,1546682400,1546713000,2
```

| Column | Description |
|--------|-------------|
| `uri` | Delivery URI of the line item. |
| `label` | Label of the line item. |
| `slug` | Unique slug (external identifier) of the line item. |
| `startTimestamp` | Starting date of line item as [Unix epoch timestamp](https://www.epochconverter.com/clock). |
| `endTimestamp` | Ending date of line item as [Unix epoch timestamp](https://www.epochconverter.com/clock). |
| `maxAttempts` | Maximum available test taking attempts (`0` = infinite attempts). |

## Examples

Ingesting line items from default (`local`) storage with custom batch size:
```shell script
$ sudo -u www-data bin/console roster:ingest:line-item /path/to/file.csv --batch=10000 --force
```

Ingesting line items from default (`local`) storage with custom column delimiter (`;`):
```shell script
$ sudo -u www-data bin/console roster:ingest:line-item /path/to/file.csv --delimiter=; --force
```

Ingesting line items from a custom storage:
```shell script
$ sudo -u www-data bin/console roster:ingest:line-item /path/to/file.csv --storage=myCustomStorage --force
```

> For configuring custom storages, please check the [Storage registry documentation](../storage-registry.md).
