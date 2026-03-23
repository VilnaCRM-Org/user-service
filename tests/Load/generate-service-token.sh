#!/bin/bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PRIVATE_KEY="${SCRIPT_DIR}/../../config/jwt/private.pem"

if [ ! -f "$PRIVATE_KEY" ]; then
  echo "Error: JWT private key not found at ${PRIVATE_KEY}" >&2
  exit 1
fi

base64url_encode() {
  openssl enc -base64 -A | tr '+/' '-_' | tr -d '='
}

NOW=$(date +%s)
EXP=$((NOW + 900))
JTI=$(openssl rand -hex 16)
SID=$(openssl rand -hex 16)

HEADER='{"alg":"RS256","typ":"JWT"}'
PAYLOAD=$(printf '{"sub":"load-test-service","iss":"vilnacrm-user-service","aud":"vilnacrm-api","exp":%d,"iat":%d,"nbf":%d,"jti":"%s","sid":"%s","roles":["ROLE_SERVICE"]}' \
  "$EXP" "$NOW" "$NOW" "$JTI" "$SID")

HEADER_B64=$(printf '%s' "$HEADER" | base64url_encode)
PAYLOAD_B64=$(printf '%s' "$PAYLOAD" | base64url_encode)

UNSIGNED="${HEADER_B64}.${PAYLOAD_B64}"

SIGNATURE=$(printf '%s' "$UNSIGNED" | openssl dgst -sha256 -sign "$PRIVATE_KEY" -passin pass: -binary | base64url_encode)

printf '%s.%s' "$UNSIGNED" "$SIGNATURE"
