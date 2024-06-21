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

eval "$SYMFONY" league:oauth2-server:delete-client "$clientID" --env=test || true
eval "$SYMFONY" league:oauth2-server:create-client "$clientName" "$clientID" "$clientSecret" "$clientRedirectUri" --env=test