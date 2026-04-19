#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR=$(CDPATH='' cd -- "$(dirname -- "$0")/.." && pwd)
SCHEMATHESIS_IMAGE=${SCHEMATHESIS_IMAGE:-schemathesis/schemathesis:4.9.5}
SCHEMATHESIS_BASE_URL=${SCHEMATHESIS_BASE_URL:-http://localhost:8081}
SCHEMATHESIS_REPORT_DIR=$(mktemp -d "${TMPDIR:-/tmp}/schemathesis-report.XXXXXX")

readonly ROOT_DIR
readonly SCHEMATHESIS_IMAGE
readonly SCHEMATHESIS_BASE_URL
readonly SCHEMATHESIS_REPORT_DIR

cleanup() {
    set +e
    rm -rf "$SCHEMATHESIS_REPORT_DIR"
}

trap cleanup EXIT

compose_schemathesis() {
    docker compose -f docker-compose.yml -f docker-compose.schemathesis.yml "$@"
}

php_schemathesis() {
    compose_schemathesis exec -T php "$@"
}

refresh_seed_data() {
    php_schemathesis bin/console cache:pool:clear cache.app >/dev/null
    php_schemathesis bin/console app:seed-schemathesis-data >/dev/null
}

sign_in_access_token() {
    local email=$1
    local password=$2
    local body

    body=$(curl -sS \
        -X POST \
        "${SCHEMATHESIS_BASE_URL}/api/signin" \
        -H 'Content-Type: application/json' \
        --data "{\"email\":\"${email}\",\"password\":\"${password}\",\"rememberMe\":false}")

    printf '%s' "$body" | sed -n 's/.*"access_token":"\([^"]*\)".*/\1/p'
}

service_access_token() {
    php_schemathesis sh tests/Load/generate-service-token.sh | tr -d '\r\n'
}

run_schemathesis() {
    local label=$1
    shift

    local log_file="${SCHEMATHESIS_REPORT_DIR}/${label}.log"

    echo
    echo "==> ${label}"

    set +e
    docker run --rm --network=host \
        -v "${ROOT_DIR}/.github/openapi-spec:/data" \
        "${SCHEMATHESIS_IMAGE}" \
        run \
        --no-color \
        --checks all \
        /data/spec.yaml \
        --url "${SCHEMATHESIS_BASE_URL}" \
        --header 'X-Schemathesis-Test: cleanup-users' \
        "$@" 2>&1 | tee "${log_file}"
    local status=${PIPESTATUS[0]}
    set -e

    if [ "$status" -ne 0 ]; then
        return "$status"
    fi

    if grep -q '^=================================== WARNINGS' "${log_file}"; then
        echo "Schemathesis emitted warnings for ${label}; failing the validation run." >&2
        return 1
    fi
}

require_token() {
    local label=$1
    local token=$2

    if [ -n "$token" ]; then
        return 0
    fi

    echo "Unable to obtain ${label} access token for Schemathesis validation." >&2
    exit 1
}

compose_schemathesis up --detach --wait php redis mailer localstack >/dev/null
compose_schemathesis restart php >/dev/null
compose_schemathesis up --detach --wait php >/dev/null
php_schemathesis bin/console cache:clear >/dev/null

refresh_seed_data
run_schemathesis \
    examples-public \
    --phases=examples \
    --include-operation-id create_http \
    --include-operation-id confirm_http \
    --include-operation-id request_password_reset \
    --include-operation-id confirm_password_reset

refresh_seed_data
user_token=$(sign_in_access_token 'user@example.com' 'Password1!')
require_token 'user' "$user_token"
run_schemathesis \
    examples-user-self \
    --phases=examples \
    --include-operation-id api_users_id_get \
    --include-path '/api/users/{id}/resend-confirmation-email' \
    --header "Authorization: Bearer ${user_token}"

refresh_seed_data
update_token=$(sign_in_access_token 'update-user@example.com' 'Password1!')
require_token 'update user' "$update_token"
run_schemathesis \
    examples-user-update \
    --phases=examples \
    --include-operation-id api_users_id_put \
    --include-operation-id api_users_id_patch \
    --header "Authorization: Bearer ${update_token}"

refresh_seed_data
delete_token=$(sign_in_access_token 'delete-user@example.com' 'Password1!')
require_token 'delete user' "$delete_token"
run_schemathesis \
    examples-user-delete \
    --phases=examples \
    --include-operation-id api_users_id_delete \
    --header "Authorization: Bearer ${delete_token}"

refresh_seed_data
service_token=$(service_access_token)
require_token 'service' "$service_token"
run_schemathesis \
    examples-service \
    --phases=examples \
    --include-operation-id create_batch_http \
    --header "Authorization: Bearer ${service_token}"

refresh_seed_data
run_schemathesis \
    coverage-public \
    --phases=coverage \
    -n 1 \
    --include-operation-id create_http \
    --include-operation-id request_password_reset \
    --include-operation-id api_health_get

refresh_seed_data
user_token=$(sign_in_access_token 'user@example.com' 'Password1!')
require_token 'user' "$user_token"
run_schemathesis \
    coverage-user-self \
    --phases=coverage \
    -n 1 \
    --include-operation-id api_users_get_collection \
    --include-operation-id api_users_id_get \
    --include-path '/api/users/{id}/resend-confirmation-email' \
    --include-operation-id setup_2fa_http \
    --header "Authorization: Bearer ${user_token}"

refresh_seed_data
run_schemathesis \
    fuzzing-public \
    --phases=fuzzing \
    -n 1 \
    --include-operation-id create_http \
    --include-operation-id request_password_reset \
    --include-operation-id api_health_get

refresh_seed_data
user_token=$(sign_in_access_token 'user@example.com' 'Password1!')
require_token 'user' "$user_token"
run_schemathesis \
    fuzzing-user-self \
    --phases=fuzzing \
    -n 1 \
    --include-operation-id api_users_get_collection \
    --include-operation-id api_users_id_get \
    --include-operation-id setup_2fa_http \
    --header "Authorization: Bearer ${user_token}"
