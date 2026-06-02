#!/bin/bash
set -euo pipefail

SCRIPT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
REPO_ROOT=$(cd "$SCRIPT_DIR/../.." && pwd)

cd "$REPO_ROOT"

loadTestComposeProject=${LOAD_TEST_COMPOSE_PROJECT:-user-service-load-tests}
loadTestComposeFile=${LOAD_TEST_COMPOSE_FILE:-docker-compose.load-tests.yml}
loadTestPhpService=${LOAD_TEST_PHP_SERVICE:-php}
loadTestMailerService=${LOAD_TEST_MAILER_SERVICE:-mailer}
settleSeconds=${LOAD_TEST_RECOVERY_SETTLE_SECONDS:-5}
composeCmd=(docker compose -p "$loadTestComposeProject")

if [[ ! "$settleSeconds" =~ ^[0-9]+$ ]]; then
  echo "Error: LOAD_TEST_RECOVERY_SETTLE_SECONDS must be a non-negative integer." >&2
  exit 1
fi

IFS=':' read -r -a composeFiles <<< "$loadTestComposeFile"

for composeFile in "${composeFiles[@]}"; do
  if [ -z "$composeFile" ]; then
    continue
  fi

  composeCmd+=(-f "$composeFile")
done

echo "Restarting load-test services: $loadTestPhpService $loadTestMailerService"
"${composeCmd[@]}" restart "$loadTestPhpService" "$loadTestMailerService"
"${composeCmd[@]}" up --detach --wait "$loadTestPhpService" "$loadTestMailerService"

if [ "$settleSeconds" -gt 0 ]; then
  sleep "$settleSeconds"
fi
