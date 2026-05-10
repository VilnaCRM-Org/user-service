#!/usr/bin/env bats

load 'bats-support/load'
load 'bats-assert/load'

setup() {
  if project_root="$(git rev-parse --show-toplevel 2>/dev/null)"; then
    cd "$project_root"
    return
  fi

  cd "$BATS_TEST_DIRNAME/../../.."
}

@test "make help includes agent-learning targets" {
  run make help
  assert_success
  assert_output --partial "agent-learning-capture"
  assert_output --partial "agent-learning-evals"
  assert_output --partial "agent-learning-propose"
}

@test "capture-codex-run records proxy env and complete trace schema" {
  local learning_dir="${BATS_TEST_TMPDIR}/learning"

  run env \
    AGENT_LEARNING_DIR="${learning_dir}" \
    AGENT_LEARNING_NOW="2026-05-10T00:00:00Z" \
    AGENT_LIGHTNING_BASE_URL="http://127.0.0.1:9999/v1" \
    ./scripts/agent-learning/capture-codex-run.sh \
      --skill "${BATS_TEST_TMPDIR}/skill.md" \
      --skill-version "test-version" \
      --prompt "respect repository constraints" \
      --trace-id "trace-proxy" \
      -- bash -c 'printf "base=%s\n" "${OPENAI_BASE_URL:-}"'

  assert_success
  assert_output --partial "Trace recorded:"

  run jq -e '
    .schema_version == "agent-learning.trace.v1"
    and .trace_id == "trace-proxy"
    and .skill_version == "test-version"
    and .openai_base_url == "http://127.0.0.1:9999/v1"
    and .proxy_source == "agent_lightning"
    and .tool_calls == []
    and .tool_results == []
    and (.final_output | contains("base=http://127.0.0.1:9999/v1"))
    and .exit_code == 0
  ' "${learning_dir}/traces/trace-proxy.json"
  assert_success
}

@test "record-intervention links learning signals to a source trace" {
  local learning_dir="${BATS_TEST_TMPDIR}/learning"
  mkdir -p "${learning_dir}/traces"

  cat > "${learning_dir}/traces/trace-1.json" <<'JSON'
{
  "schema_version": "agent-learning.trace.v1",
  "trace_id": "trace-1",
  "skill_ref": ".claude/skills/code-review/SKILL.md"
}
JSON

  run env \
    AGENT_LEARNING_DIR="${learning_dir}" \
    AGENT_LEARNING_NOW="2026-05-10T00:01:00Z" \
    ./scripts/agent-learning/record-intervention.sh \
      --trace-id "trace-1" \
      --type "reprompt" \
      --summary "Agent ignored apply_patch editing rule" \
      --reprompt "Use apply_patch for manual edits" \
      --labels "review, editing"

  assert_success
  assert_output --partial "Intervention recorded:"

  local signal_file
  signal_file="$(find "${learning_dir}/interventions" -type f -name '*.json' | sort | head -n 1)"

  run jq -e '
    .schema_version == "agent-learning.signal.v1"
    and .trace_id == "trace-1"
    and .skill_ref == ".claude/skills/code-review/SKILL.md"
    and .type == "reprompt"
    and (.labels == ["review", "editing"])
  ' "${signal_file}"
  assert_success

  run env \
    AGENT_LEARNING_DIR="${learning_dir}" \
    AGENT_LEARNING_NOW="2026-05-10T00:02:00Z" \
    ./scripts/agent-learning/record-intervention.sh \
      --trace-id "trace-1" \
      --type "reprompt" \
      --summary "Agent ignored apply_patch editing rule" \
      --reprompt "Use apply_patch for manual edits" \
      --labels "review, editing"

  assert_success

  run bash -lc "find '${learning_dir}/interventions' -type f -name '*.json' | wc -l | tr -d '[:space:]'"
  assert_success
  assert_output "1"
}

@test "episode builder creates deterministic JSONL from traces and interventions" {
  local learning_dir="${BATS_TEST_TMPDIR}/learning"

  env \
    AGENT_LEARNING_DIR="${learning_dir}" \
    AGENT_LEARNING_NOW="2026-05-10T00:00:00Z" \
    ./scripts/agent-learning/capture-codex-run.sh \
      --skill ".claude/skills/code-review/SKILL.md" \
      --skill-version "test-version" \
      --prompt "review this change" \
      --trace-id "trace-episode" \
      -- bash -c 'printf "bad output\n"' >/dev/null

  env \
    AGENT_LEARNING_DIR="${learning_dir}" \
    AGENT_LEARNING_NOW="2026-05-10T00:01:00Z" \
    ./scripts/agent-learning/record-intervention.sh \
      --trace-id "trace-episode" \
      --type "manual-diff" \
      --summary "Missing test evidence in review response" \
      --good-output "Include exact test command and result" \
      --labels "review,evidence" >/dev/null

  mkdir -p "${learning_dir}/interventions"
  printf '{not valid json\n' >"${learning_dir}/interventions/malformed.json"

  run ./scripts/agent-learning/build-episodes.sh --store-dir "${learning_dir}" --output "${learning_dir}/episodes-1.jsonl"
  assert_success
  assert_output --partial "(1 records)"
  assert_output --partial "Warning: skipping malformed intervention"

  run ./scripts/agent-learning/build-episodes.sh --store-dir "${learning_dir}" --output "${learning_dir}/episodes-2.jsonl"
  assert_success

  run cmp "${learning_dir}/episodes-1.jsonl" "${learning_dir}/episodes-2.jsonl"
  assert_success

  run jq -s -e '
    length == 1
    and .[0].schema_version == "agent-learning.episode.v1"
    and .[0].source.trace_id == "trace-episode"
    and (.[0].labels | index("manual-diff"))
    and .[0].good_output == "Include exact test command and result"
  ' "${learning_dir}/episodes-1.jsonl"
  assert_success
}

@test "propose-skill-update outputs a concrete patch without mutating the source skill" {
  local learning_dir="${BATS_TEST_TMPDIR}/learning"
  local skill_file="${BATS_TEST_TMPDIR}/skill.md"
  local before_hash after_hash

  cat > "${skill_file}" <<'MARKDOWN'
---
name: fixture-skill
description: Fixture skill.
---

# Fixture Skill

Follow the repository workflow.
MARKDOWN

  env \
    AGENT_LEARNING_DIR="${learning_dir}" \
    AGENT_LEARNING_NOW="2026-05-10T00:00:00Z" \
    ./scripts/agent-learning/capture-codex-run.sh \
      --skill "${skill_file}" \
      --skill-version "fixture-version" \
      --prompt "review a patch" \
      --trace-id "trace-skill" \
      -- bash -c 'printf "missing verification\n"' >/dev/null

  env \
    AGENT_LEARNING_DIR="${learning_dir}" \
    AGENT_LEARNING_NOW="2026-05-10T00:01:00Z" \
    ./scripts/agent-learning/record-intervention.sh \
      --trace-id "trace-skill" \
      --type "test-failure" \
      --summary "Always include local verification output" \
      --good-output "Report commands run before declaring completion" >/dev/null

  ./scripts/agent-learning/build-episodes.sh --store-dir "${learning_dir}" >/dev/null

  cat >> "${learning_dir}/episodes.jsonl" <<'JSON'
{"schema_version":"agent-learning.episode.v1","episode_id":"episode-unrelated","skill_ref":"","intervention":{"type":"reprompt","summary":"Unrelated missing skill ref"},"good_output":"Do not include this note"}
JSON

  before_hash="$(sha256sum "${skill_file}" | awk '{print $1}')"
  run ./scripts/agent-learning/propose-skill-update.sh \
    --store-dir "${learning_dir}" \
    --skill-file "${skill_file}" \
    --episodes "${learning_dir}/episodes.jsonl" \
    --output "${learning_dir}/skill.patch"

  assert_success
  assert_output --partial "Skill update patch written:"
  after_hash="$(sha256sum "${skill_file}" | awk '{print $1}')"
  [ "${before_hash}" = "${after_hash}" ]

  run grep -F "## Learning Notes" "${learning_dir}/skill.patch"
  assert_success
  run grep -F "Always include local verification output" "${learning_dir}/skill.patch"
  assert_success
  run grep -F "Unrelated missing skill ref" "${learning_dir}/skill.patch"
  assert_failure
}

@test "agent-learning scripts reject missing option values clearly" {
  run ./scripts/agent-learning/capture-codex-run.sh --skill
  assert_failure 2
  assert_output --partial "Error: --skill requires a value."

  run ./scripts/agent-learning/record-intervention.sh --trace-id trace-1 --type
  assert_failure 2
  assert_output --partial "Error: --type requires a value."

  run ./scripts/agent-learning/propose-skill-update.sh --skill-file
  assert_failure 2
  assert_output --partial "Error: --skill-file requires a value."
}

@test "verify-gh-codex exposes Agent Lightning proxy wiring" {
  run bash -lc '
    set -euo pipefail
    grep -F "AGENT_LIGHTNING_BASE_URL" scripts/local-coder/verify-gh-codex.sh
    grep -F "OPENAI_BASE_URL" scripts/local-coder/verify-gh-codex.sh
  '
  assert_success
}
