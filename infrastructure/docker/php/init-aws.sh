#!/bin/sh
set -eu
# Enable pipefail when the running shell supports it (bash/zsh/ksh); /bin/sh on
# some distros does not, so guard it to keep the script POSIX-portable.
# shellcheck disable=SC3040
(set -o pipefail 2>/dev/null) && set -o pipefail

AWS_ENDPOINT_URL="http://localhost:4566"

aws --endpoint-url "$AWS_ENDPOINT_URL" --no-sign-request sqs create-queue --queue-name send-email
aws --endpoint-url "$AWS_ENDPOINT_URL" --no-sign-request sqs create-queue --queue-name failed-emails
aws --endpoint-url "$AWS_ENDPOINT_URL" --no-sign-request sqs create-queue --queue-name insert-user
