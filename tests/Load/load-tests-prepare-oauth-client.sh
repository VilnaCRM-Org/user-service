#!/bin/bash
set -e

# Check if clientName is provided
if [ -z "$1" ]; then
    echo "Error: clientName not provided."
    exit 1
fi

# Check if clientID is provided
if [ -z "$2" ]; then
    echo "Error: clientID not provided."
    exit 1
fi

# Check if clientSecret is provided
if [ -z "$3" ]; then
    echo "Error: clientSecret not provided."
    exit 1
fi

# Check if clientRedirectUri is provided
if [ -z "$4" ]; then
    echo "Error: clientRedirectUri not provided."
    exit 1
fi

clientName=$1
clientID=$2
clientSecret=$3
clientRedirectUri=$4

eval "$SYMFONY" league:oauth2-server:delete-client "$clientID" || true
eval "$SYMFONY" league:oauth2-server:create-client "$clientName" "$clientID" "$clientSecret" "$clientRedirectUri"