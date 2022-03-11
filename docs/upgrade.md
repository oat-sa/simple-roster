# Upgrade

## 2.5.0

New version of doctrine/dbal does not support json_array.
To get rid of the unsupported type, run the SQL command
```
comment on column users.roles is '(DC2Type:json)';
```
