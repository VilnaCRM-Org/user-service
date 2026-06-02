#!/bin/bash
set -euo pipefail

if [ "$#" -ne 5 ]; then
  echo "Usage: $0 <run-smoke> <run-average> <run-stress> <run-spike> <html-prefix>" >&2
  exit 1
fi

runSmoke=$1
runAverage=$2
runStress=$3
runSpike=$4
htmlPrefix=$5
configFile=${LOAD_TEST_CONFIG_FILE:-tests/Load/config.json}

if [ ! -f "$configFile" ]; then
  configFile=tests/Load/config.json.dist
fi

delayBetweenScenarios=${LOAD_TEST_DELAY_BETWEEN_SCENARIOS:-}

if [ -z "$delayBetweenScenarios" ]; then
  delayBetweenScenarios=$(
    sed -n 's/^[[:space:]]*"delayBetweenScenarios"[[:space:]]*:[[:space:]]*\([0-9][0-9]*\).*/\1/p' "$configFile" | head -1
  )
fi

delayBetweenScenarios=${delayBetweenScenarios:-0}

if [[ ! "$delayBetweenScenarios" =~ ^[0-9]+$ ]]; then
  echo "Error: delayBetweenScenarios must be a non-negative integer." >&2
  exit 1
fi

mapfile -t loadTestScenarios < <(./tests/Load/get-load-test-scenarios.sh)
scenarioCount=${#loadTestScenarios[@]}

for scenarioIndex in "${!loadTestScenarios[@]}"; do
  scenario=${loadTestScenarios[$scenarioIndex]}
  ./tests/Load/execute-load-test.sh "$scenario" "$runSmoke" "$runAverage" "$runStress" "$runSpike" "$htmlPrefix"

  if [ "$delayBetweenScenarios" -gt 0 ] && [ $((scenarioIndex + 1)) -lt "$scenarioCount" ]; then
    sleep "$delayBetweenScenarios"
  fi
done
