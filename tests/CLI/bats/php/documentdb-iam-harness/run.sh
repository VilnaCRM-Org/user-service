#!/bin/sh
# Runs the MONGODB-AWS client harness against local mock services.
#
# Needs a PHP image with ext-mongodb and NET_ADMIN, to give the loopback the
# ECS credentials address 169.254.170.2. From the repository root:
#
#   docker compose run --rm --no-deps --cap-add NET_ADMIN --entrypoint sh php \
#     tests/CLI/bats/php/documentdb-iam-harness/run.sh
#
# This proves how the client behaves. It does not prove that DocumentDB
# accepts the role; only the live TEST check can.
set -eu

cd "$(dirname "$0")"
state="$(mktemp -d)"
port=27099

ip addr add 169.254.170.2/32 dev lo
php mock-services.php "$state" "$port" &
mock_pid=$!
trap 'kill "$mock_pid" 2>/dev/null || true; rm -rf "$state"' EXIT

for _ in $(seq 1 50); do
	[ -f "$state/ready" ] && break
	sleep 0.1
done
[ -f "$state/ready" ] || { echo "mock services did not start" >&2; exit 1; }

# No static AWS keys and no profile: the only credential source is ECS.
unset AWS_ACCESS_KEY_ID AWS_SECRET_ACCESS_KEY AWS_SESSION_TOKEN AWS_PROFILE
php run-scenarios.php "$state" "$port"
