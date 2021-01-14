# CLI documentation

> The application currently offers the following CLI commands:

- [Ingestion related commands](#ingestion-related-commands)
- [Cache warmup related commands](#cache-warmup-related-commands)
- [Other commands](#other-commands)

## Ingestion related commands

| Command                                       | Description                         | Links                                                      |
| ----------------------------------------------|:------------------------------------|:-----------------------------------------------------------|
| `roster:ingest:lti-instance`                  | LTI instance ingestion              | [link](cli/lti-instance-ingester-command.md)               |
| `roster:ingest:line-item`                     | Line item instance ingestion        | [link](cli/line-item-ingester-command.md)                  |
| `roster:ingest:user`                          | User ingestion                      | [link](cli/user-ingester-command.md)                       |
| `roster:ingest:assignment`                    | Assignment ingestion                | [link](cli/assignment-ingester-command.md)                 |

## Cache warmup related commands

| Command                                       | Description                         | Links                                                      |
| ----------------------------------------------|:------------------------------------|:-----------------------------------------------------------|
| `roster:cache-warmup:lti-instance`            | LTI instance cache warming          | [link](cli/lti-instance-cache-warmer-command.md)           |
| `roster:cache-warmup:line-item`               | Line Item cache warming             | [link](cli/line-item-cache-warmer-command.md)              |
| `roster:cache-warmup:user`                    | User cache warming                  | [link](cli/user-cache-warmer-command.md)                   |

## Other commands

| Command                                       | Description                         | Links                                                      |
| ----------------------------------------------|:------------------------------------|:-----------------------------------------------------------|
| `roster:modify-entity:line-item:change-dates` | Change line-item(s) start/end dates | [link](cli/modify-entity-line-item-change-dates-command.md)|
| `roster:modify-entity:line-item:change-state` | Activate/Deactivate line items      | [link](cli/modify-entity-line-item-change-state-command.md)|
| `roster:garbage-collector:assignment`         | Assignment garbage collection       | [link](cli/assignment-garbage-collector-command.md)        |

