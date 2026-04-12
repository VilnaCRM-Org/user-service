#!/usr/bin/env bats

load 'bats-support/load'
load 'bats-assert/load'

setup_isolated_bmalph_env() {
  if [ -n "${HOME:-}" ]; then
    BMALPH_ORIGINAL_HOME="${HOME}"
    BMALPH_ORIGINAL_HOME_SET=1
  else
    BMALPH_ORIGINAL_HOME=""
    BMALPH_ORIGINAL_HOME_SET=0
  fi

  BMALPH_TEST_HOME="$(mktemp -d)"
  export HOME="${BMALPH_TEST_HOME}"
  export CS_USER_NPM_GLOBAL_BIN="${BMALPH_TEST_HOME}/.npm-global/bin"
  mkdir -p "${CS_USER_NPM_GLOBAL_BIN}"
}

teardown() {
  if [ -n "${BMALPH_TEST_HOME:-}" ] && [ -d "${BMALPH_TEST_HOME}" ]; then
    rm -rf "${BMALPH_TEST_HOME}"
  fi

  if [ "${BMALPH_ORIGINAL_HOME_SET:-0}" = "1" ]; then
    export HOME="${BMALPH_ORIGINAL_HOME}"
  else
    unset HOME
  fi

  unset BMALPH_ORIGINAL_HOME BMALPH_ORIGINAL_HOME_SET BMALPH_TEST_HOME CS_USER_NPM_GLOBAL_BIN
}

@test "make help lists BMALPH targets" {
  run make help
  assert_success
  assert_output --partial "bmalph-install"
  assert_output --partial "bmalph-codex"
  assert_output --partial "bmalph-claude"
  assert_output --partial "bmalph-init"
  assert_output --partial "bmalph-setup"
}

@test "make bmalph-install installs and verifies BMALPH for codex" {
  setup_isolated_bmalph_env
  run make bmalph-install BMALPH_PLATFORM=codex
  assert_success
  assert_output --partial "BMALPH installed:"
  assert_output --partial "BMALPH dry-run verification passed for platform 'codex'."
}

@test "make bmalph-codex installs and verifies the Codex BMALPH flow" {
  setup_isolated_bmalph_env
  run make bmalph-codex
  assert_success
  assert_output --partial 'install-bmalph.sh --platform "codex"'
  assert_output --partial "BMALPH dry-run verification passed for platform 'codex'."
}

@test "make bmalph-claude installs and verifies the Claude BMALPH flow" {
  setup_isolated_bmalph_env
  run make bmalph-claude
  assert_success
  assert_output --partial 'install-bmalph.sh --platform "claude-code"'
  assert_output --partial "BMALPH dry-run verification passed for platform 'claude-code'."
}

@test "make bmalph-init supports dry-run without changing tracked files" {
  local before_status after_status

  setup_isolated_bmalph_env
  before_status="$(git status --short --untracked-files=all)"

  run make bmalph-init BMALPH_PLATFORM=codex BMALPH_DRY_RUN=true
  assert_success
  assert_output --partial "Running BMALPH init in"
  if [[ "${output}" != *"[dry-run] Would perform the following actions:"* ]] && [[ "${output}" != *"bmalph is already initialized in this project."* ]]; then
    echo "Unexpected bmalph-init output:" >&3
    printf '%s\n' "${output}" >&3
    false
  fi

  after_status="$(git status --short --untracked-files=all)"
  [ "${before_status}" = "${after_status}" ]
}

@test "make bmalph-setup supports one-command dry-run without changing tracked files" {
  local before_status after_status

  setup_isolated_bmalph_env
  before_status="$(git status --short --untracked-files=all)"

  run make bmalph-setup BMALPH_PLATFORM=codex BMALPH_DRY_RUN=true
  assert_success
  assert_output --partial 'install-bmalph.sh --platform "codex" --init --dry-run'
  if [[ "${output}" != *"[dry-run] Would perform the following actions:"* ]] && [[ "${output}" != *"bmalph is already initialized in this project."* ]]; then
    echo "Unexpected bmalph-setup output:" >&3
    printf '%s\n' "${output}" >&3
    false
  fi

  after_status="$(git status --short --untracked-files=all)"
  [ "${before_status}" = "${after_status}" ]
}

@test "make bmalph-setup refuses to run init over dirty tracked files" {
  setup_isolated_bmalph_env
  run bash -lc '
    set -euo pipefail
    repo_root="$(pwd)"
    tmpdir="$(mktemp -d)"
    cleanup() {
      git -C "$repo_root" worktree remove --force "$tmpdir" >/dev/null 2>&1 || true
      rm -rf "$tmpdir"
    }
    trap cleanup EXIT

    git -C "$repo_root" worktree add --detach "$tmpdir" HEAD >/dev/null
	    rsync -a --delete \
	      --exclude ".git" \
	      --exclude "config/jwt" \
	      --exclude "vendor" \
	      --exclude "var" \
	      --exclude "tests/CLI/bats/bats-support" \
      --exclude "tests/CLI/bats/bats-assert" \
      "$repo_root/" "$tmpdir/"
    cd "$tmpdir"
    printf "\n# dirty\n" >> README.md

    set +e
    output="$(make bmalph-setup BMALPH_PLATFORM=codex 2>&1)"
    status=$?
    set -e

    printf "%s\n" "$output"
    [ "$status" -ne 0 ]
    grep -F "Error: refusing to run BMALPH init with existing tracked changes." <<<"$output"
  '
  assert_success
  assert_output --partial "Error: refusing to run BMALPH init with existing tracked changes."
}

@test "BMALPH generated paths stay ignored for local installs" {
  run bash -lc 'grep -Fx ".ralph/" .gitignore && grep -Fx ".ralph/logs/" .gitignore && grep -Fx "_bmad/" .gitignore && grep -Fx "_bmad-output/" .gitignore'
  assert_success
}

@test "workspace bootstrap scripts verify bmalph availability" {
  run bash -lc '
    set -euo pipefail
    grep -F "cs_ensure_bmalph_cli" scripts/local-coder/setup-secure-agent-env.sh
    grep -F "BMALPH configured:" scripts/local-coder/setup-secure-agent-env.sh
    grep -F "cs_verify_bmalph_dry_run" scripts/local-coder/startup-smoke-tests.sh
    grep -F "BMALPH startup smoke test passed." scripts/local-coder/startup-smoke-tests.sh
    grep -F "cs_verify_bmalph_dry_run" scripts/local-coder/verify-gh-codex.sh
    grep -F "BMALPH Codex dry-run verification ok." scripts/local-coder/verify-gh-codex.sh
  '
  assert_success
}

@test "BMALPH wrapper skills explain how to bootstrap missing local assets" {
  run bash -lc '
    missing=0
    while IFS= read -r file; do
      if ! grep -q "_bmad/" "$file"; then
        continue
      fi
      if grep -q "make bmalph-setup" "$file"; then
        continue
      fi
      if grep -q "BMALPH assets must be initialized" "$file"; then
        continue
      fi
      if grep -q "If \`_bmad/\` is missing" "$file"; then
        continue
      fi
      echo "$file"
      missing=1
    done < <(find .agents/skills -name SKILL.md -print | sort)
    exit "$missing"
  '
  assert_success
}

@test "autonomous planning wrapper skill documents the Codex subagent flow" {
  run bash -lc '
    set -euo pipefail
    wrapper=".agents/skills/bmad-autonomous-planning/SKILL.md"
    grep -F "repo-local bash" "$wrapper"
    grep -F "canonical planning contract" "$wrapper"
    grep -F "Run each BMALPH planning stage in a dedicated subagent" "$wrapper"
    grep -F "the main agent must decide the next step" "$wrapper"
    grep -F ".claude/skills/bmad-autonomous-planning/SKILL.md" "$wrapper"
    grep -F "Minimal Codex trigger example:" "$wrapper"
    ! grep -F "make bmalph-setup" "$wrapper"
  '
  assert_success
}

@test "autonomous planning skill contract is stage-oriented" {
  run bash -lc '
    set -euo pipefail
    skill=".claude/skills/bmad-autonomous-planning/SKILL.md"
    grep -F "Use BMALPH as the primary process surface" "$skill"
    grep -F "Spawn one focused subagent per BMALPH planning stage" "$skill"
    grep -F "The main agent is the user surrogate." "$skill"
    grep -F "_bmad/bmm/agents/analyst.agent.yaml" "$skill"
    grep -F "_bmad/core/tasks/bmad-create-prd/workflow.md" "$skill"
    grep -F "_bmad/bmm/workflows/3-solutioning/bmad-create-architecture/workflow.md" "$skill"
    grep -F "_bmad/bmm/workflows/3-solutioning/bmad-create-epics-and-stories/workflow.md" "$skill"
    grep -F "_bmad/bmm/workflows/3-solutioning/bmad-check-implementation-readiness/workflow.md" "$skill"
    grep -F "Subagent Execution Log" "$skill"
    grep -F "Use \`1\` to \`3\` validation rounds per artifact." "$skill"
  '
  assert_success
}

@test "documentation points to BMALPH and current-session planning" {
  run bash -lc '
    set -euo pipefail
    grep -F "bmalph --version" README.md
    grep -F "make bmalph-codex" README.md
    grep -F "bmad-autonomous-planning" README.md
    grep -F "make bmalph-codex" docs/getting-started.md
    grep -F ".agents/skills/bmad-autonomous-planning/SKILL.md" docs/getting-started.md
    grep -F "make bmalph-codex" docs/onboarding.md
    grep -F "bmad-autonomous-planning" AGENTS.md
    grep -F "make bmalph-claude" CLAUDE.md
    grep -F "Preferred Codex trigger for this skill:" .claude/skills/AI-AGENT-GUIDE.md
    grep -F "Key trigger prompt" .claude/skills/README.md
    grep -F "run the flow in the current session" .claude/skills/SKILL-DECISION-GUIDE.md
  '
  assert_success
}
