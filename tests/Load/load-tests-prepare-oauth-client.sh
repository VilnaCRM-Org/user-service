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

for (( i=0; i<clientPoolSize; i++ )); do
    poolClientID="${clientID}"
    poolClientSecret="${clientSecret}"
    if [ "$i" -gt 0 ]; then
        poolClientID="${clientID}-${i}"
        poolClientSecret="${clientSecret}-${i}"
    fi

    # Delete existing client if it exists
    if ! eval "${SYMFONY}" league:oauth2-server:delete-client "${poolClientID}"; then
        echo "Warning: Failed to delete client ${poolClientID}. Proceeding to create a new one."
    fi

    # Create new client
    if ! eval "${SYMFONY}" league:oauth2-server:create-client "${clientName}" "${poolClientID}" "${poolClientSecret}" "${clientRedirectUri}"; then
        echo "Error: Failed to create client ${poolClientID}."
        exit 1
    fi
done

echo "Client pool '${clientName}' created successfully with base ID '${clientID}' and size '${clientPoolSize}'."
