#!/bin/sh
set -eu

# Prints a ROLE_SERVICE access token for the load tests. The token is signed
# by the KMS JWT key (LocalStack in the load-test stack, S5.11); no local
# private key exists.
SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)

exec php "${SCRIPT_DIR}/../../bin/console" app:load-test:issue-service-token --no-debug
