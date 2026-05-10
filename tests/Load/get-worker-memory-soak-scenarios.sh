#!/bin/bash
set -euo pipefail

declare -a scenarios=(
  "oauth"
  "apiEntrypoint"
  "getUser"
  "refreshToken"
  "setupTwoFactor"
  "createUser"
  "resetPassword"
  "oauthSocialInitiate"
  "oauthSocialCallback"
  "graphQLSignin"
  "graphQLSetupTwoFactor"
  "graphQLCreateUser"
  "graphQLGetUsers"
)

mapfile -t available_scenarios < <(
  find "./tests/Load/scripts" -type f -name "*.js" -exec basename {} .js \; | sort
)

for scenario in "${scenarios[@]}"; do
  scenario_exists=false

  for available_scenario in "${available_scenarios[@]}"; do
    if [ "$available_scenario" = "$scenario" ]; then
      scenario_exists=true
      break
    fi
  done

  if [ "$scenario_exists" != "true" ]; then
    echo "Error: worker memory soak scenario '${scenario}' does not exist." >&2
    exit 1
  fi

  echo "$scenario"
done
