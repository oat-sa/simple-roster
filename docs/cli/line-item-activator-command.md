# Line Item activator command

[LineItemActivatorCommand](../../src/Command/Activator/LineItemActivatorCommand.php) is responsible for Activate 
or Deactivate `Line Items` after [ingesting](line-item-ingester-command.md).
    
## Usage
```shell script
$ sudo -u www-data bin/console roster:activate:line-item <toggle> <query-field> <query-value>
```
### Arguments

| Argument        | Description                                                                                                                                                                                        |
| ----------------|:---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| toggle          | Accepted two values "activate" to activate a line item. "deactivate" to deactivate a line item.                                                                                                    |
| query-field     | How do you want to query the line items that you want to activate/deactivate. Accepted parameters are: id, slug, uri                                                                               |
| query-value     | The value that should match based on the query field. Example: given that the query field is "slug" and the query value is "test" then all the line items with slug equals to test will be updated |

## Examples
- Activating a line item by line item uri
```shell script
$ sudo -u www-data bin/console roster:activate:line-item activate uri https://i.o#i5fb54d6ecd2839a3192238deb544fe
```
- Activating a line item by line item id
```shell script
$ sudo -u www-data bin/console roster:activate:line-item activate id 4
```
- Activating a line item by line item slug
```shell script
$ sudo -u www-data bin/console roster:activate:line-item activate slug my-line-item
```

- Deactivating a line item by line item uri
```shell script
$ sudo -u www-data bin/console roster:activate:line-item deactivate uri https://i.o#i5fb54d6ecd2839a3192238deb544fe
```
- Deactivating a line item by line item id
```shell script
$ sudo -u www-data bin/console roster:activate:line-item deactivate id 4
```
- Deactivating a line item by line item slug
```shell script
$ sudo -u www-data bin/console roster:activate:line-item deactivate slug my-line-item
```

> **NOTE** There is no need to warmup the cache for the affected line-items when you run this command. 
>meaning this is done automatically.


## Help
For the full list of options please refer to the helper option:
```shell script
$ sudo -u www-data bin/console roster:cache-warmup:line-item -h
```