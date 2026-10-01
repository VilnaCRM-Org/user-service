#!/bin/sh
# LocalStack is healthy only after init-aws.sh completed successfully, both SQS
# and KMS are running, and the local KMS keys exist: the 2FA key (S5.12) and the
# JWT signing key (S5.11).
set -eu

init=$(curl -fsS http://localhost:4566/_localstack/init/ready)
health=$(curl -fsS http://localhost:4566/_localstack/health)

echo "$init" | grep -q '"completed": true'
echo "$init" | grep -q '"state": "SUCCESSFUL"'
if echo "$init" | grep -q '"state": "ERROR"'; then
  exit 1
fi
echo "$health" | grep -q '"sqs": "running"'
echo "$health" | grep -q '"kms": "running"'
# KMS ListAliases over plain HTTP (LocalStack does not verify SigV4); awslocal
# would exceed the 3 s healthcheck timeout.
aliases=$(curl -fsS -X POST http://localhost:4566/ \
  -H 'X-Amz-Target: TrentService.ListAliases' \
  -H 'Content-Type: application/x-amz-json-1.1' \
  -H 'Authorization: AWS4-HMAC-SHA256 Credential=test/20240101/us-east-1/kms/aws4_request, SignedHeaders=host, Signature=0' \
  -d '{}')
for alias in alias/user-service-two-factor alias/user-service-jwt; do
  echo "$aliases" | grep -q "\"AliasName\": \"$alias\""
done
