# Garbage Collector - Assignments

[AssignmentGarbageCollectorCommand](../../src/Command/GarbageCollector/AssignmentGarbageCollectorCommand.php) is responsible for transitioning all stuck assignments from `started` state to `completed` state.

- [Usage](#usage)
    - [Main options](#main-options)
- [Related environment variables](#related-environment-variables)
- [Examples](#examples)

The interval threshold is coming from the `ASSIGNMENT_STATE_INTERVAL_THRESHOLD` environment variable. Supported formats can be found [here](http://php.net/manual/en/dateinterval.format.php).

## Usage

```shell script
$ sudo -u www-data bin/console roster:garbage-collector:assignment [--force]
```

### Main options

| Option | Description |
| ------------- |:-------------|
| -b, --batch-size | Number of assignments to process per batch [default: `1000`] |
| -f, --force      |  To involve actual database modifications or not [default: `false`] |

For the full list of options please refer to the helper option:

```shell script
$ sudo -u www-data bin/console roster:garbage-collector:assignment -h
```

## Related environment variables

| Variable | Description |
|----------|-------------|
| `ASSIGNMENT_STATE_INTERVAL_THRESHOLD` | Time interval threshold. [Example: `P1D`] Supported formats can be found [here](http://php.net/manual/en/dateinterval.format.php). |

## Examples

Collecting all assignments stuck in `STARTED` state and move them to `COMPLETED` state:

```shell script
sudo -u www-data bin/console roster:garbage-collector:assignment --force
```
