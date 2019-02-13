# Garbage Collector - Assignments

[AssignmentGarbageCollectorCommand](../../src/Command/GarbageCollector/AssignmentGarbageCollectorCommand.php) is responsible for transitioning all stuck assignments from `started` state to `completed` state.

The interval threshold is coming from the `ASSIGNMENT_STATE_INTERVAL_THRESHOLD` environment variable. Default value is [P1D] (= 1 day).

## Default usage:
```php
$ bin/console roster:garbage-collector:assignment [--force]
```
### Main options:

| Option | Description |
| ------------- |:-------------|
| -b, --batch-size | Number of assignments to process per batch [default: `1000`] |
| -f, --force      |  To involve actual database modifications or not [default: `false`] |

### Other options
For the full list of options please refer to the helper option:
```php
$ bin/console roster:garbage-collector:assignment -h
```