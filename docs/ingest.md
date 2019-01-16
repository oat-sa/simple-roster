## Ingesting process

To ingest data, use CLI commands inside [src/Command/Ingesting](src/Command/Ingesting).

Ingested data should be put a CSV file. CSV is the only supported format.

You can use a local file or a file in Amazon S3.

## Commands

In a general form the command is:

```
bin/console tao:{local|s3}-ingest --data-type=infrastructures|line-items|user-and-assignments {source options}
```

Source options depend on a chosen format.

## Sources
No matter the source, `--delimiter` option can be used to specify which delimiter is used in a given CSV file. By default it's set to `,`

### Local file
In case the source is a local file, the single source option is just `--filename` which specifies the file location.

### Amazon S3
In case the source is S3, the source options are:

`--s3_bucket` Name of an S3 bucket
`--s3_object` Key of an S3 object

## Dry run

Use option `--dry-run` just to make sure the command works as expected for your data. It disables real writing, so nothing in the real storage will change.

## Data types and CSV formats
Please follow these data structures. In case at least one line of a CSV file is considered as invalid, the entire process terminates.

### infrastructures
```
bin/console tao:local-ingest --data-type=infrastructures --filename=infrastructures.csv
```
CSV fields for infrastructures are: 
1. `id` string, required `must be unique`
2. `lti_director_link` string, required
3. `key` string, required
4. `secret` string, required

Example:
```
"some infrastructure 1", "some_lti_director_link.com", "key", "super secret"
"some infrastructure 2", "some_lti_director_link.com", "qwerty", "qwerty12345"
```

In case an infrastructure with the same `id` already exists, this line of CSV will be skipped (not updated!).

### Line items
```
bin/console tao:local-ingest --data-type=line-items --filename=line-items.csv
```

CSV fields for line items are: 
1. `tao_uri` string, required `must be unique`
2. `title` string, required
3. `infrastructure_id` string, required `infrastructure must be already ingested`
4. `start_date_time` string, optional
5. `end_date_time` string, optional

Example:
```
"http://tao.installation/delivery_1.rdf", "some title", "some infrastructure 2", 
"http://tao.installation/delivery_2.rdf", "some title", "some infrastructure 1", "start", "end",
```

In case a line-item with the same `tao_uri` already exists, this line of CSV will be skipped (not updated!).
In case a `infrastructure_id` refers to a non-existent infrastructure, this line will be considered as invalid, which entail the entire process termination.

### User and assignments
```
bin/console tao:local-ingest --data-type=user-and-assignments --filename=users.csv
```

CSV fields for users and their assignments are: 
1. `user login` string `must be unique`
2. `user password` string `plain`
3. `assignment 1 line item tao URI` string `optional`
4. `assignment 2 line item tao URI` string `optional`
5. `assignment 3 line item tao URI` string `optional`

...

N. `assignment N line item tao URI` string `optional`

This structure can work with any amount of CSV fields >2. All fields starting from third are assignments list. 
In case a user with the same `user login` already exists, those assignments not existing in the storage and specified in CSV will be inserted. In this case the password will not be updated and can even be omitted in CSV if the goal is updating the assignment list.