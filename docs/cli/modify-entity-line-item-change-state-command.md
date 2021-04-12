# Line Item change state command

[LineItemChangeStatusCommand](../../src/Command/ModifyEntity/LineItem/LineItemChangeStatusCommand.php) is responsible for enable 
or disable `Line Items` after [ingestion](line-item-ingester-command.md).

- [Usage](#usage)
    - [Main arguments](#main-arguments)
- [Related environment variables](#related-environment-variables)
- [Examples](#examples)

## Usage
```shell script
$ sudo -u www-data bin/console roster:modify-entity:line-item:change-state <toggle> <query-field> <query-value>
```

### Main arguments

| Argument    | Description                                                                                                                                                                                                                                                                 |
| ------------|:----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| toggle      | Accepted two values "enable" to enable a line item, "disable" to disable a line item.                                                                                                                                                                                       |
| query-field | How do you want to query the line items that you want to enable/disable. Accepted parameters are: id, slug, uri                                                                                                                                                             |
| query-value | The value that should match based on the query field. it can be one value or a list of values split by space. Example: given that the query field is "slug" and the query value is "test1 test2" then all the line items with slug equals to test1 or test2 will be updated |

For the full list of options please refer to the helper option:
```shell script
$ sudo -u www-data bin/console roster:modify-entity:line-item:change-state -h
```

## Related environment variables

| Variable | Description |
|----------|-------------|
| `DATABASE_URL` | Database connection string. Supported formats are described [here](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html#connecting-using-a-url). |

## Examples

- Enabling a line item by line item slug
```shell script
$ sudo -u www-data bin/console roster:modify-entity:line-item:change-state enable slug my-line-item
```
- Enabling a line item by multiple slugs:
 ```shell script
$ sudo -u www-data bin/console roster:modify-entity:line-item:change-state enable slug my-line-item1 my-line-item2
```
- Enabling a line item by line item id
```shell script
$ sudo -u www-data bin/console roster:modify-entity:line-item:change-state enable id 00000001-0000-6000-0000-000000000000
```
- Enabling a line item by line item uri
```shell script
$ sudo -u www-data bin/console roster:modify-entity:line-item:change-state enable uri https://i.o#i5fb54d6ecd
```
- Disabling a line item by line item slug
```shell script
$ sudo -u www-data bin/console roster:modify-entity:line-item:change-state disable slug my-line-item
```
- Disabling a line item by multiple slugs
```shell script
$ sudo -u www-data bin/console roster:modify-entity:line-item:change-state disable slug my-line-item1 my-line-item2
```
- Disabling a line item by line item id
```shell script
$ sudo -u www-data bin/console roster:modify-entity:line-item:change-state disable id 00000001-0000-6000-0000-000000000000
```
- Disabling a line item by line item uri
```shell script
$ sudo -u www-data bin/console roster:modify-entity:line-item:change-state disable uri https://i.o#i5fb54d6ecd
```

> **NOTE:** There is no need to warmup the cache manually for the affected line-items when you run this command,
>this is done automatically.
