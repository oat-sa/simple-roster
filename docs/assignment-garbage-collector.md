# Garbage Collector - Assignments

[AssignmentGarbageCollectorCommand](src/Command/AssignmentGarbageCollectorCommand.php) is responsible for transitioning all stuck assignments from `started` state to `completed` state.

Default usage:
```php
$ bin/console roster:garbage-collector:assignment
```

Main options:

| Option | Description |
| ------------- |:-------------|
| -b, --batch-size | Number of assignments to process per batch [default: `1000`] |
| -f, --force      |  To involve actual database modifications or not [default: `false`] |

For the full list of options please refer to the helper option:
```php
$ bin/console roster:garbage-collector:assignment -h
```