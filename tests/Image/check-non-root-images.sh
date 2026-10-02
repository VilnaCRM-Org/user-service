#!/usr/bin/env bash
# Verifies the production web and worker images: a fixed non-root user, no
# privileged port binding, application-owned writable paths, and passing health
# checks both with the image defaults and with the ECS task shape (read-only
# root filesystem, every Linux capability dropped, bootstrap command override).
# The web image also serves HTTPS on :8443 with a certificate from Caddy's
# internal CA, created at runtime in the application volume and renewed before
# it expires.
set -euo pipefail

ROOT_DIR=$(CDPATH='' cd -- "$(dirname -- "$0")/../.." && pwd)
readonly ROOT_DIR

readonly MINIMUM_UID=1000
readonly WEB_PORT=8080
readonly TLS_PORT=8443
readonly TLS_SERVER_NAME=user-service.internal
readonly CADDY_STORAGE=/srv/app/var/caddy
readonly CADDY_ROOT_CERTIFICATE="${CADDY_STORAGE}/pki/authorities/local/root.crt"
readonly CADDY_LEAF_DIRECTORY="${CADDY_STORAGE}/certificates/local/${TLS_SERVER_NAME}"
# The renewal check shortens the internal certificate lifetime and the renewal
# check interval (both in seconds); Caddy renews in the last third of the
# lifetime. Caddy's internal CA rejects a lifetime of one minute or less.
readonly RENEWAL_CERT_LIFETIME_SECONDS=${IMAGE_CHECK_RENEWAL_CERT_LIFETIME_SECONDS:-90}
readonly RENEWAL_CHECK_INTERVAL_SECONDS=${IMAGE_CHECK_RENEWAL_CHECK_INTERVAL_SECONDS:-5}
readonly RENEWAL_TIMEOUT_SECONDS=${IMAGE_CHECK_RENEWAL_TIMEOUT_SECONDS:-240}
readonly SUPERVISOR_SOCKET=/srv/app/var/run/supervisor.sock
readonly WEB_IMAGE_REF=${IMAGE_CHECK_WEB_IMAGE:-user-service-web:non-root-check}
readonly WORKER_IMAGE_REF=${IMAGE_CHECK_WORKER_IMAGE:-user-service-worker:non-root-check}
readonly BUILD_IMAGES=${IMAGE_CHECK_BUILD:-true}
readonly RUN_ID=${IMAGE_CHECK_RUN_ID:-$$}
readonly PREFIX="user-service-image-check-${RUN_ID}"
readonly NETWORK="${PREFIX}"
readonly MONGODB_IMAGE=${MONGODB_IMAGE:-mongo:8.0}
readonly REDIS_IMAGE=${IMAGE_CHECK_REDIS_IMAGE:-redis:8.0.0-alpine}
readonly LOCALSTACK_IMAGE=${IMAGE_CHECK_LOCALSTACK_IMAGE:-localstack/localstack:3.4.0}
readonly HEALTH_TIMEOUT_SECONDS=${IMAGE_CHECK_HEALTH_TIMEOUT_SECONDS:-300}
# The ECS task-role credential endpoint: the stub answers at this address on the
# internal network, and the containers find it through the relative URI only.
readonly TASK_ROLE_SUBNET=169.254.170.0/24
readonly TASK_ROLE_ENDPOINT_IP=169.254.170.2
readonly TASK_ROLE_RELATIVE_URI=/v2/credentials/image-runtime-check
# The stub reuses the pinned LocalStack image for its Python HTTP server.
readonly CREDENTIALS_STUB_IMAGE='localstack/localstack:3.4.0@sha256:54fcf172f6ff70909e1e26652c3bb4587282890aff0d02c20aa7695469476ac0'
readonly FAKE_KMS_KEY_ARN_PREFIX='arn:aws:kms:eu-central-1:123456789012:key'
# Runs share the fixed task-role subnet, so one run at a time per Docker host:
# a later run waits for this lock, then fails if the subnet is still taken.
readonly LOCK_FILE=${IMAGE_CHECK_LOCK_FILE:-/tmp/user-service-image-check.lock}
readonly LOCK_TIMEOUT_SECONDS=${IMAGE_CHECK_LOCK_TIMEOUT_SECONDS:-3600}
readonly MONGODB_USER=${MONGODB_USER:-root}
readonly MONGODB_PASSWORD=${MONGODB_PASSWORD:-secret}

# Docker lets every user bind any port by lowering this sysctl in the container
# network namespace; the kernel default (and the ECS task) keeps ports below 1024
# privileged, which is the boundary these checks exercise.
readonly PRIVILEGED_PORTS=(--sysctl net.ipv4.ip_unprivileged_port_start=1024)
# Fargate applies the read-only root filesystem and the dropped capabilities, but
# not no-new-privileges, so the ECS shape leaves it unset.
readonly ECS_TASK_SHAPE=(--read-only --cap-drop ALL)
readonly BOOTSTRAP='set -eu; install -d -m 700 /srv/app/var/run/secrets; install -d -m 1777 /srv/app/var/tmp; install -d -m 755 /srv/app/var/log /srv/app/var/run'
readonly WEB_RUNTIME_COMMAND="${BOOTSTRAP}; exec frankenphp run --config /etc/caddy/Caddyfile"
readonly WORKER_RUNTIME_COMMAND="${BOOTSTRAP}; exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf -l /srv/app/var/log/supervisord.log -j /srv/app/var/run/supervisord.pid"
readonly WEB_WRITABLE_PATHS='/srv/app/var /srv/app/var/cache /srv/app/var/log /srv/app/var/run /srv/app/var/tmp /data /config /srv/app/public/bundles /srv/app/config/jwt'
readonly WORKER_WRITABLE_PATHS='/srv/app/var /srv/app/var/cache /srv/app/var/log /srv/app/var/run /srv/app/var/tmp'
readonly READ_ONLY_CODE_PATHS='/srv/app /srv/app/src /srv/app/vendor /srv/app/public /srv/app/config /etc/caddy /etc/supervisor'
BIND_PROBE=$(
    cat <<'PHP'
<?php
$server = @stream_socket_server("tcp://0.0.0.0:" . $argv[1], $code, $message);
echo $server === false ? "refused: {$message}" : "bound", PHP_EOL;
exit($server === false ? 1 : 0);
PHP
)
readonly BIND_PROBE

failures=0
runtime_env_file=''
fixture_images=()
# The checked images: the IDs built (or resolved) under the host lock, tagged
# with tags unique to this run so no other run can retag them mid-check.
WEB_IMAGE=''
WORKER_IMAGE=''
WEB_IMAGE_ID=''
WORKER_IMAGE_ID=''

cleanup() {
    local containers=() image

    mapfile -t containers < <(docker ps -aq --filter "name=^${PREFIX}-")
    if [ "${#containers[@]}" -gt 0 ]; then
        docker rm -f -v "${containers[@]}" >/dev/null
    fi
    docker network rm "$NETWORK" >/dev/null 2>&1 || true
    if [ "${#fixture_images[@]}" -gt 0 ]; then
        docker image rm -f "${fixture_images[@]}" >/dev/null 2>&1 || true
    fi
    for image in "$WEB_IMAGE" "$WORKER_IMAGE"; do
        [ -z "$image" ] || docker image rm "$image" >/dev/null 2>&1 || true
    done
    [ -z "$runtime_env_file" ] || rm -f "$runtime_env_file"
}

trap cleanup EXIT

pass() {
    echo "PASS: $*"
}

fail() {
    echo "FAIL: $*" >&2
    failures=$((failures + 1))
}

check() {
    local description=$1
    shift

    if "$@"; then
        pass "$description"
    else
        fail "$description"
    fi
}

build_image() {
    local target=$1
    local reference=$2
    local iidfile id

    if [ "$BUILD_IMAGES" != 'true' ]; then
        docker image inspect --format '{{.Id}}' "$reference"
        return
    fi
    iidfile=$(mktemp)
    docker build --target "$target" --iidfile "$iidfile" -t "$reference" "$ROOT_DIR" >&2
    id=$(cat "$iidfile")
    rm -f "$iidfile"
    echo "$id"
}

build_images() {
    WEB_IMAGE_ID=$(build_image frankenphp_prod "$WEB_IMAGE_REF")
    WORKER_IMAGE_ID=$(build_image app_workers "$WORKER_IMAGE_REF")
    WEB_IMAGE="${WEB_IMAGE_REF%%:*}:image-check-${RUN_ID}"
    WORKER_IMAGE="${WORKER_IMAGE_REF%%:*}:image-check-${RUN_ID}"
    docker tag "$WEB_IMAGE_ID" "$WEB_IMAGE"
    docker tag "$WORKER_IMAGE_ID" "$WORKER_IMAGE"
    echo "built web image ${WEB_IMAGE_REF}: ${WEB_IMAGE_ID} (checked as ${WEB_IMAGE})"
    echo "built worker image ${WORKER_IMAGE_REF}: ${WORKER_IMAGE_ID} (checked as ${WORKER_IMAGE})"
}

image_id_of() {
    docker image inspect --format '{{.Id}}' "$1"
}

container_image_id() {
    docker inspect --format '{{.Image}}' "${PREFIX}-$1"
}

layers_of() {
    docker image inspect --format '{{range .RootFS.Layers}}{{.}} {{end}}' "$1"
}

# Every check ran the images built under the lock: the run tags still resolve
# to the recorded IDs, each runtime container ran them, and each fixture image
# was built on top of the web image.
checked_images_are_the_built_images() {
    local name expected actual fixture web_layers

    [ "$(image_id_of "$WEB_IMAGE")" = "$WEB_IMAGE_ID" ] || { echo "${WEB_IMAGE} moved" >&2; return 1; }
    [ "$(image_id_of "$WORKER_IMAGE")" = "$WORKER_IMAGE_ID" ] || { echo "${WORKER_IMAGE} moved" >&2; return 1; }
    for name in web-default web-ecs web-tls-renewal worker-default worker-ecs; do
        expected=$WEB_IMAGE_ID
        [[ "$name" != worker-* ]] || expected=$WORKER_IMAGE_ID
        if ! docker inspect "${PREFIX}-${name}" >/dev/null 2>&1; then
            continue
        fi
        actual=$(container_image_id "$name") || return 1
        echo "checked ${name} image: ${actual}"
        [ "$actual" = "$expected" ] || { echo "${name} ran ${actual}, built ${expected}" >&2; return 1; }
    done
    web_layers=$(layers_of "$WEB_IMAGE_ID") || return 1
    for fixture in "${fixture_images[@]}"; do
        if ! docker image inspect "$fixture" >/dev/null 2>&1; then
            continue
        fi
        [[ "$(layers_of "$fixture")" == "${web_layers}"* ]] \
            || { echo "${fixture} is not built on ${WEB_IMAGE_ID}" >&2; return 1; }
        echo "checked ${fixture}: built on ${WEB_IMAGE_ID}"
    done
    if docker inspect "${PREFIX}-privilege-fixture" >/dev/null 2>&1; then
        actual=$(container_image_id privilege-fixture) || return 1
        expected=$(image_id_of "${WEB_IMAGE%%:*}:privilege-fixture-${RUN_ID}") || return 1
        [ "$actual" = "$expected" ] || { echo "privilege-fixture ran ${actual}" >&2; return 1; }
    fi
    echo "checked web image ID: ${WEB_IMAGE_ID}; checked worker image ID: ${WORKER_IMAGE_ID}"
}

image_user_is_numeric_non_root() {
    local image=$1
    local user uid gid

    user=$(docker image inspect --format '{{.Config.User}}' "$image")
    echo "${image} USER=${user}"
    [[ "$user" =~ ^([0-9]+):([0-9]+)$ ]] || return 1
    uid=${BASH_REMATCH[1]}
    gid=${BASH_REMATCH[2]}
    [ "$uid" -ge "$MINIMUM_UID" ] && [ "$gid" -ge "$MINIMUM_UID" ]
}

process_uid_is_non_root() {
    local image=$1
    local uid

    uid=$(docker run --rm --entrypoint id "$image" -u)
    echo "${image} id -u=${uid}"
    [[ "$uid" =~ ^[0-9]+$ ]] && [ "$uid" -ge "$MINIMUM_UID" ]
}

bind_probe() {
    local image=$1
    local php_runner=$2
    local port=$3
    shift 3

    docker run --rm "${PRIVILEGED_PORTS[@]}" "$@" --entrypoint sh "$image" -c \
        'printf "%s" "$1" > /tmp/bind-probe.php; exec $2 /tmp/bind-probe.php "$3"' \
        bind-probe "$BIND_PROBE" "$php_runner" "$port"
}

web_bind_probe() {
    bind_probe "$WEB_IMAGE" 'frankenphp php-cli' "$@"
}

privileged_bind_is_refused() {
    local output

    if output=$("$@" 2>&1); then
        echo "unexpectedly bound: ${output}" >&2
        return 1
    fi
    echo "$output"
    [[ "$output" == *'refused: Permission denied'* ]]
}

writable_paths_belong_to_the_application_user() {
    local image=$1
    local paths=$2

    docker run --rm --entrypoint sh "$image" -c '
        uid=$(id -u)
        for path in $1; do
            [ "$(stat -c %u "$path")" = "$uid" ] && [ -w "$path" ] || { echo "not owned or writable: $path"; exit 1; }
        done
        for path in $2; do
            [ ! -w "$path" ] || { echo "application user can modify $path"; exit 1; }
        done
        writable=$(find /srv/app -xdev -path /srv/app/var -prune -o -perm -o+w ! -type l -print) || exit 1
        [ -z "$writable" ] || { echo "world-writable application files: $writable"; exit 1; }' \
        ownership "$paths" "$READ_ONLY_CODE_PATHS"
}

random_base64_key() {
    head -c 32 /dev/urandom | base64 | tr -d '\n'
}

# Throwaway production secrets for this run, synthetic KMS key ARNs in an
# obviously fake account, and the ECS task-role credential URI. No static AWS
# credential variable is set: the production guards refuse them. The SQS
# endpoint override points the queue client at LocalStack only.
write_runtime_secrets() {
    runtime_env_file=$(mktemp)
    chmod 600 "$runtime_env_file"
    {
        echo "APP_SECRET=$(random_base64_key)"
        echo "OAUTH_ENCRYPTION_KEY=$(random_base64_key)"
        echo "TWO_FACTOR_ENCRYPTION_KEY=$(random_base64_key)"
        echo 'AWS_REGION=eu-central-1'
        echo "JWT_KMS_KEY_ID=${FAKE_KMS_KEY_ARN_PREFIX}/00000000-0000-4000-8000-00000000f001"
        echo "TWO_FACTOR_KMS_KEY_ID=${FAKE_KMS_KEY_ARN_PREFIX}/00000000-0000-4000-8000-00000000f002"
        echo "AWS_CONTAINER_CREDENTIALS_RELATIVE_URI=${TASK_ROLE_RELATIVE_URI}"
        echo 'AWS_ENDPOINT_URL_SQS=http://localstack:4566'
    } >"$runtime_env_file"
}

CREDENTIALS_STUB=$(
    cat <<'PYTHON'
import datetime
import http.server
import json
import sys

relative_uri = sys.argv[1]


class Handler(http.server.BaseHTTPRequestHandler):
    def do_GET(self):
        if self.path != relative_uri:
            self.send_error(404)
            return
        expiration = datetime.datetime.now(datetime.timezone.utc) + datetime.timedelta(hours=1)
        body = json.dumps({
            "AccessKeyId": "test",
            "SecretAccessKey": "test",
            "Token": "fake-task-role-session",
            "Expiration": expiration.strftime("%Y-%m-%dT%H:%M:%SZ"),
        }).encode()
        self.send_response(200)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)


http.server.ThreadingHTTPServer(("0.0.0.0", 80), Handler).serve_forever()
PYTHON
)
readonly CREDENTIALS_STUB

start_credentials_stub() {
    docker run -d --name "${PREFIX}-credentials" --network "$NETWORK" --ip "$TASK_ROLE_ENDPOINT_IP" \
        --entrypoint python3 "$CREDENTIALS_STUB_IMAGE" -u -c "$CREDENTIALS_STUB" "$TASK_ROLE_RELATIVE_URI" \
        >/dev/null
}

acquire_host_lock() {
    if ! command -v flock >/dev/null; then
        echo 'FAIL: flock (util-linux) is required to serialize image checks on this Docker host.' >&2
        echo '1 non-root image check(s) failed.' >&2
        exit 1
    fi
    exec 9>"$LOCK_FILE"
    if ! flock -n 9; then
        echo "Waiting for another image check on this Docker host to release ${LOCK_FILE}"
        if ! flock -w "$LOCK_TIMEOUT_SECONDS" 9; then
            echo "Another image check on this Docker host still holds ${LOCK_FILE} after ${LOCK_TIMEOUT_SECONDS}s." >&2
            exit 1
        fi
    fi
    echo "Holding ${LOCK_FILE} since $(date -Is)"
}

ipv4_to_integer() {
    local a b c d
    IFS=. read -r a b c d <<<"$1"
    echo $(((a << 24) | (b << 16) | (c << 8) | d))
}

cidr_overlaps_task_role_subnet() {
    local address=${1%/*} bits=${1#*/}
    local own_address=${TASK_ROLE_SUBNET%/*} own_bits=${TASK_ROLE_SUBNET#*/}
    local shortest=$((bits < own_bits ? bits : own_bits))
    local mask=$(((0xFFFFFFFF << (32 - shortest)) & 0xFFFFFFFF))

    [[ "$1" =~ ^[0-9]+(\.[0-9]+){3}/[0-9]+$ ]] || return 1
    [ $(($(ipv4_to_integer "$address") & mask)) -eq $(($(ipv4_to_integer "$own_address") & mask)) ]
}

task_role_subnet_is_free() {
    local network subnet taken=''

    while read -r network subnet; do
        [ -n "$subnet" ] || continue
        if cidr_overlaps_task_role_subnet "$subnet"; then
            taken="${taken} ${network} (${subnet})"
        fi
    done < <(docker network ls -q | xargs -r docker network inspect \
        --format '{{.Name}}{{range .IPAM.Config}} {{.Subnet}}{{end}}' | awk '{for (i = 2; i <= NF; i++) print $1, $i}')

    [ -z "$taken" ] && return 0
    echo "The task-role subnet ${TASK_ROLE_SUBNET} is already used by Docker network(s):${taken}." >&2
    echo "Remove the network or wait for the image check that owns it." >&2
    return 1
}

# The internal network has no egress, so no request can leave for a real AWS endpoint.
start_dependencies() {
    docker network create --internal --subnet "$TASK_ROLE_SUBNET" "$NETWORK" >/dev/null
    start_credentials_stub
    docker run -d --name "${PREFIX}-database" --network "$NETWORK" --network-alias database \
        -e MONGO_INITDB_ROOT_USERNAME="$MONGODB_USER" -e MONGO_INITDB_ROOT_PASSWORD="$MONGODB_PASSWORD" \
        "$MONGODB_IMAGE" >/dev/null
    docker run -d --name "${PREFIX}-redis" --network "$NETWORK" --network-alias redis \
        "$REDIS_IMAGE" >/dev/null
    docker run -d --name "${PREFIX}-localstack" --network "$NETWORK" --network-alias localstack \
        -e SERVICES=sqs \
        -v "${ROOT_DIR}/infrastructure/docker/php/init-aws.sh:/etc/localstack/init/ready.d/init-aws.sh:ro" \
        "$LOCALSTACK_IMAGE" >/dev/null
    wait_until "SQS queues are ready" localstack_queues_ready
}

localstack_queues_ready() {
    docker exec "${PREFIX}-localstack" sh -c \
        'curl -fsS http://localhost:4566/_localstack/init/ready | grep -q "\"completed\": true"' 2>/dev/null
}

wait_until() {
    local description=$1
    shift
    local deadline=$((SECONDS + HEALTH_TIMEOUT_SECONDS))

    until "$@"; do
        if [ "$SECONDS" -ge "$deadline" ]; then
            echo "Timed out waiting until ${description}" >&2
            return 1
        fi
        sleep 3
    done
}

start_runtime_container() {
    local name=$1
    local image=$2
    shift 2
    local options=()

    while [ "$#" -gt 0 ] && [ "$1" != '--' ]; do
        options+=("$1")
        shift
    done
    [ "$#" -eq 0 ] || shift

    docker run -d --name "${PREFIX}-${name}" --network "$NETWORK" --env-file "$runtime_env_file" \
        "${PRIVILEGED_PORTS[@]}" --health-interval 5s "${options[@]}" "$image" "$@" >/dev/null
}

container_is_healthy() {
    local container="${PREFIX}-$1"
    local status

    status=$(docker inspect --format '{{.State.Status}} {{.State.Health.Status}}' "$container")
    case "$status" in
        'running healthy') return 0 ;;
        running*) return 1 ;;
    esac

    echo "${container} is not healthy: ${status}" >&2
    docker logs --tail 50 "$container" >&2 || true
    return 2
}

becomes_healthy() {
    local name=$1
    local deadline=$((SECONDS + HEALTH_TIMEOUT_SECONDS))
    local status

    while :; do
        status=0
        container_is_healthy "$name" || status=$?
        [ "$status" -eq 0 ] && return 0
        [ "$status" -eq 2 ] && return 1
        if [ "$SECONDS" -ge "$deadline" ]; then
            docker logs --tail 50 "${PREFIX}-${name}" >&2 || true
            return 1
        fi
        sleep 3
    done
}

health_endpoint_returns_no_content() {
    local code

    code=$(docker exec "${PREFIX}-$1" curl -sS -o /dev/null -w '%{http_code}' \
        "http://127.0.0.1:${WEB_PORT}/api/health")
    echo "${PREFIX}-$1 GET :${WEB_PORT}/api/health -> ${code}"
    [ "$code" = '204' ]
}

runs_as_non_root() {
    local uid

    uid=$(docker exec "${PREFIX}-$1" sh -c "awk '/^Uid:/ {print \$2}' /proc/1/status")
    echo "${PREFIX}-$1 PID 1 uid=${uid}"
    [ "$uid" -ge "$MINIMUM_UID" ]
}

runtime_writes_belong_to_the_application_user() {
    local name=$1
    local paths=()
    local foreign

    read -ra paths <<<"$2"
    foreign=$(docker exec "${PREFIX}-${name}" sh -c \
        'uid=$(id -u); [ "$uid" -ge "$1" ] || echo "runtime user $uid"; shift; find "$@" ! -user "$uid"' \
        find "$MINIMUM_UID" "${paths[@]}") || return 1
    [ -z "$foreign" ] || echo "${PREFIX}-${name} root or foreign-owned runtime paths: ${foreign}" >&2
    [ -z "$foreign" ]
}

pid_one_holds_no_capabilities() {
    local capabilities

    capabilities=$(docker exec "${PREFIX}-$1" grep -E '^Cap(Prm|Eff):' /proc/1/status) || return 1
    echo "${PREFIX}-$1 ${capabilities//$'\n'/ }"
    [ "$(grep -c '0000000000000000$' <<<"$capabilities")" -eq 2 ]
}

# The filesystem scans run as root so that no unreadable directory hides a file.
image_has_no_setuid_or_setgid_files() {
    local privileged

    privileged=$(docker exec -u 0:0 "${PREFIX}-$1" find / -xdev -perm /6000 -type f) || return 1
    [ -z "$privileged" ] || echo "${PREFIX}-$1 setuid/setgid files: ${privileged}" >&2
    [ -z "$privileged" ]
}

# A dedicated exit status (3) reports a missing getcap, which fails the check.
# getcap exits 0 even when it cannot read a file, so any scan error also fails.
image_has_no_file_capabilities() {
    local capabilities errors status=0

    errors=$(mktemp)
    capabilities=$(docker exec -u 0:0 "${PREFIX}-$1" sh -c \
        'command -v getcap >/dev/null || exit 3; find / -xdev -type f -exec getcap {} +' 2>"$errors") \
        || status=$?
    if [ "$status" -eq 3 ]; then
        echo "${PREFIX}-$1 has no getcap, so file capabilities cannot be ruled out" >&2
    elif [ "$status" -ne 0 ] || [ -s "$errors" ]; then
        echo "${PREFIX}-$1 file capability scan failed (status ${status}): $(cat "$errors")" >&2
        status=1
    fi
    rm -f "$errors"
    [ "$status" -eq 0 ] || return 1
    [ -z "$capabilities" ] || echo "${PREFIX}-$1 file capabilities: ${capabilities}" >&2
    [ -z "$capabilities" ]
}

check_privileges() {
    local name=$1

    check "${name} PID 1 holds no permitted or effective capabilities" \
        pid_one_holds_no_capabilities "$name"
    check "${name} has no setuid or setgid files" image_has_no_setuid_or_setgid_files "$name"
    check "${name} has no file capabilities" image_has_no_file_capabilities "$name"
}

supervisor_socket_belongs_to_the_application_user() {
    docker exec "${PREFIX}-$1" sh -c \
        "[ -S ${SUPERVISOR_SOCKET} ] && [ \"\$(stat -c %u ${SUPERVISOR_SOCKET})\" = \"\$(id -u)\" ]"
}

image_ships_no_local_or_test_files() {
    docker run --rm --entrypoint sh "$1" -c '
        if [ -d /srv/app/config/jwt ]; then
            keys=$(find /srv/app/config/jwt -type f) || exit 1
            [ -z "$keys" ] || { echo "key files in the image: $keys"; exit 1; }
        fi
        [ ! -e /srv/app/config/reference.php ] || { echo "config/reference.php is in the image"; exit 1; }
        [ ! -e /srv/app/tests ] || { echo "/srv/app/tests is in the image"; exit 1; }'
}

network_has_no_egress() {
    local internal routes

    internal=$(docker network inspect --format '{{.Internal}}' "$NETWORK") || return 1
    routes=$(docker exec "${PREFIX}-$1" ip route) || return 1
    echo "${NETWORK} internal=${internal}; ${PREFIX}-$1 routes: ${routes//$'\n'/; }"
    [ "$internal" = 'true' ] && ! grep -q '^default' <<<"$routes"
}

task_role_credentials_were_served() {
    local requests

    requests=$(docker logs "${PREFIX}-credentials" 2>&1) || return 1
    grep -q "\"GET ${TASK_ROLE_RELATIVE_URI} HTTP/1.1\" 200" <<<"$requests"
}

worker_healthcheck_passes() {
    docker exec "${PREFIX}-$1" /usr/local/bin/worker-healthcheck
}

exposed_ports_of() {
    docker image inspect --format '{{range $port, $_ := .Config.ExposedPorts}}{{$port}} {{end}}' "$1"
}

# The base image's own EXPOSE entries (80, 443, 2019) are inherited and cannot be
# removed; the production listeners are declared on the web image only.
exposed_ports_are_the_web_listeners() {
    local web worker port

    web=$(exposed_ports_of "$WEB_IMAGE") || return 1
    worker=$(exposed_ports_of "$WORKER_IMAGE") || return 1
    echo "web exposes: ${web}; worker exposes: ${worker:-nothing}"
    for port in "$WEB_PORT" "$TLS_PORT"; do
        [[ " ${web}" == *" ${port}/tcp "* ]] || return 1
        [[ " ${worker}" != *" ${port}/tcp "* ]] || return 1
    done
}

# The internal CA and every certificate are generated at runtime: the image holds
# no Caddy certificate storage and no private key or certificate in the writable
# paths. The Composer public keys under /config/composer are not TLS material.
image_ships_no_tls_material() {
    docker run --rm --entrypoint sh "$1" -c '
        [ ! -e "$1" ] || { echo "Caddy certificate storage is in the image: $1"; exit 1; }
        named=$(find /srv/app/var /data /config -name pki -o -name certificates -o -name "*.key" \
            -o -name "*.crt") || exit 1
        pem=$(grep -r -l -E "PRIVATE KEY|BEGIN CERTIFICATE" /srv/app/var /data /config) || [ $? -eq 1 ] || exit 1
        found=$(printf "%s\n%s" "$named" "$pem" | sed "/^$/d")
        [ -z "$found" ] || { echo "TLS material in the image: $found"; exit 1; }' \
        tls-material "$CADDY_STORAGE"
}

check_image_contract() {
    local image

    for image in "$WEB_IMAGE" "$WORKER_IMAGE"; do
        check "${image} declares a numeric USER with UID and GID >= ${MINIMUM_UID}" \
            image_user_is_numeric_non_root "$image"
        check "${image} runs as UID >= ${MINIMUM_UID}" process_uid_is_non_root "$image"
    done

    check "web binding :80 fails with Docker default capabilities" \
        privileged_bind_is_refused web_bind_probe 80
    check "web binding :80 fails with every capability dropped" \
        privileged_bind_is_refused web_bind_probe 80 --cap-drop ALL
    check "web binds :${WEB_PORT} with every capability dropped" \
        web_bind_probe "$WEB_PORT" --cap-drop ALL
    check "web binds :${TLS_PORT} with every capability dropped" \
        web_bind_probe "$TLS_PORT" --cap-drop ALL
    check "web image declares :${WEB_PORT} and :${TLS_PORT}; the worker image declares neither" \
        exposed_ports_are_the_web_listeners
    check "web image ships no TLS key, certificate or Caddy certificate storage" \
        image_ships_no_tls_material "$WEB_IMAGE"
    check "worker binding :80 fails" \
        privileged_bind_is_refused bind_probe "$WORKER_IMAGE" php 80
    check "web writable paths belong to the application user; code is read-only" \
        writable_paths_belong_to_the_application_user "$WEB_IMAGE" "$WEB_WRITABLE_PATHS"
    check "worker writable paths belong to the application user; code is read-only" \
        writable_paths_belong_to_the_application_user "$WORKER_IMAGE" "$WORKER_WRITABLE_PATHS"
    for image in "$WEB_IMAGE" "$WORKER_IMAGE"; do
        check "${image} ships no JWT key files, config/reference.php or tests/" \
            image_ships_no_local_or_test_files "$image"
    done
}

# The certificate chains to the internal root CA that this container generated in
# its certificate storage, for the name the server presents.
https_health_endpoint_returns_no_content() {
    local code

    code=$(docker exec "${PREFIX}-$1" curl -sS -o /dev/null -w '%{http_code}' \
        --cacert "$CADDY_ROOT_CERTIFICATE" --resolve "${TLS_SERVER_NAME}:${TLS_PORT}:127.0.0.1" \
        "https://${TLS_SERVER_NAME}:${TLS_PORT}/api/health") || return 1
    echo "${PREFIX}-$1 GET https://${TLS_SERVER_NAME}:${TLS_PORT}/api/health (internal CA) -> ${code}"
    [ "$code" = '204' ]
}

# A load balancer connects by IP address without SNI and does not validate the
# certificate; the server still presents the internal certificate.
https_by_address_without_sni_returns_no_content() {
    local output

    output=$(docker exec "${PREFIX}-$1" curl -sS -k -o /dev/null -w '%{http_code} %{certs}' \
        "https://127.0.0.1:${TLS_PORT}/api/health") || return 1
    echo "${PREFIX}-$1 GET https://127.0.0.1:${TLS_PORT}/api/health -> ${output%% *}"
    [ "${output%% *}" = '204' ] \
        && grep -q "Subject Alternative Name:DNS:${TLS_SERVER_NAME}" <<<"$output" \
        && grep -q 'Issuer:CN = Caddy Local Authority' <<<"$output"
}

# Plain HTTP on the TLS port gets Go's fixed 400 reply: no application response,
# no redirect and no plain-HTTP fallback.
plain_http_on_tls_port_is_refused() {
    local name=$1 port=${2:-$TLS_PORT}
    local response status

    response=$(docker exec "${PREFIX}-${name}" curl -sS -i --max-time 10 \
        "http://127.0.0.1:${port}/api/health" 2>&1) || true
    status=$(head -n 1 <<<"$response" | tr -d '\r')
    echo "${PREFIX}-${name} plain GET :${port}/api/health -> ${status}"
    [ "$status" = 'HTTP/1.0 400 Bad Request' ] \
        && grep -q 'Client sent an HTTP request to an HTTPS server.' <<<"$response" \
        && ! grep -qi '^location:' <<<"$response"
}

# The internal CA keys and the issued certificate live in the application volume,
# readable only by the application user.
tls_material_is_private_to_the_application_user() {
    docker exec "${PREFIX}-$1" sh -c '
        uid=$(id -u)
        for path in "$1" "$1/pki/authorities/local/root.key" \
            "$1/pki/authorities/local/intermediate.key" "$2/$3.key" "$2/$3.crt"; do
            [ -e "$path" ] && [ "$(stat -c %u "$path")" = "$uid" ] \
                || { echo "missing or not application-owned: $path"; exit 1; }
        done
        [ "$(stat -c %a "$1")" = 700 ] || { echo "$1 is not mode 0700"; exit 1; }
        open=$(find "$1" -perm /077) || exit 1
        [ -z "$open" ] || { echo "group or world access: $open"; exit 1; }' \
        tls-material "$CADDY_STORAGE" "$CADDY_LEAF_DIRECTORY" "$TLS_SERVER_NAME"
}

# Prints "<serial>|<not before>|<not after>" (epoch seconds) of the certificate
# the server presents.
served_certificate() {
    local certificate fields serial start expiry

    certificate=$(docker exec "${PREFIX}-$1" curl -sS -k -o /dev/null -w '%{certs}' \
        "https://127.0.0.1:${TLS_PORT}/") || return 1
    fields=$(awk '/^Serial Number:/ && !serial {serial = substr($0, 15)}
        /^Start date:/ && !start {start = substr($0, 12)}
        /^Expire date:/ && !expiry {expiry = substr($0, 13)}
        END {if (serial == "" || start == "" || expiry == "") exit 1; print serial "|" start "|" expiry}' \
        <<<"$certificate") || return 1
    IFS='|' read -r serial start expiry <<<"$fields"
    echo "${serial}|$(date -d "$start" +%s)|$(date -d "$expiry" +%s)"
}

stored_certificate_serial() {
    docker exec "${PREFIX}-$1" php -r \
        'echo strtolower(ltrim(openssl_x509_parse(file_get_contents($argv[1]))["serialNumberHex"], "0"));' \
        "${CADDY_LEAF_DIRECTORY}/${TLS_SERVER_NAME}.crt"
}

# Caddy's certificate maintenance replaces the served certificate in the last third
# of its configured lifetime, before it expires, and writes the new one to its
# storage on the read-only root filesystem.
certificate_renews_before_expiry() {
    local name=$1
    local serial issued_at expires_at current next_serial next_issued_at next_expires_at
    local renewed_at window_opens served deadline=$((SECONDS + RENEWAL_TIMEOUT_SECONDS))

    IFS='|' read -r serial issued_at expires_at <<<"$(served_certificate "$name")"
    [ -n "$expires_at" ] || return 1
    echo "${PREFIX}-${name} serves serial ${serial}, valid $((expires_at - issued_at))s until $(date -u -d "@${expires_at}" +%T)"
    [ "$((expires_at - issued_at))" -eq "$RENEWAL_CERT_LIFETIME_SECONDS" ] || return 1
    while current=$(served_certificate "$name") && [ "${current%%|*}" = "$serial" ]; do
        if [ "$SECONDS" -ge "$deadline" ]; then
            echo "${PREFIX}-${name} still serves serial ${serial}" >&2
            return 1
        fi
        sleep 2
    done
    renewed_at=$(date +%s)
    IFS='|' read -r next_serial next_issued_at next_expires_at <<<"$current"
    [ -n "$next_expires_at" ] || return 1
    # Observed within one poll (plus one maintenance tick) of the actual renewal.
    window_opens=$((issued_at + RENEWAL_CERT_LIFETIME_SECONDS * 2 / 3 - RENEWAL_CHECK_INTERVAL_SECONDS))
    echo "${PREFIX}-${name} serves serial ${next_serial} from $((renewed_at - issued_at))s after issue, $((expires_at - renewed_at))s before the old expiry"
    [ "$renewed_at" -ge "$window_opens" ] || { echo 'renewed before the last third of the lifetime' >&2; return 1; }
    [ "$renewed_at" -lt "$expires_at" ] || { echo 'renewed only after the old certificate expired' >&2; return 1; }
    [ "$next_issued_at" -le "$renewed_at" ] && [ "$next_expires_at" -gt "$expires_at" ] || return 1
    served=${next_serial,,}
    served=${served#"${served%%[!0]*}"}
    [ "$(stored_certificate_serial "$name")" = "$served" ] \
        || { echo 'the stored certificate is not the served one' >&2; return 1; }
    docker logs "${PREFIX}-${name}" 2>&1 | grep 'certificate renewed successfully' \
        | grep -q "\"identifier\":\"${TLS_SERVER_NAME}\""
}

# A client that sends SNI for another name gets the internal certificate too
# (fallback_sni) and the catch-all site serves the application.
https_with_another_sni_returns_no_content() {
    local output

    output=$(docker exec "${PREFIX}-$1" curl -sS -k -o /dev/null -w '%{http_code} %{certs}' \
        --resolve "other.invalid:${TLS_PORT}:127.0.0.1" "https://other.invalid:${TLS_PORT}/api/health") \
        || return 1
    echo "${PREFIX}-$1 GET https://other.invalid:${TLS_PORT}/api/health -> ${output%% *}"
    [ "${output%% *}" = '204' ] && grep -q "Subject Alternative Name:DNS:${TLS_SERVER_NAME}" <<<"$output"
}

# The TLS server offers HTTP/1.1 and HTTP/2 only: no Alt-Svc advertisement and no
# UDP (HTTP/3) listener on the port.
tls_port_serves_no_http3() {
    local headers udp port_hex

    headers=$(docker exec "${PREFIX}-$1" curl -sS -k -D - -o /dev/null --http2 \
        "https://127.0.0.1:${TLS_PORT}/api/health") || return 1
    printf -v port_hex '%04X' "$TLS_PORT"
    udp=$(docker exec "${PREFIX}-$1" cat /proc/net/udp /proc/net/udp6) || return 1
    echo "${PREFIX}-$1 :${TLS_PORT} $(head -n 1 <<<"$headers" | tr -d '\r')"
    [[ "$(head -n 1 <<<"$headers")" == 'HTTP/2 204'* ]] \
        && ! grep -qi '^alt-svc:' <<<"$headers" \
        && ! awk '{print $2}' <<<"$udp" | grep -q ":${port_hex}$"
}

check_tls_runtime() {
    local name=$1

    check "${name} serves /api/health over HTTPS on :${TLS_PORT} with the internal CA certificate" \
        https_health_endpoint_returns_no_content "$name"
    check "${name} serves HTTPS on :${TLS_PORT} by address without SNI" \
        https_by_address_without_sni_returns_no_content "$name"
    check "${name} serves HTTPS on :${TLS_PORT} to a client sending another SNI" \
        https_with_another_sni_returns_no_content "$name"
    check "${name} serves HTTP/2 on :${TLS_PORT} with no HTTP/3 advertisement or UDP listener" \
        tls_port_serves_no_http3 "$name"
    check "${name} refuses plain HTTP on :${TLS_PORT} without a redirect" \
        plain_http_on_tls_port_is_refused "$name"
    check "${name} keeps the internal CA and certificate in ${CADDY_STORAGE}, private to the application user" \
        tls_material_is_private_to_the_application_user "$name"
}

check_web_runtime() {
    local name=$1

    if ! becomes_healthy "$name"; then
        fail "${name} becomes healthy through the image HEALTHCHECK"
        return
    fi
    pass "${name} becomes healthy through the image HEALTHCHECK"
    check "${name} serves /api/health on :${WEB_PORT}" health_endpoint_returns_no_content "$name"
    check_tls_runtime "$name"
    check "${name} PID 1 runs as UID >= ${MINIMUM_UID}" runs_as_non_root "$name"
    check "${name} wrote only application-owned files in its volumes" \
        runtime_writes_belong_to_the_application_user "$name" '/srv/app/var /data /config'
    check_privileges "$name"
}

check_certificate_renewal() {
    local name=web-tls-renewal

    if ! becomes_healthy "$name"; then
        fail "${name} becomes healthy through the image HEALTHCHECK"
        return
    fi
    pass "${name} becomes healthy through the image HEALTHCHECK"
    check "${name} renews its ${RENEWAL_CERT_LIFETIME_SECONDS}s certificate in its last third, before expiry, into ${CADDY_STORAGE}" \
        certificate_renews_before_expiry "$name"
    check "${name} serves /api/health over HTTPS with the renewed certificate" \
        https_health_endpoint_returns_no_content "$name"
    check "${name} wrote only application-owned files in its volumes" \
        runtime_writes_belong_to_the_application_user "$name" '/srv/app/var /data /config'
}

check_worker_runtime() {
    local name=$1

    if ! becomes_healthy "$name"; then
        fail "${name} becomes healthy through worker-healthcheck"
        return
    fi
    pass "${name} becomes healthy through worker-healthcheck"
    check "${name} worker-healthcheck passes" worker_healthcheck_passes "$name"
    check "${name} PID 1 runs as UID >= ${MINIMUM_UID}" runs_as_non_root "$name"
    check "${name} supervisor socket is ${SUPERVISOR_SOCKET} and application-owned" \
        supervisor_socket_belongs_to_the_application_user "$name"
    check "${name} wrote only application-owned files in its volume" \
        runtime_writes_belong_to_the_application_user "$name" '/srv/app/var'
    check_privileges "$name"
}

fails() {
    ! "$@"
}

seeded_world_writable_file_is_reported() {
    local output

    if output=$(writable_paths_belong_to_the_application_user "$1" "$WEB_WRITABLE_PATHS" 2>&1); then
        return 1
    fi
    echo "$output"
    [[ "$output" == *'world-writable application files: /srv/app/src/world-writable-fixture'* ]]
}

reports_exactly() {
    local expected=$1
    local output
    shift

    if output=$("$@" 2>&1); then
        echo "unexpectedly passed: ${output}" >&2
        return 1
    fi
    echo "$output"
    [[ "$output" == *"$expected"* ]]
}

build_fixture_image() {
    local tag=$1

    fixture_images+=("$tag")
    docker build -q -t "$tag" - >/dev/null
}

check_privilege_fixture() {
    local fixture="${WEB_IMAGE%%:*}:privilege-fixture-${RUN_ID}"
    local name=privilege-fixture

    if ! build_fixture_image "$fixture" <<DOCKERFILE; then
FROM ${WEB_IMAGE}
USER 0:0
RUN touch /usr/local/bin/setuid-fixture /usr/local/bin/setgid-fixture \
    && chmod 4755 /usr/local/bin/setuid-fixture && chmod 2755 /usr/local/bin/setgid-fixture \
    && cp /usr/bin/curl /usr/local/bin/file-capability-fixture \
    && setcap cap_net_raw+ep /usr/local/bin/file-capability-fixture
USER 10001:10001
DOCKERFILE
        fail "negative fixture: the privilege fixture image cannot be built from ${WEB_IMAGE}"
        return
    fi
    if ! docker run -d --name "${PREFIX}-${name}" --network none --entrypoint sleep "$fixture" 600 >/dev/null; then
        fail "negative fixture: the privilege fixture container cannot be started"
        return
    fi
    check "negative fixture: a setuid file fails the setuid/setgid scan and is named" \
        reports_exactly '/usr/local/bin/setuid-fixture' image_has_no_setuid_or_setgid_files "$name"
    check "negative fixture: a setgid file fails the setuid/setgid scan and is named" \
        reports_exactly '/usr/local/bin/setgid-fixture' image_has_no_setuid_or_setgid_files "$name"
    check "negative fixture: a non-FrankenPHP file capability fails the scan and is named" \
        reports_exactly '/usr/local/bin/file-capability-fixture cap_net_raw=ep' \
        image_has_no_file_capabilities "$name"
    if ! docker stop -t 0 "${PREFIX}-${name}" >/dev/null; then
        fail "negative fixture: the privilege fixture container cannot be stopped"
        return
    fi
    check "negative fixture: a stopped container fails the file capability check" \
        fails image_has_no_file_capabilities "$name"
}

check_world_writable_fixture() {
    local fixture="${WEB_IMAGE%%:*}:world-writable-fixture-${RUN_ID}"

    if ! build_fixture_image "$fixture" <<DOCKERFILE; then
FROM ${WEB_IMAGE}
USER 0:0
RUN touch /srv/app/src/world-writable-fixture && chmod o+w /srv/app/src/world-writable-fixture
USER 10001:10001
DOCKERFILE
        fail "negative fixture: the world-writable fixture image cannot be built from ${WEB_IMAGE}"
        return
    fi
    check "negative fixture: a seeded world-writable application file fails the ownership check" \
        seeded_world_writable_file_is_reported "$fixture"
}

check_tls_material_fixture() {
    local fixture="${WEB_IMAGE%%:*}:tls-material-fixture-${RUN_ID}"

    if ! build_fixture_image "$fixture" <<DOCKERFILE; then
FROM ${WEB_IMAGE}
RUN mkdir -p /srv/app/var/cache/fixture \
    && echo '-----BEGIN PRIVATE KEY-----' > /srv/app/var/cache/fixture/seeded
DOCKERFILE
        fail "negative fixture: the TLS material fixture image cannot be built from ${WEB_IMAGE}"
        return
    fi
    check "negative fixture: a private key baked into the image fails the TLS material check" \
        reports_exactly 'TLS material in the image: /srv/app/var/cache/fixture/seeded' \
        image_ships_no_tls_material "$fixture"
}

check_negative_fixtures() {
    check_world_writable_fixture
    check_tls_material_fixture
    check "negative fixture: an unreachable container fails the runtime ownership check" \
        fails runtime_writes_belong_to_the_application_user missing-container '/srv/app/var'
    check "negative fixture: an unreachable container fails the capability check" \
        fails pid_one_holds_no_capabilities missing-container
    check_privilege_fixture
}

# Against a healthy web container: a plain-HTTP listener fails the refusal check,
# and an unreachable container fails the HTTPS check.
check_tls_negative_fixtures() {
    check "negative fixture: the plain-HTTP listener on :${WEB_PORT} fails the plain-HTTP refusal check" \
        fails plain_http_on_tls_port_is_refused web-default "$WEB_PORT"
    check "negative fixture: an unreachable container fails the HTTPS check" \
        fails https_health_endpoint_returns_no_content missing-container
}

check_runtime() {
    if ! task_role_subnet_is_free; then
        fail "the task-role subnet ${TASK_ROLE_SUBNET} is free on this Docker host"
        return
    fi
    pass "the task-role subnet ${TASK_ROLE_SUBNET} is free on this Docker host"
    write_runtime_secrets
    start_dependencies

    start_runtime_container web-default "$WEB_IMAGE"
    start_runtime_container web-ecs "$WEB_IMAGE" "${ECS_TASK_SHAPE[@]}" \
        -e TMPDIR=/srv/app/var/tmp -- /bin/sh -ec "$WEB_RUNTIME_COMMAND"
    start_runtime_container web-tls-renewal "$WEB_IMAGE" "${ECS_TASK_SHAPE[@]}" \
        -e TMPDIR=/srv/app/var/tmp -e CADDY_INTERNAL_CERT_LIFETIME="${RENEWAL_CERT_LIFETIME_SECONDS}s" \
        -e CADDY_RENEW_INTERVAL="${RENEWAL_CHECK_INTERVAL_SECONDS}s" -- /bin/sh -ec "$WEB_RUNTIME_COMMAND"
    start_runtime_container worker-default "$WORKER_IMAGE"
    start_runtime_container worker-ecs "$WORKER_IMAGE" "${ECS_TASK_SHAPE[@]}" \
        -e TMPDIR=/srv/app/var/tmp -- /bin/sh -ec "$WORKER_RUNTIME_COMMAND"

    check_web_runtime web-default
    check_web_runtime web-ecs
    check_tls_negative_fixtures
    check_certificate_renewal
    check_worker_runtime worker-default
    check_worker_runtime worker-ecs
    check "the check network is internal and web-ecs has no default route" network_has_no_egress web-ecs
    check "the containers fetched task-role credentials from the stub endpoint" \
        task_role_credentials_were_served
}

acquire_host_lock
build_images
check_image_contract
check_negative_fixtures
check_runtime
check "every check ran the image IDs built under the host lock" checked_images_are_the_built_images

if [ "$failures" -ne 0 ]; then
    echo "${failures} non-root image check(s) failed." >&2
    exit 1
fi

echo 'All non-root image checks passed.'
