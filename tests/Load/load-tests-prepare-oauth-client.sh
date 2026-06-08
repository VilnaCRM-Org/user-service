#!/bin/bash
set -euo pipefail

if [ "$#" -gt 3 ]; then
    echo "Error: OAuth client secrets must be read from config or environment, not argv." >&2
    exit 1
fi

configFile=${LOAD_TEST_CONFIG_FILE:-tests/Load/config.prod.json}
loadTestComposeProject=${LOAD_TEST_COMPOSE_PROJECT:-user-service-load-tests}
loadTestComposeFile=${LOAD_TEST_COMPOSE_FILE:-docker-compose.load-tests.yml}
loadTestPhpService=${LOAD_TEST_PHP_SERVICE:-php}
clientPoolSize=${LOAD_TEST_OAUTH_CLIENT_POOL_SIZE:-20}
commandRetries=${LOAD_TEST_OAUTH_CLIENT_COMMAND_RETRIES:-3}
commandRetryDelaySeconds=${LOAD_TEST_OAUTH_CLIENT_COMMAND_RETRY_DELAY_SECONDS:-2}

composeCmd=(docker compose -p "$loadTestComposeProject")

IFS=':' read -r -a composeFiles <<< "$loadTestComposeFile"

for composeFile in "${composeFiles[@]}"; do
    if [ -z "$composeFile" ]; then
        continue
    fi

    composeCmd+=(-f "$composeFile")
done

symfonyCmd=("${composeCmd[@]}" exec -T "$loadTestPhpService" bin/console)

read_config_value() {
    local query=$1

    jq -r "$query" "$configFile"
}

clientName=${LOAD_TEST_OAUTH_CLIENT_NAME:-${1:-$(read_config_value '.endpoints.oauth.clientName')}}
clientID=${LOAD_TEST_OAUTH_CLIENT_ID:-${2:-$(read_config_value '.endpoints.oauth.clientID')}}
clientRedirectUri=${LOAD_TEST_OAUTH_CLIENT_REDIRECT_URI:-${3:-$(read_config_value '.endpoints.oauth.clientRedirectUri')}}
clientSecret=${LOAD_TEST_OAUTH_CLIENT_SECRET:-$(read_config_value '.endpoints.oauth.clientSecret')}

require_config_value() {
    local value=$1
    local name=$2

    if [ -z "$value" ] || [ "$value" = "null" ]; then
        echo "Error: ${name} not configured." >&2
        exit 1
    fi
}

require_config_value "$clientName" "clientName"
require_config_value "$clientID" "clientID"
require_config_value "$clientSecret" "clientSecret"
require_config_value "$clientRedirectUri" "clientRedirectUri"

if [[ ! "$clientPoolSize" =~ ^[1-9][0-9]*$ ]]; then
    echo "Error: LOAD_TEST_OAUTH_CLIENT_POOL_SIZE must be a positive integer." >&2
    exit 1
fi

if [[ ! "$commandRetries" =~ ^[1-9][0-9]*$ ]]; then
    echo "Error: LOAD_TEST_OAUTH_CLIENT_COMMAND_RETRIES must be a positive integer." >&2
    exit 1
fi

if [[ ! "$commandRetryDelaySeconds" =~ ^[0-9]+$ ]]; then
    echo "Error: LOAD_TEST_OAUTH_CLIENT_COMMAND_RETRY_DELAY_SECONDS must be a non-negative integer." >&2
    exit 1
fi

run_symfony_command() {
    local attempt=1
    local commandName=$1

    while true; do
        if "${symfonyCmd[@]}" "$@"; then
            return 0
        fi

        if [ "$attempt" -ge "$commandRetries" ]; then
            return 1
        fi

        echo "Retrying Symfony command after failure: $commandName"
        sleep "$((commandRetryDelaySeconds * attempt))"
        attempt=$((attempt + 1))
    done
}

for (( i=0; i<clientPoolSize; i++ )); do
    poolClientID="${clientID}"
    poolClientSecret="${clientSecret}"
    if [ "$i" -gt 0 ]; then
        poolClientID="${clientID}-${i}"
        poolClientSecret="${clientSecret}-${i}"
    fi

    # Delete existing client if it exists
    if ! run_symfony_command league:oauth2-server:delete-client "${poolClientID}"; then
        echo "Warning: Failed to delete client ${poolClientID}. Proceeding to create a new one."
    fi

    # Create new client
    if ! run_symfony_command league:oauth2-server:create-client "${clientName}" "${poolClientID}" "${poolClientSecret}" --redirect-uri "${clientRedirectUri}" --grant-type client_credentials; then
        echo "Error: Failed to create client ${poolClientID}."
        exit 1
    fi
done

echo "Client pool '${clientName}' created successfully with base ID '${clientID}' and size '${clientPoolSize}'."
