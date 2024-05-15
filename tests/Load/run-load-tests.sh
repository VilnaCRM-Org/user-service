#!/bin/bash
set -e

for scenario in $LOAD_TEST_SCENARIOS; do
  if [[ $scenario != "createUser" && $scenario != "confirmUser" && $scenario != "graphQLCreateUser" && $scenario != "graphQLConfirmUser" ]]; then
    SCENARIO_NAME=$scenario eval $BASH_PREPARE_USERS
  fi

  REPORT_FILENAME="${scenario}.html" SCENARIO_NAME=$scenario eval $BASH_EXECUTE_SCRIPT
done
