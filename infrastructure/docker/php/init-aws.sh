#!/bin/sh

awslocal sqs create-queue --queue-name send-email
awslocal sqs create-queue --queue-name failed-emails
awslocal sqs create-queue --queue-name insert-user
awslocal sqs create-queue --queue-name health-check-queue
awslocal sqs create-queue --queue-name domain-events
awslocal sqs create-queue --queue-name failed-domain-events

# Local stand-in for the JWT signing KMS key (S5.11, FR-06): an RSA SIGN_VERIFY
# key whose alias the dev, test, load_test and schemathesis environments use as
# JWT_KMS_KEY_ID. Production uses the BI-owned RSA_4096 key ARN. The stand-in is
# RSA_2048 because LocalStack takes about 250 ms per RSA_4096 Sign (about 45 ms
# for RSA_2048), which would distort the Behat timing and load-test checks; the
# application accepts any RSA SIGN_VERIFY key with RSASSA_PKCS1_V1_5_SHA_256.
JWT_KMS_ALIAS=alias/user-service-jwt
if ! awslocal kms describe-key --key-id "$JWT_KMS_ALIAS" >/dev/null 2>&1; then
  jwt_key_id=$(awslocal kms create-key \
    --description 'Local user-service JWT signing key' \
    --key-usage SIGN_VERIFY \
    --key-spec RSA_2048 \
    --query KeyMetadata.KeyId --output text)
  awslocal kms create-alias \
    --alias-name "$JWT_KMS_ALIAS" \
    --target-key-id "$jwt_key_id"
fi

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
