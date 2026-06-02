#!/bin/bash
set -euo pipefail

SCRIPT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
REPO_ROOT=$(cd "$SCRIPT_DIR/../.." && pwd)

cd "$REPO_ROOT"

loadTestComposeProject=${LOAD_TEST_COMPOSE_PROJECT:-user-service-load-tests}
loadTestComposeFile=${LOAD_TEST_COMPOSE_FILE:-docker-compose.load-tests.yml}
loadTestPhpService=${LOAD_TEST_PHP_SERVICE:-php}
loadTestRedisService=${LOAD_TEST_REDIS_SERVICE:-redis}
loadTestLockoutRedisService=${LOAD_TEST_LOCKOUT_REDIS_SERVICE:-$loadTestRedisService}
loadTestConfigFile=${LOAD_TEST_CONFIG_FILE:-tests/Load/config.prod.json}

composeCmd=(docker compose -p "$loadTestComposeProject")

IFS=':' read -r -a composeFiles <<< "$loadTestComposeFile"

for composeFile in "${composeFiles[@]}"; do
  if [ -z "$composeFile" ]; then
    continue
  fi

  composeCmd+=(-f "$composeFile")
done

read_config_value() {
  local query=$1

  jq -r "$query" "$loadTestConfigFile"
}

prepare_oauth_client_pool() {
  local symfonyCmd

  symfonyCmd=$(printf '%q ' "${composeCmd[@]}")
  symfonyCmd+=$(printf 'exec -T %q bin/console' "$loadTestPhpService")

  SYMFONY="$symfonyCmd" \
    "$REPO_ROOT/tests/Load/load-tests-prepare-oauth-client.sh" \
    "$(read_config_value '.endpoints.oauth.clientName')" \
    "$(read_config_value '.endpoints.oauth.clientID')" \
    "$(read_config_value '.endpoints.oauth.clientSecret')" \
    "$(read_config_value '.endpoints.oauth.clientRedirectUri')"
}

"${composeCmd[@]}" exec -T "$loadTestRedisService" redis-cli FLUSHALL >/dev/null

if [ "$loadTestLockoutRedisService" != "$loadTestRedisService" ]; then
  "${composeCmd[@]}" exec -T "$loadTestLockoutRedisService" redis-cli FLUSHALL >/dev/null
fi

"${composeCmd[@]}" exec -T -e APP_ENV=load_test "$loadTestPhpService" \
  bin/console doctrine:mongodb:schema:drop --skip-search-indexes >/dev/null 2>&1 || true
"${composeCmd[@]}" exec -T -e APP_ENV=load_test "$loadTestPhpService" \
  bin/console doctrine:mongodb:schema:create --skip-search-indexes >/dev/null
"${composeCmd[@]}" exec -T -e APP_ENV=load_test "$loadTestPhpService" \
  bin/console app:seed-test-oauth-client >/dev/null

prepare_oauth_client_pool >/dev/null
