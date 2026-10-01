#!/bin/sh

awslocal sqs create-queue --queue-name send-email
awslocal sqs create-queue --queue-name failed-emails
awslocal sqs create-queue --queue-name insert-user
awslocal sqs create-queue --queue-name health-check-queue
awslocal sqs create-queue --queue-name domain-events
awslocal sqs create-queue --queue-name failed-domain-events

# Local stand-in for the 2FA KMS key (S5.12): a symmetric key whose alias the
# dev, test, load_test and schemathesis environments use as TWO_FACTOR_KMS_KEY_ID.
TWO_FACTOR_KMS_ALIAS=alias/user-service-two-factor
if ! awslocal kms describe-key --key-id "$TWO_FACTOR_KMS_ALIAS" >/dev/null 2>&1; then
  two_factor_key_id=$(awslocal kms create-key \
    --description 'Local user-service 2FA key' \
    --key-usage ENCRYPT_DECRYPT \
    --key-spec SYMMETRIC_DEFAULT \
    --query KeyMetadata.KeyId --output text)
  awslocal kms create-alias \
    --alias-name "$TWO_FACTOR_KMS_ALIAS" \
    --target-key-id "$two_factor_key_id"
fi
