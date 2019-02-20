# Native user data ingestion

[NativeUserIngesterCommand.php](../../src/Command/Ingester/Native/NativeUserIngesterCommand.php) is responsible for ingesting `user` data faster than with regular ingester.

## Usage:
```bash
$ bin/console roster:native-ingest:user <source> <path> [--batch=1000]
```

#### Main arguments:

| Option | Description |
| ------------- |:-------------|
| source | Can be **local**, or **s3** |
| path      |  Local or S3 path to ingest data from |

#### Main options:

| Option | Description |
| ------------- |:-------------|
| -d, --delimiter | CSV delimiter [default: `,`] |
| -b, --batch | Batch size [default: `1000`] |

#### Other options

For the full list of options please refer to the helper option:
```bash
$ bin/console roster:native-ingest:user -h
```

## Examples:

Native ingesting users from a local CSV file:
```bash
$ bin/console roster:native-ingest:user local /path/to/file.csv
```

Native ingesting users from a S3 bucket CSV file:
```bash
$ bin/console roster:native-ingest:user s3 bucket/path/to/file.csv
```

Native ingesting users from a S3 bucket CSV file with a different batch size:
```bash
$ bin/console roster:native-ingest:user s3 bucket/path/to/file.csv --batch=500
```