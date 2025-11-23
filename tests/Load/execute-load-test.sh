#!/bin/bash
set -e

if [ -z "$1" ]; then
    echo "Error: scenario not provided."
    exit 1
fi

if [ -z "$2" ]; then
    echo "Error: runSmoke not provided."
    exit 1
fi

if [ -z "$3" ]; then
    echo "Error: runAverage not provided."
    exit 1
fi

if [ -z "$4" ]; then
    echo "Error: runStress not provided."
    exit 1
fi

if [ -z "$5" ]; then
    echo "Error: runSpike not provided."
    exit 1
fi

scenario=$1
runSmoke=$2
runAverage=$3
runStress=$4
runSpike=$5
htmlPrefix=$6

ENV_VARS=""
if [ -n "${API_HOST:-}" ]; then
  ENV_VARS="${ENV_VARS} -e API_HOST=${API_HOST}"
fi

if [ -n "${API_PORT:-}" ]; then
  ENV_VARS="${ENV_VARS} -e API_PORT=${API_PORT}"
fi

K6="docker run --user $(id -u):$(id -g) -v ./tests/Load:/loadTests --net=host --rm ${ENV_VARS} \
    k6 run --summary-trend-stats='avg,min,med,max,p(95),p(99)' \
    --out 'web-dashboard=period=1s&export=/loadTests/loadTestsResults/${htmlPrefix}${scenario}.html'"

if [[ $scenario != "createUser" && $scenario != "graphQLCreateUser" && $scenario != "createUserBatch" ]]; then
  eval "$K6" /loadTests/utils/prepareUsers.js -e scenarioName="${scenario}" -e run_smoke="${runSmoke}" -e run_average="${runAverage}" -e run_stress="${runStress}" -e run_spike="${runSpike}"
fi

eval "$K6" "/loadTests/scripts/${scenario}.js" -e run_smoke="${runSmoke}" -e run_average="${runAverage}" -e run_stress="${runStress}" -e run_spike="${runSpike}"
