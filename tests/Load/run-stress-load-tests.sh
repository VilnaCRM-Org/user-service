#!/bin/bash
set -e

LOAD_TEST_SCENARIOS=$(./tests/Load/get-load-test-scenarios.sh)

for scenario in $LOAD_TEST_SCENARIOS; do
  if [[ $scenario != "createUser" && $scenario != "confirmUser" && $scenario != "graphQLCreateUser" && $scenario != "graphQLConfirmUser" ]]; then
    SCENARIO_NAME=$scenario eval $BASH_STRESS_PREPARE_USERS
  fi

  REPORT_FILENAME="stress-${scenario}.html" SCENARIO_NAME=$scenario eval $BASH_EXECUTE_STRESS_SCRIPT
done