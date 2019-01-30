## Ingesting process

To ingest data, use CLI commands inside [src/Command/Ingesting](src/Command/Ingesting).

Ingested data should be put a CSV file. CSV is the only supported format.

You can use a local file or a file in Amazon S3.

## Commands

In a general form the command is:

```
bin/console roster:{local|s3}-ingest --data-type=infrastructures|line-items|user-assignments {source options}
```

Source options depend on a chosen format.

## Sources
No matter the source, `--delimiter` option can be used to specify which delimiter is used in a given CSV file. By default it's set to `,`

### Local file
In case the source is a local file, the file path needs to be provided as a single argument.

```bash
$ bin/console roster:local-ingest [options] [--] <filename>
```

### Amazon S3
In case the source is S3, you need to provide the bucket name is the first argument and the object name as the second argument.

```bash
$ bin/console roster:s3-ingest [options] [--] <s3_bucket> <s3_object>
```

## Dry run

Every command runs in `dry-run` mode by default so nothing in the real storage will be changed.
**Use option `--wet-run` or `-w` if you want real writing into the storage.**

## Data types and CSV formats
Please follow these data structures. In case at least one line of a CSV file is considered as invalid, the entire process terminates.

### Infrastructures

```bash
$ bin/console roster:local-ingest /path/to/infrastructures.csv --data-type=infrastructures [-w]
```

CSV fields for infrastructures are: 
1. `id` string, **required** `must be unique`
2. `lti_director_link` string, **required**
3. `key` string, **required**
4. `secret` string, **required**

Example:

```csv
"some infrastructure 1", "some_lti_director_link.com", "key", "super secret"
"some infrastructure 2", "some_lti_director_link.com", "qwerty", "qwerty12345"
```

In case an infrastructure with the same `id` already exists, this line of CSV will be skipped (not updated!).

### Line items
```bash
$ bin/console roster:local-ingest /path/to/line-items.csv --data-type=line-items [-w]
```

CSV fields for line items are: 
1. `tao_uri` string, **required** `must be unique`
2. `label` string, **required**
3. `infrastructure_id` string, **required** `infrastructure must be already ingested`
4. `start_date_time` string, _optional_ format: 2019-01-26 18:30:00
5. `end_date_time` string, _optional_ format: 2019-01-26 18:30:00

Example:
```csv
"http://tao.installation/delivery_1.rdf", "some label", "some infrastructure 1",,
"http://tao.installation/delivery_2.rdf", "some label2", "some infrastructure 2", "2019-01-26 10:00:00", "2019-02-20 18:00:00",
```

In case a line-item with the same `tao_uri` already exists, this line of CSV will be skipped (not updated!).
In case a `infrastructure_id` refers to a non-existent infrastructure, this line will be considered as invalid, which entail the entire process termination.

### User and assignments
```bash
$ bin/console roster:local-ingest /path/to/users.csv --data-type=users-assignments [-w]
```

CSV fields for users and their assignments are: 
1. `username` string, **required**, `must be unique`
2. `password` string `plain`
3. `assignment 1 line item tao URI` string `optional`
4. `assignment 2 line item tao URI` string `optional`
5. `assignment 3 line item tao URI` string `optional`
...
N. `assignment N line item tao URI` string `optional`

This structure can work with any amount of CSV fields >2. All fields starting from third are assignments list. 
In case a user with the same `username` already exists, those assignments not existing in the storage and specified in CSV will be inserted. 
In this case the password will not be updated and can even be omitted in CSV if the goal is updating the assignment list.
Each assignment must have existing line item. Otherwise, the error will occur.