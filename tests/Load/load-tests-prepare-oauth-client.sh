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

# Delete existing client if it exists
if ! eval "$SYMFONY" league:oauth2-server:delete-client "$clientID" --env=test; then
    echo "Warning: Failed to delete client $clientID. Proceeding to create a new one."
fi

# Create new client
if ! eval "$SYMFONY" league:oauth2-server:create-client "$clientName" "$clientID" "$clientSecret" "$clientRedirectUri" --env=dev; then
    echo "Error: Failed to create client $clientID."
    exit 1
fi
echo "Client $clientName created successfully with ID $clientID."
