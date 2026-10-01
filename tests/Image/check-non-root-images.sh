#!/usr/bin/env bash
# Verifies the production web and worker images: a fixed non-root user, no
# privileged port binding, application-owned writable paths, and passing health
# checks both with the image defaults and with the ECS task shape (read-only
# root filesystem, every Linux capability dropped, bootstrap command override).
set -euo pipefail

ROOT_DIR=$(CDPATH='' cd -- "$(dirname -- "$0")/../.." && pwd)
readonly ROOT_DIR

readonly MINIMUM_UID=1000
readonly WEB_PORT=8080
readonly SUPERVISOR_SOCKET=/srv/app/var/run/supervisor.sock
readonly WEB_IMAGE=${IMAGE_CHECK_WEB_IMAGE:-user-service-web:non-root-check}
readonly WORKER_IMAGE=${IMAGE_CHECK_WORKER_IMAGE:-user-service-worker:non-root-check}
readonly BUILD_IMAGES=${IMAGE_CHECK_BUILD:-true}
readonly RUN_ID=${IMAGE_CHECK_RUN_ID:-$$}
readonly PREFIX="user-service-image-check-${RUN_ID}"
readonly NETWORK="${PREFIX}"
readonly MONGODB_IMAGE=${MONGODB_IMAGE:-mongo:8.0}
readonly REDIS_IMAGE=${IMAGE_CHECK_REDIS_IMAGE:-redis:8.0.0-alpine}
readonly LOCALSTACK_IMAGE=${IMAGE_CHECK_LOCALSTACK_IMAGE:-localstack/localstack:3.4.0}
readonly HEALTH_TIMEOUT_SECONDS=${IMAGE_CHECK_HEALTH_TIMEOUT_SECONDS:-300}
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

cleanup() {
    local containers=()

    mapfile -t containers < <(docker ps -aq --filter "name=^${PREFIX}-")
    if [ "${#containers[@]}" -gt 0 ]; then
        docker rm -f -v "${containers[@]}" >/dev/null
    fi
    docker network rm "$NETWORK" >/dev/null 2>&1 || true
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

build_images() {
    if [ "$BUILD_IMAGES" != 'true' ]; then
        return
    fi

    docker build --target frankenphp_prod -t "$WEB_IMAGE" "$ROOT_DIR"
    docker build --target app_workers -t "$WORKER_IMAGE" "$ROOT_DIR"
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

# Throwaway production secrets for this run, and the LocalStack endpoint and
# placeholder credentials that replace the ECS task role for the SQS check.
write_runtime_secrets() {
    runtime_env_file=$(mktemp)
    chmod 600 "$runtime_env_file"
    {
        echo "APP_SECRET=$(random_base64_key)"
        echo "OAUTH_ENCRYPTION_KEY=$(random_base64_key)"
        echo "TWO_FACTOR_ENCRYPTION_KEY=$(random_base64_key)"
        echo 'AWS_ENDPOINT_URL_SQS=http://localstack:4566'
        echo 'AWS_ACCESS_KEY_ID=test'
        echo 'AWS_SECRET_ACCESS_KEY=test'
    } >"$runtime_env_file"
}

# The internal network has no egress, so no request can leave for a real AWS endpoint.
start_dependencies() {
    docker network create --internal "$NETWORK" >/dev/null
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

# getcap ships in the FrankenPHP base image; without it, the FrankenPHP binary
# itself must still be unable to bind a privileged port.
image_has_no_file_capabilities() {
    local capabilities

    if ! docker exec "${PREFIX}-$1" sh -c 'command -v getcap >/dev/null'; then
        echo "${PREFIX}-$1 has no getcap; probing the FrankenPHP binary instead"
        privileged_bind_is_refused bind_probe "$(docker inspect --format '{{.Config.Image}}' "${PREFIX}-$1")" \
            'frankenphp php-cli' 80
        return
    fi
    capabilities=$(docker exec -u 0:0 "${PREFIX}-$1" sh -c 'getcap -r / 2>/dev/null; exit 0') || return 1
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

worker_healthcheck_passes() {
    docker exec "${PREFIX}-$1" /usr/local/bin/worker-healthcheck
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
    check "worker binding :80 fails" \
        privileged_bind_is_refused bind_probe "$WORKER_IMAGE" php 80
    check "web writable paths belong to the application user; code is read-only" \
        writable_paths_belong_to_the_application_user "$WEB_IMAGE" "$WEB_WRITABLE_PATHS"
    check "worker writable paths belong to the application user; code is read-only" \
        writable_paths_belong_to_the_application_user "$WORKER_IMAGE" "$WORKER_WRITABLE_PATHS"
}

check_web_runtime() {
    local name=$1

    if ! check "${name} becomes healthy through the image HEALTHCHECK" becomes_healthy "$name"; then
        return
    fi
    check "${name} serves /api/health on :${WEB_PORT}" health_endpoint_returns_no_content "$name"
    check "${name} PID 1 runs as UID >= ${MINIMUM_UID}" runs_as_non_root "$name"
    check "${name} wrote only application-owned files in its volumes" \
        runtime_writes_belong_to_the_application_user "$name" '/srv/app/var /data /config'
    check_privileges "$name"
}

check_worker_runtime() {
    local name=$1

    if ! check "${name} becomes healthy through worker-healthcheck" becomes_healthy "$name"; then
        return
    fi
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

check_negative_fixtures() {
    local fixture="${WEB_IMAGE%%:*}:world-writable-fixture-${RUN_ID}"

    if ! docker build -q -t "$fixture" - >/dev/null <<DOCKERFILE; then
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
    docker image rm "$fixture" >/dev/null
    check "negative fixture: an unreachable container fails the runtime ownership check" \
        fails runtime_writes_belong_to_the_application_user missing-container '/srv/app/var'
    check "negative fixture: an unreachable container fails the capability check" \
        fails pid_one_holds_no_capabilities missing-container
}

check_runtime() {
    write_runtime_secrets
    start_dependencies

    start_runtime_container web-default "$WEB_IMAGE"
    start_runtime_container web-ecs "$WEB_IMAGE" "${ECS_TASK_SHAPE[@]}" \
        -e TMPDIR=/srv/app/var/tmp -- /bin/sh -ec "$WEB_RUNTIME_COMMAND"
    start_runtime_container worker-default "$WORKER_IMAGE"
    start_runtime_container worker-ecs "$WORKER_IMAGE" "${ECS_TASK_SHAPE[@]}" \
        -e TMPDIR=/srv/app/var/tmp -- /bin/sh -ec "$WORKER_RUNTIME_COMMAND"

    check_web_runtime web-default
    check_web_runtime web-ecs
    check_worker_runtime worker-default
    check_worker_runtime worker-ecs
}

build_images
check_image_contract
check_negative_fixtures
check_runtime

if [ "$failures" -ne 0 ]; then
    echo "${failures} non-root image check(s) failed." >&2
    exit 1
fi

echo 'All non-root image checks passed.'
