# Line Item change dates command

[LineItemChangeDatesCommand](../../src/Command/ModifyEntity/LineItem/LineItemChangeDatesCommand.php) allows the user to
set start and end date for line-item(s).

## Usage
```shell script
$ sudo -u www-data bin/console roster:modify-entity:line-item:change-state <toggle> <query-field> <query-value>
```
### Options

| Options               | Description                                                                                  |
| ----------------------|:---------------------------------------------------------------------------------------------|
| --start-date          | Define the start date for the specified line item(s). If not informed, it will be nullified. |
| --end-date            | Define the end date for the specified line item(s). If not informed, it will be nullified.   |
| -i, --line-item-ids   | Comma separated list of line item ids.                                                       |
| -s, --line-item-slugs | Comma separated list of line item slugs.                                                     |
| -f, --force           | If not used, no changes will be made to database (Dry Run)                                   |

## Examples
- Updating dates of line items using IDs
```shell script
$ sudo -u www-data bin/console roster:update-entity:line-item:change-dates -i 1,2,3 --start-date . Expected format: format: 2020-01-01T00:00:00+0000 --end-date . Expected format: format: 2020-01-01T00:00:00+0000 --force
```
- Updating dates of line items using Slugs
```shell script
$ sudo -u www-data bin/console roster:update-entity:line-item:change-dates -s slug1,slug2,slug3 --start-date . Expected format: format: 2020-01-01T00:00:00+0000 --end-date . Expected format: format: 2020-01-01T00:00:00+0000 --force
```
- Nullifying dates of a line item by line item
```shell script
$ sudo -u www-data bin/console roster:update-entity:line-item:change-dates -i 1,2,3
```

> **NOTE** The cache warm up for line-items is automatically called after updating dates into the database.

## Help
For the full list of options please refer to the helper option:
```shell script
$ sudo -u www-data bin/console roster:update-entity:line-item:change-dates -h