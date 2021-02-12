# Learning Tools Interoperability (LTI)

>Learning Tools Interoperability (LTI) is an education technology specification developed by the IMS Global Learning Consortium. It specifies a method for a learning system to invoke and to communicate with external systems.

Simple Roster is capable of generate links for the LTI versions `1.1.1` and `1.3.0`. Please check the configuration section for each version.

## Table of Contents
- [LTI Global configuration](#lti-global-configuration)
- [LTI 1.1.1](#lti-111)
    - [LTI 1.1.1 Configuration](#lti-111-configuration)
    - [LTI 1.1.1 Link Generation](#lti-111-link-generation-and-response)
    - [LTI 1.1.1 Load balancing strategy](#lti-111-load-balancing-strategy)
- [LTI 1.3.0](#lti-130)
    - [LTI 1.3.0 Configuration](#lti-130-configuration)
    - [LTI 1.3.0 Link Generation](#lti-130-link-generation-and-response)

## LTI Global configuration

The following environment variables are LTI version agnostic configurations:

| Variable | Description |
| ---------|-------------|
| `LTI_VERSION` | LTI version [Supported versions: `1.1.1`, `1.3.0` ] |
| `LTI_LAUNCH_PRESENTATION_RETURN_URL` | LTI return link after finishing the assignment. [Example: `https://test-taker-portal.com/index.html` ] |
| `LTI_LAUNCH_PRESENTATION_LOCALE` | Defines the localisation of LTI instance. [Examples: `en-EN`, `it-IT` ] |
| `LTI_OUTCOME_XML_NAMESPACE` | Defines the LTI outcome XML namespace [Recommended value: `http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0` ]. More information [here](https://www.imsglobal.org/specs/ltiv1p1p1/implementation-guide#toc-26). |

## LTI 1.1.1

LTI 1.1.1 was released in August 2014 to add the ability to the tools to pass grades back to the invoking system.

The full LTI 1.1.1 specification can be checked at [IMS Global](https://www.imsglobal.org/specs/ltiv1p1p1/implementation-guide) website.

### LTI 1.1.1 Configuration

Configure environment variables according to the tool you are integrating with the application.

| Variable | Description |
| ------------- |:-------|
| `LTI_INSTANCE_LOAD_BALANCING_STRATEGY` | LTI load balancing strategy. [Possible values: `username`, `userGroupId`] For more information check [LTI 1.1.1 Load balancing strategy](#lti-111-load-balancing-strategy) section. |

With environment variables configured, you should create your LTI Instances using the [LTI instance ingester command](../cli/lti-instance-ingester-command.md).

### LTI 1.1.1 Load balancing strategy

With the help of load balancing strategy we can decide how to distribute the users across multiple LTI instances.

There are two different load balancing strategies that can be applied. It's configurable through the 
`LTI_INSTANCE_LOAD_BALANCING_STRATEGY` environment variable.

| Strategy | Description | LTI context id |
| -------------|-------------|-----------|
| username | Username based strategy (default)| `id` of `LineItem` of current `Assignment` |
| userGroupId | User group ID based strategy | `groupId` of `User` |

> **Note:** In order to apply the `userGroupId` strategy, the users must be ingested with `groupId` column specified, 
otherwise the ingestion will fail. For more information about ingestion please refer the related [user ingestion documentation](../cli/user-ingester-command.md).

> **Note 2:** The `contextId` LTI request parameter is automatically adjusted based on the active load balancing strategy.

### LTI 1.1.1 Link generation and Response

In order to get an LTI link, you need to execute the following request.

```http request
GET {{simple-roster-url}}/api/v1/assignments/1/lti-link (Requires authentication)
```

Considering Simple Roster was configured to generate LTI 1.1.1 Links, this should be the expected response.

```json
{
    "ltiVersion": "1.1.1",
    "ltiLink": "http://infra_1.com",
    "ltiParams": {
        "oauth_body_hash": "string",
        "oauth_consumer_key": "string",
        "oauth_nonce": "string",
        "oauth_signature": "string",
        "oauth_signature_method": "string",
        "oauth_timestamp": "string",
        "oauth_version": "string",
        "lti_message_type": "string",
        "lti_version": "string",
        "context_id": 0,
        "context_label": "string",
        "context_title": "string",
        "context_type": "string",
        "roles": "string",
        "user_id": 0,
        "lis_person_name_full": "string",
        "resource_link_id": 0,
        "lis_outcome_service_url": "string",
        "lis_result_sourcedid": 0,
        "launch_presentation_return_url": "string",
        "launch_presentation_locale": "it-IT"
    }
}
```

## LTI 1.3.0

The LTI 1.3.0 was created to solve some security flaws from its previous versions. With its creation, all other past versions are now considered deprecated.
The full LTI 1.3 specification can be checked at [IMS Global](http://www.imsglobal.org/spec/lti/v1p3/) website.

### LTI 1.3.0 Configuration

Configure environment variables according to the tool you are integrating with the application.

| Variable | Description |
| -------- |:------------|
| `LTI1P3_SERVICE_ENCRYPTION_KEY` | Key used for security signature |
| `LTI1P3_REGISTRATION_ID` | ID used to find the configured registration |
| `LTI1P3_PLATFORM_AUDIENCE` | Platform Audience |
| `LTI1P3_PLATFORM_OIDC_AUTHENTICATION_URL` | Platform OIDC authentication URL |
| `LTI1P3_PLATFORM_OAUTH2_ACCESS_TOKEN_URL` | Platform OAUTH2 access token generation URL |
| `LTI1P3_TOOL_AUDIENCE` | Tool Audience |
| `LTI1P3_TOOL_OIDC_INITIATION_URL` | Tool OIDC initiation URL |
| `LTI1P3_TOOL_LAUNCH_URL` | Tool launch URL |
| `LTI1P3_TOOL_CLIENT_ID` | Tool Client Id |
| `LTI1P3_TOOL_JWKS_URL` | Tool JWKS (JSON Web Key Sets) URL |

For more details about these settings, please check [LTI 1.3.0 Symfony Bundle documentation](https://github.com/oat-sa/bundle-lti1p3/blob/master/doc/quickstart/configuration.md).


Create your key pair running:

```shell script
openssl genrsa -out config/secrets/private.key
openssl rsa -in config/secrets/private.key -outform PEM -pubout -out config/secrets/public.key
```

Configure the settings on `config/packages/lti1p3.yaml`.
- Configure the `keychain` section
- Define the `platform` settings
- Define the `tool` settings
- Add a `registration`

For more details about these settings, please check [LTI 1.3.0 Symfony Bundle documentation](https://github.com/oat-sa/bundle-lti1p3/blob/master/doc/quickstart/configuration.md).

### LTI 1.3.0 Link generation and Response

 LTI link, you need to execute the following request.

```http request
GET {{simple-roster-url}}/api/v1/assignments/1/lti-link (Requires authentication)
```

Considering Simple Roster was configured to generate LTI 1.3.0 Links, this should be the expected response.

```json
{
    "ltiLink": "http://tool-url/lti1p3/oidc/initiation?extensive-list-of-parameters",
    "ltiVersion": "1.3.0",
    "ltiParams": []
}
```
