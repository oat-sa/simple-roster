# Profiling with Blackfire

> Blackfire Profiler is a tool that instruments PHP applications to gather data about consumed server resources like memory, CPU time, and I/O operations.

## Table of contents
- [Configuration](#configuration)
- [Profiling HTTP calls](#profiling-http-calls)
- [Profiling CLI commands](#profiling-cli-commands)

## Configuration

Blackfire probe is coming out-of-the-box with docker configuration, all you have to do is to enable it and to set up 
your credentials in the `.env.local` file:

| Environment variable | Description |
|----------------------|-------------|
| `BLACKFIRE_ENABLED` | To enable blackfire profiling `[default: false]` |
| `BLACKFIRE_SERVER_ID` | Blackfire server ID. |
| `BLACKFIRE_SERVER_TOKEN` | Blackfire server token. |
| `BLACKFIRE_CLIENT_ID` | Sets the client ID from the Client ID/Client Token credentials pair. |
| `BLACKFIRE_CLIENT_TOKEN` | Sets the client Token from the Client ID/Client Token credentials pair. |

You can find your personal credentials here: [https://blackfire.io/docs/configuration/php#configuring-the-probe-via-environment-variables](https://blackfire.io/docs/configuration/php#configuring-the-probe-via-environment-variables) (needs authentication)

## Profiling HTTP calls

Profiling HTTP calls can be done just by adding `X-Blackfire` header to your API calls.

Here is an example of how to profile the `login` endpoint:

```text
curl --location --request POST 'https://simple-roster.docker.localhost/api/v1/auth/login' \
    --header 'Content-Type: application/json' \
    --header 'X-Blackfire: ' \
    --header 'Cookie: PHPSESSID=<your-session-id>' \
    --data-raw '{
	    "username": "<your-username>",
	    "password": "<your-password>"
    }'
```

## Profiling CLI commands

Profiling CLI commands can be done with the help of `OAT\SimpleRoster\Command\BlackfireProfilerTrait`.

First you have to use the trait in your CLI command and manually invoke the `addBlackfireProfilingOption()` in your command's `configure()` method.

This will add the `--blackfire` option to you command.

```php
<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Command\MyCommand;

use OAT\SimpleRoster\Command\BlackfireProfilerTrait;
use Symfony\Component\Console\Command\Command;

class MyCommand extends Command
{
    use BlackfireProfilerTrait;

    // ...
    
    protected function configure(): void
    {
        $this->addBlackfireProfilingOption();
        
        // ...
    }
    
    // ...
}
```

Now you are able to profile your command by specifying the `--blackfire` option:

```shell script
$ sudo -u www-data bin/console your:command --blackfire
```
