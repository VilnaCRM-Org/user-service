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
for alias in alias/user-service-two-factor alias/user-service-jwt; do
  awslocal kms describe-key --key-id "$alias" >/dev/null
done
