#!/bin/bash
set -e

# Check if scenario is provided
if [ -z "$1" ]; then
    echo "Error: scenario not provided."
    exit 1
fi

# Check if runSmoke is provided
if [ -z "$2" ]; then
    echo "Error: runSmoke not provided."
    exit 1
fi

# Check if runAverage is provided
if [ -z "$3" ]; then
    echo "Error: runAverage not provided."
    exit 1
fi

# Check if runStress is provided
if [ -z "$4" ]; then
    echo "Error: runStress not provided."
    exit 1
fi

# Check if runSpike is provided
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

K6="docker run -v ./tests/Load:/loadTests --net=host --rm -u "$(id -u):$(id -g)" k6 run --summary-trend-stats='avg,min,med,max,p(95),p(99)' --out 'web-dashboard=period=1s&export=/loadTests/loadTestsResults/${htmlPrefix}${scenario}.html'"

if [[ $scenario != "createUser" && $scenario != "confirmUser" && $scenario != "graphQLCreateUser" && $scenario != "graphQLConfirmUser" && $scenario != "createUserBatch" ]]; then
  eval "$K6" /loadTests/utils/prepareUsers.js -e scenarioName="${scenario}" -e run_smoke="${runSmoke}" -e run_average="${runAverage}" -e run_stress="${runStress}" -e run_spike="${runSpike}"
fi

eval "$K6" "/loadTests/scripts/${scenario}.js" -e run_smoke="${runSmoke}" -e run_average="${runAverage}" -e run_stress="${runStress}" -e run_spike="${runSpike}"