# Data ingestion

[IngesterCommand](../../src/Command/Ingester/IngesterCommand.php) is responsible for ingesting `lti-instance`, `line-item` and `user` data.

## Usage:
```bash
$ bin/console roster:ingest <type> <source> <path> [--force]
```

#### Main arguments:

| Option | Description |
| ------------- |:-------------|
| type | Can be **lti-instance**, **line-item** or **user** |
| source | Can be **local**, or **s3** |
| path      |  Local or S3 path to ingest data from |

#### Main options:

| Option | Description |
| ------------- |:-------------|
| -d, --delimiter | CSV delimiter [default: `,`] |
| -c, --charset | CSV source charset [default: `UTF-8`] |
| -f, --force      |  To involve actual database modifications or not [default: `false`] |

#### Other options

For the full list of options please refer to the helper option:
```bash
$ bin/console roster:ingest -h
```

## Examples:

Dry run ingesting LTI instances from a local CSV file:
```bash
$ bin/console roster:ingest lti-instance local /path/to/file.csv
```

Ingesting LTI instances from a local CSV file:
```bash
$ bin/console roster:ingest lti-instance local /path/to/file.csv --force
```

Ingesting LTI instances from a local UTF-16LE encoded CSV file:
```bash
$ bin/console roster:ingest lti-instance local /path/to/file.csv --charset="UTF-16LE" --force
```

Ingesting line-items from a local CSV file:
```bash
$ bin/console roster:ingest line-item local /path/to/file.csv --force
```

Ingesting users from a S3 bucket CSV file:
```bash
$ bin/console roster:ingest user s3 bucket/path/to/file.csv --force
```