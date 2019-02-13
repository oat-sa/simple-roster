# Ingesting

[IngesterCommand](../../src/Command/Ingester/IngesterCommand.php) is responsible for ingesting `infrastrcture`, `line-item` and `user` data.

## Default usage:
```bash
$ bin/console roster:ingest <type> <source> <path> [--force]
```

### Main arguments:

| Option | Description |
| ------------- |:-------------|
| type | Can be **infrastructure**, **line-item** or **user** |
| source | Can be **local**, or **s3** |
| path      |  Local or S3 path to ingest data from |

### Main options:

| Option | Description |
| ------------- |:-------------|
| -d, --delimiter | CSV delimiter [default: `,`] |
| -f, --force      |  To involve actual database modifications or not [default: `false`] |

### Other options
For the full list of options please refer to the helper option:
```bash
$ bin/console roster:ingest -h
```

## Examples:

Dry run ingesting infrastructures from a local CSV file:
```bash
$ bin/console roster:ingest infrastructure local /path/to/file.csv
```

Ingesting infrastructures from a local CSV file:
```bash
$ bin/console roster:ingest infrastructure local /path/to/file.csv --force
```

Ingesting line-items from a local CSV file:
```bash
$ bin/console roster:ingest line-item local /path/to/file.csv --force
```

Ingesting users from a S3 bucket CSV file:
```bash
$ bin/console roster:ingest user s3 bucket/path/to/file.csv --force
```