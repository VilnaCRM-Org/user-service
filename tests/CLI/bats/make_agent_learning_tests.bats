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

@test "capture-codex-run preserves multiline command arguments" {
  local learning_dir="${BATS_TEST_TMPDIR}/learning"

  run env \
    AGENT_LEARNING_DIR="${learning_dir}" \
    AGENT_LEARNING_NOW="2026-05-10T00:00:00Z" \
    ./scripts/agent-learning/capture-codex-run.sh \
      --skill "${BATS_TEST_TMPDIR}/skill.md" \
      --skill-version "test-version" \
      --prompt "capture multiline args" \
      --trace-id "trace-multiline-command" \
      -- printf $'first\nsecond\n'

  assert_success

  run jq -e '
    .command | length == 2
    and .[0] == "printf"
    and (.[1] | contains("\n"))
  ' "${learning_dir}/traces/trace-multiline-command.json"
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
  printf '[]\n' >"${learning_dir}/traces/trace-array.json"
  cat >"${learning_dir}/interventions/signal-array.json" <<'JSON'
{
  "trace_id": "trace-array",
  "signal_id": "signal-array",
  "created_at": "2026-05-10T00:02:00Z",
  "type": "reprompt",
  "summary": "Trace must be an object"
}
JSON

  run ./scripts/agent-learning/build-episodes.sh --store-dir "${learning_dir}" --output "${learning_dir}/episodes-1.jsonl"
  assert_success
  assert_output --partial "(1 records)"
  assert_output --partial "Warning: skipping malformed intervention"
  assert_output --partial "Warning: skipping signal-array; malformed trace trace-array"

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

@test "episode builder uses reprompt diff and summary fallback as desired output" {
  local learning_dir="${BATS_TEST_TMPDIR}/learning"
  local diff_file="${BATS_TEST_TMPDIR}/manual.diff"

  cat > "${diff_file}" <<'DIFF'
diff --git a/review.md b/review.md
--- a/review.md
+++ b/review.md
@@ -1 +1 @@
-missing verification
+include exact verification command output
DIFF

  env \
    AGENT_LEARNING_DIR="${learning_dir}" \
    AGENT_LEARNING_NOW="2026-05-10T00:00:00Z" \
    ./scripts/agent-learning/capture-codex-run.sh \
      --skill ".claude/skills/code-review/SKILL.md" \
      --skill-version "test-version" \
      --prompt "review reprompt fallback" \
      --trace-id "trace-reprompt-fallback" \
      -- bash -c 'printf "bad reprompt output\n"' >/dev/null

  env \
    AGENT_LEARNING_DIR="${learning_dir}" \
    AGENT_LEARNING_NOW="2026-05-10T00:01:00Z" \
    ./scripts/agent-learning/record-intervention.sh \
      --trace-id "trace-reprompt-fallback" \
      --type "reprompt" \
      --summary "Agent omitted verification evidence" \
      --reprompt "Include exact local verification commands and results" >/dev/null

  env \
    AGENT_LEARNING_DIR="${learning_dir}" \
    AGENT_LEARNING_NOW="2026-05-10T00:02:00Z" \
    ./scripts/agent-learning/capture-codex-run.sh \
      --skill ".claude/skills/code-review/SKILL.md" \
      --skill-version "test-version" \
      --prompt "review diff fallback" \
      --trace-id "trace-diff-fallback" \
      -- bash -c 'printf "bad diff output\n"' >/dev/null

  env \
    AGENT_LEARNING_DIR="${learning_dir}" \
    AGENT_LEARNING_NOW="2026-05-10T00:03:00Z" \
    ./scripts/agent-learning/record-intervention.sh \
      --trace-id "trace-diff-fallback" \
      --type "manual-diff" \
      --summary "Manual edit added missing verification evidence" \
      --diff-file "${diff_file}" >/dev/null

  env \
    AGENT_LEARNING_DIR="${learning_dir}" \
    AGENT_LEARNING_NOW="2026-05-10T00:04:00Z" \
    ./scripts/agent-learning/capture-codex-run.sh \
      --skill ".claude/skills/code-review/SKILL.md" \
      --skill-version "test-version" \
      --prompt "review summary fallback" \
      --trace-id "trace-summary-fallback" \
      -- bash -c 'printf "bad summary output\n"' >/dev/null

  env \
    AGENT_LEARNING_DIR="${learning_dir}" \
    AGENT_LEARNING_NOW="2026-05-10T00:05:00Z" \
    ./scripts/agent-learning/record-intervention.sh \
      --trace-id "trace-summary-fallback" \
      --type "tool-retry" \
      --summary "Retry tool command with preserved multiline arguments" >/dev/null

  run ./scripts/agent-learning/build-episodes.sh --store-dir "${learning_dir}" --output "${learning_dir}/episodes.jsonl"
  assert_success
  assert_output --partial "(3 records)"

  run jq -s -e '
    (length == 3)
    and (
      (map({(.source.trace_id): .good_output}) | add) as $outputs
      | $outputs["trace-reprompt-fallback"] == "Include exact local verification commands and results"
        and ($outputs["trace-diff-fallback"] | contains("include exact verification command output"))
        and $outputs["trace-summary-fallback"] == "Retry tool command with preserved multiline arguments"
    )
  ' "${learning_dir}/episodes.jsonl"
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
  jq -cn \
    --arg skill_ref "prefix${skill_file}" \
    '{
      schema_version: "agent-learning.episode.v1",
      episode_id: "episode-suffix-collision",
      skill_ref: $skill_ref,
      intervention: {
        type: "reprompt",
        summary: "Unrelated suffix collision"
      },
      good_output: "Do not include this suffix collision"
    }' >>"${learning_dir}/episodes.jsonl"

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
  run grep -F "Unrelated suffix collision" "${learning_dir}/skill.patch"
  assert_failure

  run ./scripts/agent-learning/propose-skill-update.sh \
    --store-dir "${learning_dir}" \
    --skill-file "${skill_file}" \
    --episodes "${learning_dir}/episodes.jsonl" \
    --output "${skill_file}"

  assert_failure 2
  assert_output --partial "Error: --output must not point to --skill-file."
  after_hash="$(sha256sum "${skill_file}" | awk '{print $1}')"
  [ "${before_hash}" = "${after_hash}" ]
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

  run ./scripts/agent-learning/record-intervention.sh --trace-id trace-1 --summary --help
  assert_failure 2
  assert_output --partial "Error: --summary requires a value."
}

@test "verify-gh-codex exposes Agent Lightning proxy wiring" {
  run bash -lc '
    set -euo pipefail
    grep -F "AGENT_LIGHTNING_BASE_URL" scripts/local-coder/verify-gh-codex.sh
    grep -F "OPENAI_BASE_URL" scripts/local-coder/verify-gh-codex.sh
  '
  assert_success
}
