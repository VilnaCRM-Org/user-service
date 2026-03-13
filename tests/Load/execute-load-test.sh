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

serviceToken=$(docker compose exec -T php bash tests/Load/generate-service-token.sh | tr -d '\r\n')

if [ -z "$serviceToken" ]; then
  echo "Error: failed to generate service token for load tests."
  exit 1
fi

K6="docker run -v ./tests/Load:/loadTests --net=host --rm \
    --user $(id -u) \
    k6 run --summary-trend-stats='avg,min,med,max,p(95),p(99)' \
    --out 'web-dashboard=period=1s&export=/loadTests/loadTestsResults/${htmlPrefix}${scenario}.html'"

if [[ $scenario != "createUser" && $scenario != "graphQLCreateUser" && $scenario != "createUserBatch" ]]; then
  eval "$K6" /loadTests/utils/prepareUsers.js -e scenarioName="${scenario}" -e run_smoke="${runSmoke}" -e run_average="${runAverage}" -e run_stress="${runStress}" -e run_spike="${runSpike}" -e serviceToken="${serviceToken}"
  docker compose exec -T php bin/console app:load-test:attach-access-tokens
fi

scriptFile=$(find ./tests/Load/scripts -name "${scenario}.js" | head -1)
scriptRelPath="${scriptFile#./tests/Load/}"

eval "$K6" "/loadTests/${scriptRelPath}" -e run_smoke="${runSmoke}" -e run_average="${runAverage}" -e run_stress="${runStress}" -e run_spike="${runSpike}" -e serviceToken="${serviceToken}"
