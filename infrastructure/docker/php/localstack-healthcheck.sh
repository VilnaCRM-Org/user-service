#!/bin/sh
# LocalStack is healthy only after init-aws.sh completed successfully and both
# SQS and KMS (the local 2FA key, S5.12) are running.
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
