# Update Line Items Webhook

This endpoint is intended to receive requests coming from TAO Platform whenever a new delivery publication is done.

## Table of Contents

- [Related environment variables](#related-environment-variables)
- [Request Example](#request-example)
    - [Attribute Descriptions](#attribute-descriptions)
- [Endpoint Descriptions](#endpoint-descriptions)
- [WebHook Payload Schema Definition](#webhook-schema-definition)
    - [Request](#request)
    - [Response](#response)

### Related environment variables

| Variable | Description |
| -------- |:------------|
| `WEBHOOK_BASIC_AUTH_USERNAME` | Basic auth username. |
| `WEBHOOK_BASIC_AUTH_PASSWORD` | Basic auth password. |

### Request Example

Here you can find an example about how to update the line-item via curl command:

```shell script
curl --location --request POST 'http://simple-roster.docker.localhost/api/v1/web-hooks/update-line-items' \
--header 'Content-Type: application/json' \
--header 'Authorization: Basic d2ViaG9vazpjQ1ZNeGRYOGhmY3REZWZr' \
--data-raw '{
	"source":"https://someinstance.taocloud.org/",
	"events":[
        {
			"eventId":"52a3de8dd0f270fd193f9f4bff05232c",
			"eventName":"oat\\taoPublishing\\model\\publishing\\event\\RemoteDeliveryCreatedEvent",
			"triggeredTimestamp":1565602390,
			"eventData":{
				"alias":"line-item-slug",
				"remoteDeliveryId":"https://tao.platform/ontologies/tao.rdf#delivery-uri"
			}
		}
	]
}'
```

#### Authentication

To use this endpoint, the `Authorization` header should be defined as the sample above.

#### Attribute Descriptions

| Attribute                  | Description                                                                                                                                                                                                      |
| ---------------------------|:-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| eventId                    | The event identifier. The format is described in the [WebHook Payload Schema Definition](#webhook-schema-definition). It is used by the Simple Roster only to return it in the webhook response.                 |
| eventName                  | The name of the event. Only `oat\\taoPublishing\\model\\publishing\\event\\RemoteDeliveryCreatedEvent` will be handled by Simple Roster. Any other events will be ignored.                                       |
| triggeredTimestamp         | A timestamp that represents when the event happened. In case of duplicate events, Simple Roster will assume the latter based on this attribute. The other events will be ignored.                                |
| eventData.alias            | The delivery URI alias for the new publication. This value must match the slug in the line items that need to be updated. If the line items are not found, the event is not accepted and is considered an error. |
| eventData.remoteDeliveryId | The Delivery URI of the new publication. In case the alias match with the line items slug, this value will replace the line items URI.                                                                           |

### Endpoint Descriptions

You can find the endpoint description by accessing the [open api specification](../../openapi/api_v1.yml)

### WebHook Schema Definition

These are the request and response schema definitions for the related webhook:

#### Request
```json
{
	"definitions":{},
	"$schema":"http://json-schema.org/schema#",
	"$id":"http://www.tao.lu/tao/webhookRequest.json",
	"type":"object",
	"title":"TAO event notification",
	"required":[
		"source",
		"events"
	],
	"properties":{
		"source":{
			"$id":"#/properties/source",
			"type":"string",
			"title":"TAO instance URL",
			"default":"",
			"examples":[
				"https://someinstance.taocloud.org/"
			],
			"minLength":8
		},
		"events":{
			"$id":"#/properties/events",
			"type":"array",
			"title":"Array of event notifications",
			"items":{
				"$id":"#/properties/events/items",
				"type":"object",
				"title":"Event notification",
				"required":[
					"eventId",
					"eventName",
					"triggeredTimestamp",
					"eventData"
				],
				"properties":{
					"eventId":{
						"$id":"#/properties/events/items/properties/eventId",
						"type":"string",
						"title":"Unique event identifier",
						"examples":[
							"52a3de8dd0f270fd193f9f4bff05232f"
						],
						"pattern":"^([a-z0-9]{32})$"
					},
					"eventName":{
						"$id":"#/properties/events/items/properties/eventName",
						"type":"string",
						"title":"Type of event",
						"examples":[
							"DeliveryExecutionFinished"
						],
						"minLength":1
					},
					"triggeredTimestamp":{
						"$id":"#/properties/events/items/properties/triggeredTimestamp",
						"type":"integer",
						"title":"UNIX timestamp of event triggering",
						"examples":[
							1565602371
						]
					},
					"eventData":{
						"$id":"#/properties/events/items/properties/eventData",
						"type":"object",
						"title":"Additional event data, depends on eventName"
					}
				}
			}
		}
	}
}
```

#### Response

```json
{
	"definitions":{},
	"$schema":"http://json-schema.org/schema#",
	"$id":"http://www.tao.lu/tao/webhookResponse.json",
	"type":"object",
	"title":"Event webhook response",
	"required":[
		"events"
	],
	"properties":{
		"events":{
			"$id":"#/properties/events",
			"type":"array",
			"title":"Events processing result",
			"items":{
				"$id":"#/properties/events/items",
				"type":"object",
				"title":"Event processing result",
				"required":[
					"eventId",
					"status"
				],
				"properties":{
					"eventId":{
						"$id":"#/properties/events/items/properties/eventId",
						"type":"string",
						"title":"Event id from request",
						"default":"",
						"examples":[
							"52a3de8dd0f270fd193f9f4bff05232f"
						],
						"pattern":"^([a-z0-9]{32})$"
					},
					"status":{
						"$id":"#/properties/events/items/properties/status",
						"type":"string",
						"title":"Event processing result",
						"default":"",
						"examples":[
							"accepted"
						],
						"enum":[
							"accepted",
							"ignored",
							"error"
						]
					}
				}
			}
		}
	}
}
```
