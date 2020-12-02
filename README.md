# Simple-Roster

>REST back-end service intending to mimic a simplified version of OneRoster IMS specification.

![current version](https://img.shields.io/badge/version-2.0.0-green.svg)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html)
![coverage](https://img.shields.io/badge/coverage-100%25-green.svg)

*IMS OneRoster* solves a school’s need to securely and reliably exchange roster information, course materials and grades between systems. 
OneRoster supports the commonly used .csv mode for exchange and the latest real-time web services mode known as REST.  

To learn more about *IMS OneRoster*, please refer to the official specification at [IMS Global](https://www.imsglobal.org/activity/onerosterlis).

## Table of contents

- [Docker development](docs/docker-development.md)
- [Local development](docs/local-development.md)
- [Features](#)
    - [Learning Tools Interoperability](docs/features/lti.md)
- [OpenAPI documentation](#openapi-documentation)
- [CLI documentation](#cli-documentation)
- [DevOps documentation](docs/devops-documentation.md)
- [Continuous integration](docs/continuous-integration.md)
- [Profiling with Blackfire](docs/blackfire.md)

## OpenAPI documentation

The application uses [OpenAPI 3.0](https://swagger.io/specification/) specification to describe it's REST interface.
You can find our OpenAPI documentation [here](openapi/api_v1.yml).

Please use [Swagger editor](https://editor.swagger.io/) to visualize it.

## CLI documentation

The application currently offers the following CLI commands:

| Command                               | Description                   | Links                                                    |
| --------------------------------------|:------------------------------|:---------------------------------------------------------|
| `roster:cache-warmup:lti-instance`    | LTI instance cache warming    | [link](docs/cli/lti-instance-cache-warmer-command.md)    |
| `roster:cache-warmup:user`            | User cache warming            | [link](docs/cli/user-cache-warmer-command.md)            |
| `roster:garbage-collector:assignment` | Assignment garbage collection | [link](docs/cli/assignment-garbage-collector-command.md) |
| `roster:ingest:lti-instance`          | LTI instance ingestion        | [link](docs/cli/lti-instance-ingester-command.md)        |
| `roster:ingest:line-item`             | Line item instance ingestion  | [link](docs/cli/line-item-ingester-command.md)           |
| `roster:ingest:user`                  | User ingestion                | [link](docs/cli/user-ingester-command.md)                |
| `roster:ingest:assignment`            | Assignment ingestion          | [link](docs/cli/assignment-ingester-command.md)          |