#!/usr/bin/env bash
set -euo pipefail

REGION="${AWS_DEFAULT_REGION:-eu-west-1}"

awslocal sqs create-queue --region "${REGION}" --queue-name rostering-files-uploaded >/dev/null
awslocal sqs create-queue --region "${REGION}" --queue-name rostering-files-uploaded-sr >/dev/null
