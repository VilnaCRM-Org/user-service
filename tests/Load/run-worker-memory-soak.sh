#!/bin/bash
set -euo pipefail

SCRIPT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
REPO_ROOT=$(cd "$SCRIPT_DIR/../.." && pwd)

cd "$REPO_ROOT"

loadTestComposeProject=${LOAD_TEST_COMPOSE_PROJECT:-user-service-load-tests}
loadTestComposeFile=${LOAD_TEST_COMPOSE_FILE:-docker-compose.load-tests.yml}
loadTestPhpService=${LOAD_TEST_PHP_SERVICE:-php}
loadTestRedisService=${LOAD_TEST_REDIS_SERVICE:-redis}
memorySoakRounds=${MEMORY_SOAK_ROUNDS:-6}
memorySoakWarmupRounds=${MEMORY_SOAK_WARMUP_ROUNDS:-2}
memorySoakSettleSeconds=${MEMORY_SOAK_SETTLE_SECONDS:-5}
workerMemoryStepToleranceKb=${WORKER_MEMORY_STEP_TOLERANCE_KB:-2048}
workerMemoryTotalGrowthToleranceKb=${WORKER_MEMORY_TOTAL_GROWTH_TOLERANCE_KB:-12288}
memorySoakResultsFile=${MEMORY_SOAK_RESULTS_FILE:-./tests/Load/loadTestsResults/worker-memory-soak.csv}
memorySoakResetBetweenRounds=${MEMORY_SOAK_RESET_BETWEEN_ROUNDS:-true}

IFS=':' read -r -a composeFiles <<< "$loadTestComposeFile"
composeCmd=(docker compose -p "$loadTestComposeProject")

for composeFile in "${composeFiles[@]}"; do
  if [ -z "$composeFile" ]; then
    continue
  fi

  composeCmd+=(-f "$composeFile")
done

mkdir -p "$(dirname "$memorySoakResultsFile")"
printf 'round,timestamp_utc,rss_kb,rss_mib,delta_from_baseline_kb,is_warmup\n' > "$memorySoakResultsFile"

discover_memory_soak_scenarios() {
  mapfile -t scenarios < <(
    find "$REPO_ROOT/tests/Load/scripts" -type f -name '*.js' -printf '%f\n' \
      | sed 's/\.js$//' \
      | sort
  )

  if [ "${#scenarios[@]}" -eq 0 ]; then
    echo "Error: failed to discover load-test scenarios for worker memory soak." >&2
    exit 1
  fi

  local joined_scenarios
  joined_scenarios=$(IFS=,; echo "${scenarios[*]}")

  printf '%s\n' "$joined_scenarios"
}

memorySoakScenarios=${MEMORY_SOAK_SCENARIOS:-$(discover_memory_soak_scenarios)}

sample_rss_kb() {
  local rss_kb

  rss_kb=$(
    "${composeCmd[@]}" exec -T "$loadTestPhpService" sh -c \
      "awk '/VmRSS:/ {print \$2}' /proc/1/status"
  )
  rss_kb=${rss_kb//$'\r'/}
  rss_kb=${rss_kb//$'\n'/}

  if [[ ! "$rss_kb" =~ ^[0-9]+$ ]]; then
    echo "Error: failed to sample RSS from ${loadTestPhpService} container." >&2
    echo "Got: ${rss_kb:-<empty>}" >&2
    exit 1
  fi

  printf '%s\n' "$rss_kb"
}

sample_restart_count() {
  local container_id

  container_id=$("${composeCmd[@]}" ps -q "$loadTestPhpService")
  container_id=${container_id//$'\r'/}
  container_id=${container_id//$'\n'/}

  if [ -z "$container_id" ]; then
    echo "Error: failed to resolve container ID for ${loadTestPhpService}." >&2
    exit 1
  fi

  docker inspect "$container_id" --format '{{ .RestartCount }}'
}

to_mib() {
  awk -v rss_kb="$1" 'BEGIN { printf "%.2f", rss_kb / 1024 }'
}

timestamp_utc() {
  date -u +"%Y-%m-%dT%H:%M:%SZ"
}

prepare_oauth_client_pool() {
  local symfony_cmd

  symfony_cmd=$(printf '%q ' "${composeCmd[@]}")
  symfony_cmd+=$(printf 'exec -T %q bin/console' "$loadTestPhpService")

  SYMFONY="$symfony_cmd" \
    "$REPO_ROOT/tests/Load/load-tests-prepare-oauth-client.sh" \
    "$(jq -r '.endpoints.oauth.clientName' "$REPO_ROOT/tests/Load/config.prod.json")" \
    "$(jq -r '.endpoints.oauth.clientID' "$REPO_ROOT/tests/Load/config.prod.json")" \
    "$(jq -r '.endpoints.oauth.clientSecret' "$REPO_ROOT/tests/Load/config.prod.json")" \
    --redirect-uri="$(jq -r '.endpoints.oauth.clientRedirectUri' "$REPO_ROOT/tests/Load/config.prod.json")"
}

reset_round_state() {
  local reset_timestamp

  reset_timestamp=$(timestamp_utc)
  echo "[$reset_timestamp] resetting load-test data stores before round execution"

  "${composeCmd[@]}" exec -T "$loadTestRedisService" redis-cli FLUSHALL >/dev/null
  "${composeCmd[@]}" exec -T -e APP_ENV=load_test "$loadTestPhpService" \
    bin/console doctrine:mongodb:schema:drop >/dev/null 2>&1 || true
  "${composeCmd[@]}" exec -T -e APP_ENV=load_test "$loadTestPhpService" \
    bin/console doctrine:mongodb:schema:create >/dev/null
  "${composeCmd[@]}" exec -T -e APP_ENV=load_test "$loadTestPhpService" \
    bin/console app:seed-test-oauth-client >/dev/null

  prepare_oauth_client_pool >/dev/null
}

run_round_loads() {
  local round=$1
  local scenario
  local trimmed_scenario

  IFS=',' read -r -a scenarios <<< "$memorySoakScenarios"

  for scenario in "${scenarios[@]}"; do
    trimmed_scenario=$(echo "$scenario" | xargs)

    if [ -z "$trimmed_scenario" ]; then
      continue
    fi

    echo "[$(timestamp_utc)] round ${round}/${memorySoakRounds}: scenario=${trimmed_scenario}"
    LOAD_TEST_DISABLE_DURATION_THRESHOLDS=true \
      ./tests/Load/execute-load-test.sh "$trimmed_scenario" true false false false "worker-memory-r${round}-"
  done
}

if [ "$memorySoakRounds" -le "$memorySoakWarmupRounds" ]; then
  echo "Error: MEMORY_SOAK_ROUNDS must be greater than MEMORY_SOAK_WARMUP_ROUNDS." >&2
  exit 1
fi

declare -a rss_samples=()

initial_rss_kb=$(sample_rss_kb)
initial_timestamp=$(timestamp_utc)
initial_rss_mib=$(to_mib "$initial_rss_kb")
rss_samples+=("$initial_rss_kb")

printf '0,%s,%s,%s,0,true\n' \
  "$initial_timestamp" \
  "$initial_rss_kb" \
  "$initial_rss_mib" >> "$memorySoakResultsFile"

echo "Worker memory soak runner"
echo "  compose project: $loadTestComposeProject"
echo "  compose file:    $loadTestComposeFile"
echo "  php service:     $loadTestPhpService"
echo "  rounds:          $memorySoakRounds"
echo "  warmup rounds:   $memorySoakWarmupRounds"
echo "  settle seconds:  $memorySoakSettleSeconds"
echo "  step tolerance:  ${workerMemoryStepToleranceKb}kB"
echo "  total tolerance: ${workerMemoryTotalGrowthToleranceKb}kB"
echo "  scenarios:       ${memorySoakScenarios}"
echo "  report file:     $memorySoakResultsFile"
echo "[$initial_timestamp] baseline rss=${initial_rss_kb}kB (${initial_rss_mib} MiB)"
echo

for round in $(seq 1 "$memorySoakRounds"); do
  if [ "$memorySoakResetBetweenRounds" = "true" ]; then
    reset_round_state
  fi

  run_round_loads "$round"

  if [ "$memorySoakSettleSeconds" -gt 0 ]; then
    sleep "$memorySoakSettleSeconds"
  fi

  restart_count=$(sample_restart_count)
  if [ "$restart_count" -ne 0 ]; then
    echo "Error: worker container restarted during soak run (restart_count=${restart_count})." >&2
    exit 1
  fi

  rss_kb=$(sample_rss_kb)
  rss_samples+=("$rss_kb")
  rss_mib=$(to_mib "$rss_kb")
  sample_timestamp=$(timestamp_utc)
  delta_from_baseline_kb=$((rss_kb - initial_rss_kb))
  is_warmup=false

  if [ "$round" -le "$memorySoakWarmupRounds" ]; then
    is_warmup=true
  fi

  printf '%s,%s,%s,%s,%s,%s\n' \
    "$round" \
    "$sample_timestamp" \
    "$rss_kb" \
    "$rss_mib" \
    "$delta_from_baseline_kb" \
    "$is_warmup" >> "$memorySoakResultsFile"

  echo "[$sample_timestamp] round ${round}/${memorySoakRounds}: rss=${rss_kb}kB (${rss_mib} MiB), delta_from_baseline=${delta_from_baseline_kb}kB, warmup=${is_warmup}"
done

warmup_baseline_index=$memorySoakWarmupRounds
warmup_baseline_rss_kb=${rss_samples[$warmup_baseline_index]}
final_rss_kb=${rss_samples[${#rss_samples[@]} - 1]}
total_growth_after_warmup_kb=$((final_rss_kb - warmup_baseline_rss_kb))
all_steps_exceeded=true
peak_rss_kb=$initial_rss_kb
post_warmup_step_count=$((memorySoakRounds - memorySoakWarmupRounds))

for rss_kb in "${rss_samples[@]}"; do
  if [ "$rss_kb" -gt "$peak_rss_kb" ]; then
    peak_rss_kb=$rss_kb
  fi
done

for round in $(seq $((memorySoakWarmupRounds + 1)) "$memorySoakRounds"); do
  current_index=$round
  previous_index=$((round - 1))
  current_rss_kb=${rss_samples[$current_index]}
  previous_rss_kb=${rss_samples[$previous_index]}
  step_growth_kb=$((current_rss_kb - previous_rss_kb))

  echo "step ${round}: growth=${step_growth_kb}kB"

  if [ "$step_growth_kb" -le "$workerMemoryStepToleranceKb" ]; then
    all_steps_exceeded=false
  fi
done

if [ "$post_warmup_step_count" -lt 2 ]; then
  all_steps_exceeded=false
  echo "Leak decision skipped: not enough post-warmup samples to classify repeated growth."
fi

echo
echo "Worker memory soak complete"
echo "  baseline_rss_kb:             $initial_rss_kb"
echo "  warmup_baseline_rss_kb:      $warmup_baseline_rss_kb"
echo "  final_rss_kb:                $final_rss_kb"
echo "  peak_rss_kb:                 $peak_rss_kb"
echo "  total_growth_after_warmup:   ${total_growth_after_warmup_kb}kB"
echo "  report_file:                 $memorySoakResultsFile"

if [ "$all_steps_exceeded" = true ] && [ "$total_growth_after_warmup_kb" -gt "$workerMemoryTotalGrowthToleranceKb" ]; then
  echo
  echo "❌ MEMORY LEAK SIGNAL: repeated post-warmup growth exceeded ${workerMemoryStepToleranceKb}kB per round and ${workerMemoryTotalGrowthToleranceKb}kB overall." >&2
  exit 1
fi

echo
echo "✅ Worker memory remained within configured leak tolerances."
