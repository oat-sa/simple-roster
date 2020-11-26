# User cache warmer command

[UserCacheWarmerCommand](../../src/Command/Cache/UserCacheWarmerCommand.php) is responsible for warming up the cache for 
`users` after [ingesting](user-ingester-command.md) them.

- [Usage](#usage)
    - [Main options](#main-options)
    - [Examples](#examples)
- [Synchronous cache warmup parallelization](#synchronous-cache-warmup-parallelization)
    - [Example](#example)
- [Asynchronous cache warmup with Amazon SQS](#asynchronous-cache-warmup-with-amazon-sqs)
    
## Usage

```shell script
$ sudo -u www-data bin/console roster:cache-warmup:user [--usernames] [--line-item-slugs] [--batch=1000] [--modulo] [--remainder]
```

### Main options

| Option                | Description                                                       |
| ----------------------|:------------------------------------------------------------------|
| -u, --usernames       | Comma separated list of usernames to scope the cache warmup       |
| -l, --line-item-slugs | Comma separated list of line item slugs to scope the cache warmup |                                                                                 
| -b, --batch           | Number of cache entries to process per batch [default: `1000`]    |

For the full list of options please refer to the helper option:
```shell script
$ sudo -u www-data bin/console roster:cache-warmup:user -h
```

### Examples

Warming up all result cache entries in batches of 10.000:

```shell script
$ sudo -u www-data bin/console roster:cache-warmup:user --batch=10000
```

Warming up result cache entries for specific users:
```shell script
$ sudo -u www-data bin/console roster:cache-warmup:user --usernames=user1,user2,user3,user4
```

Warming up result cache entries for specific line items:
```shell script
$ sudo -u www-data bin/console roster:cache-warmup:user --line-item-slugs=slug1,slug2,slug3
```

## Synchronous cache warmup parallelization

Sometimes it can be necessary to parallelize the cache warmup process due to the huge amount of users in the system. This
can be done by applying an Euclidean division (`modulo` option) on the primary key of the users (ID) and by launching 
multiple commands in parallel with different `remainder` option. 

| Option | Description |
| ------------- |:---------------|
| -m, --modulo | Modulo (M) of Euclidean division A = M*Q + R (0 ≤ R < M), where A = user id, Q = quotient, R = 'remainder' option. |
| -r, --remainder | Remainder (R) of Euclidean division A = M*Q + R (0 ≤ R < M), where A = user id, Q = quotient, M = 'modulo' option. |

### Example

Let's assume we would like to parallelize the cache warmup by launching `4` instances of the command in separate screens:

First let's warmup the cache for all users where `ID % 4 === 0`.

```shell script
$ screen -S cache-warmup-0
$ sudo -u www-data bin/console roster:cache-warmup:user --modulo=4 --remainder=0
```

Exit from screen `cache-warmup-0` by pressing `CTRL+A` then `CTRL+D`.

Now let's warmup the cache for all users where `ID % 4 === 1`.

```shell script
$ screen -S cache-warmup-1
$ sudo -u www-data bin/console roster:cache-warmup:user --modulo=4 --remainder=1
```

Exit from screen `cache-warmup-1` by pressing `CTRL+A` then `CTRL+D`.

Now let's warmup the cache for all users where `ID % 4 === 2`.

```shell script
$ screen -S cache-warmup-2
$ sudo -u www-data bin/console roster:cache-warmup:user --modulo=4 --remainder=2
```

Exit from screen `cache-warmup-2` by pressing `CTRL+A` then `CTRL+D`.

Now let's warmup the cache for all users where `ID % 4 === 3`.

```shell script
$ screen -S cache-warmup-3
$ sudo -u www-data bin/console roster:cache-warmup:user --modulo=4 --remainder=3
```

Exit from screen `cache-warmup-3` by pressing `CTRL+A` then `CTRL+D`.

Once all the commands have finished, the result cache should be warmed up for all the users in the system.

> **Important** - Always take into account the physical limitations of your web server, database instance and cache server 
> before deciding how many command instances to launch in parallel.

## Asynchronous cache warmup with Amazon SQS

Amazon SQS queue setup can be done by setting up the `MESSENGER_TRANSPORT_DSN` environment variable in your `.env.local` file:

Here is an example:

```dotenv
# MESSENGER_TRANSPORT_DSN=sqs://sqs.<your-aws-region>.amazonaws.com/<your-aws-account-id>/<queue-name>
MESSENGER_TRANSPORT_DSN=sqs://sqs.eu-central-1.amazonaws.com/123456789/cache-warmup
```

Then you have to configure the transport in `config/packeges/<environment>/messenger.yaml` file:

```yaml
framework:
    messenger:
        transports:
            cache-warmup:
                dsn: "%env(MESSENGER_TRANSPORT_DSN)%"
                serializer: messenger.transport.symfony_serializer
                options:
                    access_key: <your-aws-access-key>
                    secret_key: <your-aws-secret-key>
```
> Depending on your environment settings `access_key` and `secret_key` options might not be required.

For the full list of options please refer to the official documentation: [https://symfony.com/doc/current/messenger.html#amazon-sqs](https://symfony.com/doc/current/messenger.html#amazon-sqs)

### Setting up the worker

To consume cache warmup messages asynchronously you can use the following command:

```shell script
$ sudo -u www-data bin/console messenger:consume cache-warmup
```

> You can add the `-vv` option for more verbosity of messages (can be useful for debugging).

### Deploying to production

On production, there are a few important things to think about:

- __Use Supervisor to keep your worker(s) running__

You’ll want one or more “workers” running at all times. To do that, use a process control system like [Supervisor](http://supervisord.org/).

- __Don’t Let Workers Run Forever__

Some services (like Doctrine’s EntityManager) will consume more memory over time. So, instead of allowing your worker to run forever, 
use a flag like `messenger:consume --limit=10` to tell your worker to only handle 10 messages before exiting (then Supervisor will create a new process). 
There are also other options like `--memory-limit=128M` and `--time-limit=3600`.

- __Restart Workers on Deploy__

Each time you deploy, you’ll need to restart all your worker processes so that they see the newly deployed code. 
To do this, run `messenger:stop-workers` on deploy. This will signal to each worker that it should finish the message 
it’s currently handling and shut down gracefully. Then, Supervisor will create new worker processes. 
