# Learning Tools Interoperability (LTI)

>Learning Tools Interoperability (LTI) is an education technology specification developed by the IMS Global Learning Consortium. It specifies a method for a learning system to invoke and to communicate with external systems.

Simple Roster is capable of generate links for the LTI versions `1.1.1` and `1.3.0`. Please check the configuration section for each version.

## Table of Contents
- [LTI 1.1.1](#lti-111)
    - [LTI 1.1.1 Configuration](#lti-111-configuration)
    - [LTI 1.1.1 Link Generation](#lti-111-link-generation-and-response)
- [LTI 1.3.0](#lti-130)
    - [LTI 1.3.0 Configuration](#lti-130-configuration)
    - [LTI 1.3.0 Link Generation](#lti-130-link-generation-and-response)

### LTI 1.1.1

LTI 1.1.1 was released in August 2014 to add the ability to the tools to pass grades back to the invoking system.

The full LTI 1.1.1 specification can be checked at [IMS Global](https://www.imsglobal.org/specs/ltiv1p1p1/implementation-guide) website.

#### LTI 1.1.1 Configuration

Configure environment variables according to the tool you are connecting to simple roster. For a complete list of environment variable, please check [DevOps documentation - Lti Related Environment Variables](devops-documentation.md#lti-related-environment-variables).

With environment variables configured, you should create your LTI Instances using the [LTI instance ingester command](../cli/lti-instance-ingester-command.md).

#### LTI 1.1.1 Link generation and Response

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

### LTI 1.3.0

The LTI 1.3.0 was created to solve some security flaws from its previous versions. With its creation, all other past versions are now considered deprecated.
The full LTI 1.3 specification can be checked at [IMS Global](http://www.imsglobal.org/spec/lti/v1p3/) website.

#### LTI 1.3.0 Configuration

Configure environment variables according to the tool you are connecting to simple roster. For a complete list of environment variable, please check [DevOps documentation - Lti Related Environment Variables](devops-documentation.md#lti-related-environment-variables).

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

For more details about these settings, please check [LTI 1.3.0 Bundle documentation](https://github.com/oat-sa/bundle-lti1p3/blob/master/doc/quickstart/configuration.md)

#### LTI 1.3.0 Link generation and Response

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