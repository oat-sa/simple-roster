# Line Item change dates command

[LineItemChangeDatesCommand](../../src/Command/ModifyEntity/LineItem/LineItemChangeDatesCommand.php) allows the user to
set start and end date for line-item(s).

- [Usage](#usage)
    - [Main options](#main-options)
- [Related environment variables](#related-environment-variables)
- [Examples](#examples)

## Usage
```shell script
$ sudo -u www-data bin/console roster:modify-entity:line-item:change-dates -i <Line Item ID(s)> -s <date> -e <date>
$ sudo -u www-data bin/console roster:modify-entity:line-item:change-dates -u <Line Item Slug(s)> -s <date> -e <date>
```

### Main options

| Option                | Description                                                                                                                             |
| ----------------------|:----------------------------------------------------------------------------------------------------------------------------------------|
| -i, --line-item-ids   | Comma separated list of line item ids.                                                                                                  |
| -s, --line-item-slugs | Comma separated list of line item slugs.                                                                                                |
| --start-date          | Define the start date for the specified line item(s). Expected Format: 2020-01-01T00:00:00+0000. If not informed, it will be nullified. |
| --end-date            | Define the end date for the specified line item(s). Expected Format: 2020-01-01T00:00:00+0000. If not informed, it will be nullified.   |
| -f, --force           | If not used, no changes will be made to database (Dry Run)                                                                              |

> **NOTE:** You need to specify at least one of the parameters (IDs or Slugs).

For the full list of options please refer to the helper option:
```shell script
$ sudo -u www-data bin/console roster:modify-entity:line-item:change-dates -h
```

## Related environment variables

| Variable | Description |
|----------|-------------|
| `DATABASE_URL` | Database connection string. Supported formats are described [here](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html#connecting-using-a-url). |

## Examples
- Updating dates of line items using IDs
```shell script
$ sudo -u www-data bin/console roster:modify-entity:line-item:change-dates -i 1,2,3 --start-date 2020-01-01T00:00:00+0000 --end-date 2020-01-05T00:00:00+0000 --force
```
- Updating dates of line items using Slugs
```shell script
$ sudo -u www-data bin/console roster:modify-entity:line-item:change-dates -s slug1,slug2,slug3 --start-date 2020-01-01T00:00:00+0000 --end-date 2020-01-05T00:00:00+0000 --force
```
- Updating dates in a different timezone (UTC+1)
```shell script
$ sudo -u www-data bin/console roster:modify-entity:line-item:change-dates -s slug1,slug2,slug3 --start-date 2020-01-01T00:00:00+0100 --end-date 2020-01-05T00:00:00+0100 --force
```
- Nullifying dates of a line item by line item
```shell script
//Nullifying start and end dates
$ sudo -u www-data bin/console roster:modify-entity:line-item:change-dates -i 1,2,3 -f

//Nullifying only start date
$ sudo -u www-data bin/console roster:modify-entity:line-item:change-dates -i 1,2,3 --end-date 2020-01-05T00:00:00+0000 --force

//Nullifying only end date
$ sudo -u www-data bin/console roster:modify-entity:line-item:change-dates -i 1,2,3 --start-date 2020-01-01T00:00:00+0000 --force
```

> **NOTE:** There is no need to warmup the cache manually for the affected line-items when you run this command,
>this is done automatically.
