# Bulk user assignment creation command

[BulkUserCreationCommand](../../src/Command/Ingester/BulkUserCreationCommand.php) is responsible for creating bulk `users` and `assignments` data into the application based on Line Items.

- [Usage](#usage)
    - [Main arguments](#main-arguments)
    - [Main options](#main-options)
- [Related environment variables](#related-environment-variables)
- [Examples](#examples)

## Usage
```shell script
$ sudo -u www-data bin/console roster:create:user [-i|--line-item-ids] [-s|--line-item-slugs] [-b|--batch-size] [-g|--group-prefix] <user-prefix>
```

### Main arguments

| Argument | Description                                          |
| ---------|:-----------------------------------------------------|
| user-prefix     | Names of users (Ex: QA,OAT,CUSTOMER) |

### Main options

| Option          | Description                                                                                                   |
| ----------------|:--------------------------------------------------------------------------------------------------------------|
| -i, --line-item-ids   | Ids of line items seperated by commas |
| -s, --line-item-slugs | Slugs of line items seperated by commas                                                                                 |
| -b, --batch-size | Batch size is the number of users created for each  user-prefix                                                                                |
| -g, --group-prefix | Group prefix to be used if load balancing configured to userGroupId [default: null]                                                     |

For the full list of options please refer to the helper option:
```shell script
$ sudo -u www-data bin/console roster:create:user -h
```

## Related environment variables

| Variable | Description |
|----------|-------------|
| `AUTOMATE_USER_LIST_PATH` | Folder path in which new users and assignments csv files created(saved as `local`) |

## Examples

Ingesting users and assignments with line item ids, custom batch size and group prefix:
```shell script
$ sudo bin/console roster:create:user -i 100,200 -b 100 QA,OAT,TE -g TestCollege
```

Ingesting users and assignments with line item slugs, custom batch size and group prefix:
```shell script
$ sudo bin/console roster:create:user -s 21XBCALL15_1,21XBCALL14_2 -b 100 QA,OAT -g TestCollege
```

Ingesting users and assignments without line item ids & line item slugs and custom batch size:- 
In this case users will be created for all line items existing in the system
```shell script
$ sudo bin/console roster:create:user -b 100 QA,OAT -g TestCollege
```

Ingesting users and assignments with line item slugs and without group prefix:
```shell script
$ sudo bin/console roster:create:user -s 21XBCALL15_1,21XBCALL14_2 -b 100 QA,OAT
```

Ingesting users and assignments with line item slugs and without group prefix:
```shell script
$ sudo bin/console roster:create:user -s 21XBCALL15_1,21XBCALL14_2 -b 100 QA,OAT
```

Ingesting users and assignments with line item slugs and without batch size:
```shell script
$ sudo bin/console roster:create:user -s 21XBCALL15_1,21XBCALL14_2 QA,OAT -g TestCollege
```
