#!/bin/bash
set -euo pipefail

SCRIPT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
REPO_ROOT=$(cd "$SCRIPT_DIR/../.." && pwd)

cd "$REPO_ROOT"

if [ -z "${1:-}" ]; then
    echo "Error: scenario not provided."
    exit 1
fi

if [ -z "${2:-}" ]; then
    echo "Error: runSmoke not provided."
    exit 1
fi

if [ -z "${3:-}" ]; then
    echo "Error: runAverage not provided."
    exit 1
fi

if [ -z "${4:-}" ]; then
    echo "Error: runStress not provided."
    exit 1
fi

if [ -z "${5:-}" ]; then
    echo "Error: runSpike not provided."
    exit 1
fi

scenario=$1
runSmoke=$2
runAverage=$3
runStress=$4
runSpike=$5
htmlPrefix=${6:-}
loadTestComposeProject=${LOAD_TEST_COMPOSE_PROJECT:-user-service-load-tests}
loadTestComposeFile=${LOAD_TEST_COMPOSE_FILE:-docker-compose.load-tests.yml}
loadTestApiHost=${LOAD_TEST_API_HOST:-localhost}
loadTestApiPort=${LOAD_TEST_API_PORT:-18081}
loadTestMailCatcherHttpPort=${LOAD_TEST_MAILCATCHER_HTTP_PORT:-1180}
composeCmd=(docker compose -p "$loadTestComposeProject")

IFS=':' read -r -a composeFiles <<< "$loadTestComposeFile"

for composeFile in "${composeFiles[@]}"; do
    if [ -z "$composeFile" ]; then
        continue
    fi

    composeCmd+=(-f "$composeFile")
done

scenario_requires_seeded_users() {
    case "$1" in
        createUser|graphQLCreateUser|createUserBatch|health|oauth|oauthSocialCallback|oauthSocialInitiate)
            return 1
            ;;
    esac

    return 0
}

serviceToken=$(
  "${composeCmd[@]}" exec -T php sh /srv/app/tests/Load/generate-service-token.sh | tr -d '\r\n'
)

if [ -z "$serviceToken" ]; then
  echo "Error: failed to generate service token for load tests."
  exit 1
fi

k6Cmd=(
  docker run
  -v "${REPO_ROOT}/tests/Load:/loadTests"
  --net=host
  --rm
  --user "$(id -u)"
  k6
  run
  --summary-trend-stats=avg,min,med,max,p\(95\),p\(99\)
  --out "web-dashboard=period=1s&export=/loadTests/loadTestsResults/${htmlPrefix}${scenario}.html"
)

if scenario_requires_seeded_users "$scenario"; then
  "${k6Cmd[@]}" /loadTests/utils/prepareUsers.js \
    -e "scenarioName=${scenario}" \
    -e "run_smoke=${runSmoke}" \
    -e "run_average=${runAverage}" \
    -e "run_stress=${runStress}" \
    -e "run_spike=${runSpike}" \
    -e "serviceToken=${serviceToken}" \
    -e "API_HOST=${loadTestApiHost}" \
    -e "API_PORT=${loadTestApiPort}" \
    -e "MAILCATCHER_PORT=${loadTestMailCatcherHttpPort}"
  "${composeCmd[@]}" exec -T php bin/console app:load-test:attach-access-tokens
fi

scriptFile=$(find ./tests/Load/scripts -name "${scenario}.js" | head -1)
if [ -z "$scriptFile" ]; then
  echo "Error: load-test scenario script not found for '${scenario}'." >&2
  exit 1
fi

scriptRelPath="${scriptFile#./tests/Load/}"

"${k6Cmd[@]}" "/loadTests/${scriptRelPath}" \
  -e "run_smoke=${runSmoke}" \
  -e "run_average=${runAverage}" \
  -e "run_stress=${runStress}" \
  -e "run_spike=${runSpike}" \
  -e "serviceToken=${serviceToken}" \
  -e "API_HOST=${loadTestApiHost}" \
  -e "API_PORT=${loadTestApiPort}" \
  -e "MAILCATCHER_PORT=${loadTestMailCatcherHttpPort}"
