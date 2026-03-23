#!/bin/bash
set -euo pipefail

mapfile -t scenarios < <(find ./tests/Load/scripts -name "*.js" -exec basename {} .js \; | sort)

for scenario in "${scenarios[@]}"; do
  if [ "$scenario" = "oauth" ]; then
    echo "$scenario"
  fi
done

for scenario in "${scenarios[@]}"; do
  if [ "$scenario" != "oauth" ]; then
    echo "$scenario"
  fi
done
