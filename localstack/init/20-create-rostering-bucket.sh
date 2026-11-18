#!/usr/bin/env bash
set -euo pipefail

REGION="${AWS_DEFAULT_REGION:-eu-west-1}"
BUCKET_NAME="${ROSTERING_BUCKET_NAME:-rostering-files}"

if ! awslocal s3api head-bucket --bucket "${BUCKET_NAME}" >/dev/null 2>&1; then
  awslocal s3api create-bucket \
    --region "${REGION}" \
    --bucket "${BUCKET_NAME}" \
    --create-bucket-configuration "LocationConstraint=${REGION}" >/dev/null
fi
