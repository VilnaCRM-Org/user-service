#!/bin/bash
set -e

if [ -z "$1" ]; then
    echo "Error: clientName not provided."
    exit 1
fi

if [ -z "$2" ]; then
    echo "Error: clientID not provided."
    exit 1
fi

if [ -z "$3" ]; then
    echo "Error: clientSecret not provided."
    exit 1
fi

if [ -z "$4" ]; then
    echo "Error: clientRedirectUri not provided."
    exit 1
fi

clientName=$1
clientID=$2
clientSecret=$3
clientRedirectUri=$4
clientPoolSize=20
commandRetries=${LOAD_TEST_OAUTH_CLIENT_COMMAND_RETRIES:-3}
commandRetryDelaySeconds=${LOAD_TEST_OAUTH_CLIENT_COMMAND_RETRY_DELAY_SECONDS:-2}

if [[ ! "$commandRetries" =~ ^[1-9][0-9]*$ ]]; then
    echo "Error: LOAD_TEST_OAUTH_CLIENT_COMMAND_RETRIES must be a positive integer." >&2
    exit 1
fi

if [[ ! "$commandRetryDelaySeconds" =~ ^[0-9]+$ ]]; then
    echo "Error: LOAD_TEST_OAUTH_CLIENT_COMMAND_RETRY_DELAY_SECONDS must be a non-negative integer." >&2
    exit 1
fi

run_symfony_command() {
    local command
    local attempt=1

    command=$(printf '%q ' "$@")

    while true; do
        if eval "${SYMFONY} ${command}"; then
            return 0
        fi

        if [ "$attempt" -ge "$commandRetries" ]; then
            return 1
        fi

        echo "Retrying Symfony command after failure: $*"
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
