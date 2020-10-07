# Native user data ingestion

[NativeUserIngesterCommand](../../src/Command/Ingester/Native/NativeUserIngesterCommand.php) is responsible for ingesting `user` data faster than with regular ingester.

- [Usage](#usage)
    - [Main arguments](#main-arguments)
    - [Main options](#main-options)
- [Use cases](#use-cases)
    - [User ingestion with single assignments](#user-ingestion-with-single-assignments)
    - [User ingestion with multiple assignments](#user-ingestion-with-multiple-assignments)
- [Examples](#examples)

## Usage
```shell script
$ sudo -u www-data bin/console roster:native-ingest:user <source> <path> [--batch=1000]
```

### Main arguments

| Option | Description |
| ------------- |:-------------|
| source | Can be **local**, or **s3** |
| path   |  Local or S3 path to ingest data from |

### Main options

| Option | Description |
| ------------- |:-------------|
| -d, --delimiter | CSV delimiter [default: `,`] |
| -c, --charset | CSV source charset [default: `UTF-8`] |
| -b, --batch | Batch size [default: `1000`] |
| -f, --force | To apply database modifications or not [default: `false`] |

For the full list of options please refer to the helper option:
```shell script
$ sudo -u www-data bin/console roster:native-ingest:user -h
```

## Use cases

### User ingestion with single assignments

This is basic use case, when every user is represented as a single row in the source CSV file. During ingestion for every user 
a single assignment will be created based on the line item `slug` defined.

Here is an example csv structure: 

> Note: `groupId` column in optional depending of the chosen [LTI load balancing strategy](../devops/devops-documentation.md#lti-load-balancing-strategy).

```csv
username,password,slug,groupId
user_1,password_1,lineItem_slug_1,group_1
user_2,password_2,lineItem_slug_1,group_2
user_3,password_3,lineItem_slug_2,group_2
user_4,password_4,lineItem_slug_2,group_3
```

### User ingestion with multiple assignments

It is also possible to create multiple assignments per user by slightly altering the input csv file.

Here is an example csv structure:

> Note: `groupId` column in optional depending of the chosen [LTI load balancing strategy](../devops/devops-documentation.md#lti-load-balancing-strategy).


```csv
username,password,slug,groupId
user_1,password_1,lineItem_slug_1,group_1
user_1,password_1,lineItem_slug_2,group_1
user_1,password_1,lineItem_slug_3,group_1
user_2,password_2,lineItem_slug_2,group_1
user_2,password_2,lineItem_slug_3,group_1
```

Despite the fact that the same users are represented multiple times in the file, only the first occurrence of each user 
will be ingested. However the line item slugs will be taken into account for each and every row, resulting in multiple
assignment creation for each user.

## Examples

Native ingesting users from a local CSV file:
```shell script
$ sudo -u www-data bin/console roster:native-ingest:user local /path/to/file.csv --force
```

Native ingesting users from a local UTF-16LE encoded CSV file:
```shell script
$ sudo -u www-data bin/console roster:native-ingest:user local /path/to/file.csv --charset="UTF-16LE" --force
```

Native ingesting users from a S3 bucket CSV file:
```shell script
$ sudo -u www-data bin/console roster:native-ingest:user s3 bucket/path/to/file.csv --force
```

Native ingesting users from a S3 bucket CSV file with a different batch size:
```shell script
$ sudo -u www-data bin/console roster:native-ingest:user s3 bucket/path/to/file.csv --batch=500 --force
```
