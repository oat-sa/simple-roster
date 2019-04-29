# Assignments - Bulk creation

The responsibility of [BulkCreateUsersAssignmentsCommand](../../src/Command/Bulk/BulkCreateUsersAssignmentsCommand.php) is identical with the `[POST]api/v1/bulk/assignments` REST endpoint. 
It creates new assignments with `READY` state for the provided users, **and cancels any previous ones**. 

The API endpoint has a limitation of `1000` users per request, so if there is a need to create large scale of assignments, this command can be used instead of the Lambda assignment manager.

## Usage:

```bash
$ bin/console roster:assignments:bulk-create [--force]
```

#### Main arguments:

| Option | Description |
| ------------- |:-------------|
| source | Can be **local**, or **s3** |
| path   |  Local or S3 path of the source CSV file |

#### Main options:

| Option | Description |
| ------------- |:-------------|
| -d, --delimiter  | CSV delimiter [default: `,`] |
| -c, --charset    | CSV source charset [default: `UTF-8`] |
| -b, --batch-size | Number of assignments to process per batch [default: `1000`] |
| -f, --force      | To apply database modifications or not [default: `false`] |

#### Other options

For the full list of options please refer to the helper option:

```bash
$ bin/console roster:assignments:bulk-create -h
```

## Examples:

Creating new assignments in dry mode using a local CSV file:

```bash
$ bin/console roster:assignments:bulk-create local /path/to/file.csv
```

Creating new assignments using a local CSV file:

```bash
$ bin/console roster:assignments:bulk-create local /path/to/file.csv --force
```

Creating new assignments using a local UTF-16LE encoded CSV file:

```bash
$ bin/console roster:assignments:bulk-create local /path/to/file.csv --charset="UTF-16LE" --force
```

Creating new assignments using a CSV file on an S3 bucket:

```bash
$ bin/console roster:assignments:bulk-create s3 bucket/path/to/file.csv --force
```
