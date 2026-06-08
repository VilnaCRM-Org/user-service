#!/bin/bash
set -euo pipefail

configFile=${LOAD_TEST_CONFIG_FILE:-tests/Load/config.prod.json}
clientID=${LOAD_TEST_OAUTH_CLIENT_ID:-$(jq -r '.endpoints.oauth.clientID' "$configFile")}
clientSecret=${LOAD_TEST_OAUTH_CLIENT_SECRET:-$(jq -r '.endpoints.oauth.clientSecret' "$configFile")}
clientPoolSize=${LOAD_TEST_OAUTH_CLIENT_POOL_SIZE:-20}
apiHost=${LOAD_TEST_API_HOST:-localhost}
apiPort=${LOAD_TEST_API_PORT:-18081}
timeoutSeconds=${LOAD_TEST_OAUTH_READY_TIMEOUT:-90}
tokenUrl="http://${apiHost}:${apiPort}/api/oauth/token"
deadline=$((SECONDS + timeoutSeconds))

if [ -z "$clientID" ] || [ "$clientID" = "null" ]; then
  echo "Error: clientID not configured."
  exit 1
fi

if [ -z "$clientSecret" ] || [ "$clientSecret" = "null" ]; then
  echo "Error: clientSecret not configured."
  exit 1
fi

resolve_pooled_credential() {
  local baseValue=$1
  local poolIndex=$2

  if [ "$poolIndex" -eq 0 ]; then
    printf '%s' "$baseValue"
    return
  fi

  printf '%s-%s' "$baseValue" "$poolIndex"
}

client_ready() {
  local poolIndex=$1
  local poolClientID
  local poolClientSecret
  local netrcFile
  local status

  poolClientID=$(resolve_pooled_credential "$clientID" "$poolIndex")
  poolClientSecret=$(resolve_pooled_credential "$clientSecret" "$poolIndex")
  netrcFile=$(mktemp)
  chmod 600 "$netrcFile"
  printf 'machine %s login %s password %s\n' "$apiHost" "$poolClientID" "$poolClientSecret" > "$netrcFile"

  status=$(
    curl \
      --silent \
      --show-error \
      --output /dev/null \
      --write-out '%{http_code}' \
      --connect-timeout 2 \
      --max-time 5 \
      --header 'Content-Type: application/json' \
      --netrc-file "$netrcFile" \
      --data '{"grant_type":"client_credentials"}' \
      "$tokenUrl" 2>/dev/null || true
  )
  rm -f "$netrcFile"

  [ "$status" = "200" ]
}

echo "Waiting for OAuth2 client pool to accept client_credentials tokens..."

while [ "$SECONDS" -lt "$deadline" ]; do
  missingClients=()

  for (( i=0; i<clientPoolSize; i++ )); do
    if ! client_ready "$i"; then
      missingClients+=("$i")
    fi
  done

  if [ "${#missingClients[@]}" -eq 0 ]; then
    echo "OAuth2 client pool is ready (${clientPoolSize} clients)."
    exit 0
  fi

  echo "OAuth2 client pool not ready yet; pending indexes: ${missingClients[*]}"
  sleep 2
done

echo "Error: OAuth2 client pool was not ready within ${timeoutSeconds}s." >&2
exit 1
