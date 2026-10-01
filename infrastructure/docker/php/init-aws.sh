#!/bin/sh

awslocal sqs create-queue --queue-name send-email
awslocal sqs create-queue --queue-name failed-emails
awslocal sqs create-queue --queue-name insert-user
awslocal sqs create-queue --queue-name health-check-queue
awslocal sqs create-queue --queue-name domain-events
awslocal sqs create-queue --queue-name failed-domain-events

# Local stand-in for the JWT signing KMS key (S5.11, FR-06): an RSA_4096
# SIGN_VERIFY key whose alias the dev, test, load_test and schemathesis
# environments use as JWT_KMS_KEY_ID. Production uses the BI-owned key ARN.
JWT_KMS_ALIAS=alias/user-service-jwt
if ! awslocal kms describe-key --key-id "$JWT_KMS_ALIAS" >/dev/null 2>&1; then
  jwt_key_id=$(awslocal kms create-key \
    --description 'Local user-service JWT signing key' \
    --key-usage SIGN_VERIFY \
    --key-spec RSA_4096 \
    --query KeyMetadata.KeyId --output text)
  awslocal kms create-alias \
    --alias-name "$JWT_KMS_ALIAS" \
    --target-key-id "$jwt_key_id"
fi
