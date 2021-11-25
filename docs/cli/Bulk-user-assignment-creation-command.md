# Bulk user assignment creation command

[CreateUserCommand](../../src/Command/Ingester/CreateUserCommand.php) is responsible for creating bulk `users` and `assignments` data into the application based on LineItems.

- [Usage](#usage)
    - [Main arguments](#main-arguments)
    - [Main options](#main-options)
- [Related environment variables](#related-environment-variables)
- [Examples](#examples)

## Usage
```shell script
$ sudo -u www-data bin/console roster:create:user [-i|--line-item-ids] [-s|--line-item-slugs] [-b|--batch-size] [-p|--group-prefix] [--] <user-prefix>
```

### Main arguments

| Argument | Description                                          |
| ---------|:-----------------------------------------------------|
| user-prefix     | Names of users (Ex: QA,LQA,OAT) |

### Main options

| Option          | Description                                                                                                   |
| ----------------|:--------------------------------------------------------------------------------------------------------------|
| -i, --line-item-ids   | Ids of line items seperated by commas |
| -s, --line-item-slugs | Slugs of line items seperated by commas                                                                                 |
| -b, --batch-size | Batch size is the number of users created for each  user-prefix                                                                                |
| -p, --group-prefix | Group prefix used for load balancer in newly created users [default: null]                                                     |

For the full list of options please refer to the helper option:
```shell script
$ sudo -u www-data bin/console roster:create:user -h
```

## Related environment variables

| Variable | Description |
|----------|-------------|
| `AUTOMATE_USER_LIST_PATH` | Folder path in which new users and assignments csv files created(saved as `local`) |

## Examples

Ingesting users and assignments with lineitem ids, custom batch size and group prefix:
```shell script
$ sudo docker container exec -it simple-roster-phpfpm bin/console roster:create:user -i 100,200 -b 100 QA,OAT,TE -p TestColleges
```

Ingesting users and assignments with lineitem slugs, custom batch size and group prefix:
```shell script
$ sudo docker container exec -it simple-roster-phpfpm bin/console roster:create:user -s 21XBCALL15_1,21XBCALL14_2 -b 100 QA,OAT -p TestColleges
```

Ingesting users and assignments without lineitem ids & lineitem slugs and custom batch size:- 
In this case users will be created for all line items existing in the system
```shell script
$ sudo docker container exec -it simple-roster-phpfpm bin/console roster:create:user -b 100 QA,OAT -p TestColleges
```

Ingesting users and assignments with lineitem slugs and without group prefix:
```shell script
$ sudo docker container exec -it simple-roster-phpfpm bin/console roster:create:user -s 21XBCALL15_1,21XBCALL14_2 -b 100 QA,OAT
```

Ingesting users and assignments with lineitem slugs and without group prefix:
```shell script
$ sudo docker container exec -it simple-roster-phpfpm bin/console roster:create:user -s 21XBCALL15_1,21XBCALL14_2 -b 100 QA,OAT
```

Ingesting users and assignments with lineitem slugs and without batch size:
```shell script
$ sudo docker container exec -it simple-roster-phpfpm bin/console roster:create:user -s 21XBCALL15_1,21XBCALL14_2 QA,OAT -p TestColleges
```
