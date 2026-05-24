#!/usr/bin/env bash
set -Eeuo pipefail

# ---------------------------------------------------------------------------
# NanoClaw Dev Cycle — deterministic, auditable development workflow driver
# ---------------------------------------------------------------------------

# Load optional config override
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CONFIG_FILE="${SCRIPT_DIR}/config.env"
if [[ -f "${CONFIG_FILE}" ]]; then
  # shellcheck source=/dev/null
  source "${CONFIG_FILE}"
fi

# Defaults
MAX_FIX_ATTEMPTS="${NANOCLAW_WORKFLOW_MAX_FIX_ATTEMPTS:-3}"
LOG_LEVEL="${NANOCLAW_WORKFLOW_LOG_LEVEL:-info}"
COLORS="${NANOCLAW_WORKFLOW_COLORS:-auto}"
GH_REMOTE="${NANOCLAW_WORKFLOW_GH_REMOTE:-origin}"

# ---------------------------------------------------------------------------
# Color setup
# ---------------------------------------------------------------------------
_use_colors() {
  if [[ "${COLORS}" == "always" ]]; then return 0; fi
  if [[ "${COLORS}" == "never" ]]; then return 1; fi
  [ -t 1 ]
}

if _use_colors; then
  RED='\033[0;31m'
  YELLOW='\033[0;33m'
  GREEN='\033[0;32m'
  CYAN='\033[0;36m'
  BOLD='\033[1m'
  RESET='\033[0m'
else
  RED='' YELLOW='' GREEN='' CYAN='' BOLD='' RESET=''
fi

info()    { echo -e "${CYAN}[INFO]${RESET} $*"; }
warn()    { echo -e "${YELLOW}[WARN]${RESET} $*" >&2; }
error()   { echo -e "${RED}[ERROR]${RESET} $*" >&2; }
success() { echo -e "${GREEN}[OK]${RESET} $*"; }
phase()   { echo -e "\n${BOLD}${CYAN}==> $*${RESET}"; }

# ---------------------------------------------------------------------------
# Utility functions
# ---------------------------------------------------------------------------

require_command() {
  local cmd="${1}"
  local hint="${2:-}"
  if ! command -v "${cmd}" &>/dev/null; then
    error "Required command not found: ${cmd}"
    [[ -n "${hint}" ]] && echo -e "  Install: ${hint}"
    exit 1
  fi
}

detect_repo_root() {
  local dir="${NANOCLAW_WORKFLOW_REPO_ROOT:-}"
  if [[ -n "${dir}" ]]; then
    if [[ ! -d "${dir}/.git" ]]; then
      error "NANOCLAW_WORKFLOW_REPO_ROOT is set but '${dir}' is not a git repo."
      exit 1
    fi
    export REPO_ROOT="${dir}"
    return
  fi

  dir="$(pwd)"
  while [[ "${dir}" != "/" ]]; do
    if [[ -d "${dir}/.git" ]]; then
      export REPO_ROOT="${dir}"
      return
    fi
    dir="$(dirname "${dir}")"
  done

  error "Could not find a git repository root. Run this script from inside a git repo."
  exit 1
}

ensure_clean_context() {
  local status
  status="$(git -C "${REPO_ROOT}" status --short)"
  if [[ -n "${status}" ]]; then
    warn "Uncommitted changes detected:"
    echo "${status}" | head -20 | sed 's/^/  /'
    warn "Proceeding anyway — consider committing or stashing first."
  else
    success "Working tree is clean."
  fi
}

create_task_slug() {
  local description="${1}"
  local timestamp
  timestamp="$(date '+%Y%m%d-%H%M%S')"
  local slug
  slug="$(echo "${description}" | tr '[:upper:]' '[:lower:]' | sed 's/[^a-z0-9]/-/g' | tr -s '-' | sed 's/^-//;s/-$//' | cut -c1-40)"
  echo "${timestamp}-${slug}"
}

create_task_folder() {
  local slug="${1}"
  local folder="${REPO_ROOT}/.ai/tasks/${slug}"
  mkdir -p "${folder}"

  for f in spec.md impact-analysis.md plan.md implementation-log.md validation.md ci.md review.md final-summary.md; do
    local title
    title="$(echo "${f%.md}" | sed 's/-/ /g' | awk '{for(i=1;i<=NF;i++) $i=toupper(substr($i,1,1)) substr($i,2); print}')"
    printf "# %s\n\n_Not yet produced._\n" "${title}" > "${folder}/${f}"
  done

  echo "${folder}"
}

detect_make_targets() {
  local makefile="${REPO_ROOT}/Makefile"
  if [[ ! -f "${makefile}" ]]; then echo ""; return; fi
  grep -E '^[a-zA-Z0-9_-]+:' "${makefile}" | cut -d: -f1 | tr '\n' ' '
}

detect_composer_scripts() {
  local cj="${REPO_ROOT}/composer.json"
  if [[ ! -f "${cj}" ]]; then echo ""; return; fi
  if command -v jq &>/dev/null; then
    jq -r '.scripts // {} | keys[]' "${cj}" 2>/dev/null | tr '\n' ' '
  else
    grep -A200 '"scripts"' "${cj}" | grep -E '^\s+"[a-z]' | sed 's/.*"\([^"]*\)".*/\1/' | tr '\n' ' '
  fi
}

detect_validation_commands() {
  local targets
  targets="$(detect_make_targets)"
  local scripts
  scripts="$(detect_composer_scripts)"

  local ordered=(
    "make ci"
    "make test"
    "make tests"
    "make phpunit"
    "make phpstan"
    "make psalm"
    "make cs-check"
    "make lint"
    "composer test"
    "composer phpunit"
    "composer phpstan"
    "composer psalm"
    "composer cs-check"
  )

  local available=()
  for cmd in "${ordered[@]}"; do
    local tool="${cmd%% *}"
    local target="${cmd#* }"
    if [[ "${tool}" == "make" ]]; then
      if echo " ${targets} " | grep -q " ${target} "; then
        available+=("${cmd}")
      fi
    elif [[ "${tool}" == "composer" ]]; then
      if echo " ${scripts} " | grep -q " ${target} "; then
        available+=("${cmd}")
      fi
    fi
  done

  printf '%s\n' "${available[@]:-}"
}

run_and_log() {
  local logfile="${1}"; shift
  local description="${1}"; shift
  local cmd=("$@")

  info "Running: ${cmd[*]}"
  info "Log: ${logfile}"

  local exit_code=0
  if ! "${cmd[@]}" 2>&1 | tee -a "${logfile}"; then
    exit_code="${PIPESTATUS[0]}"
  fi

  if [[ "${exit_code}" -eq 0 ]]; then
    success "${description} — passed"
  else
    warn "${description} — FAILED (exit ${exit_code})"
  fi

  return "${exit_code}"
}

summarize_git_status() {
  git -C "${REPO_ROOT}" status --short 2>/dev/null || echo "(no git status)"
}

summarize_git_diff() {
  local base="${1:-HEAD}"
  echo "### Diff stat"
  git -C "${REPO_ROOT}" diff --stat "${base}" 2>/dev/null || echo "(no diff)"
  echo ""
  echo "### First 50 changed lines"
  git -C "${REPO_ROOT}" diff "${base}" 2>/dev/null | head -50 || echo "(no diff)"
}

extract_pr_number() {
  local input="${1}"
  if [[ "${input}" =~ /pull/([0-9]+) ]]; then
    echo "${BASH_REMATCH[1]}"
  elif [[ "${input}" =~ ^[0-9]+$ ]]; then
    echo "${input}"
  else
    error "Cannot extract PR number from: ${input}"
    exit 1
  fi
}

fetch_pr_metadata() {
  local pr="${1}"
  gh pr view "${pr}" --json number,title,body,state,baseRefName,headRefName,author,assignees,labels,milestone,reviewRequests,reviews,checks,url,mergeable,additions,deletions,changedFiles,commits
}

fetch_pr_checks() {
  local pr="${1}"
  echo "### PR Checks"
  gh pr checks "${pr}" 2>/dev/null || echo "(no checks data)"
  echo ""
  echo "### Recent Workflow Runs"
  gh run list --limit 10 2>/dev/null || echo "(no run data)"
}

fetch_pr_review_comments() {
  local pr="${1}"
  local owner_repo
  owner_repo="$(gh repo view --json nameWithOwner -q .nameWithOwner)"
  gh api "repos/${owner_repo}/pulls/${pr}/comments" 2>/dev/null
}

fetch_pr_issue_comments() {
  local pr="${1}"
  local owner_repo
  owner_repo="$(gh repo view --json nameWithOwner -q .nameWithOwner)"
  gh api "repos/${owner_repo}/issues/${pr}/comments" 2>/dev/null
}

write_markdown_section() {
  local file="${1}"
  local heading="${2}"
  local content="${3}"

  if grep -q "^## ${heading}" "${file}" 2>/dev/null; then
    # Replace existing section (up to next ## or EOF)
    local tmp
    tmp="$(mktemp)"
    awk -v heading="${heading}" -v content="${content}" '
      BEGIN { skip=0; done=0 }
      /^## / && $0 != "## " heading {
        if (skip && !done) { print "## " heading "\n\n" content "\n"; done=1 }
        skip=0
      }
      /^## / && $0 == "## " heading { skip=1; next }
      skip { next }
      { print }
      END { if (!done && skip) print "## " heading "\n\n" content "\n" }
    ' "${file}" > "${tmp}"
    mv "${tmp}" "${file}"
  else
    printf '\n## %s\n\n%s\n' "${heading}" "${content}" >> "${file}"
  fi
}

update_phase_checklist() {
  local task_file="${1}"
  local phase_name="${2}"
  if [[ ! -f "${task_file}" ]]; then return; fi
  sed -i "s/- \[ \] ${phase_name}/- [x] ${phase_name}/" "${task_file}"
}

resolve_task_folder() {
  local arg="${1}"
  if [[ -d "${arg}" ]]; then
    echo "${arg}"
  elif [[ -d "${REPO_ROOT}/.ai/tasks/${arg}" ]]; then
    echo "${REPO_ROOT}/.ai/tasks/${arg}"
  else
    error "Task folder not found: ${arg}"
    error "Provide an absolute path or a folder name under .ai/tasks/"
    exit 1
  fi
}

# ---------------------------------------------------------------------------
# Safety reminder
# ---------------------------------------------------------------------------
print_safety_reminder() {
  echo -e "${YELLOW}${BOLD}SAFETY REMINDER:${RESET}"
  echo -e "${YELLOW}  - Never push to remote without explicit user approval${RESET}"
  echo -e "${YELLOW}  - Never run: rm -rf, git reset --hard, git clean -fd, docker compose down -v,${RESET}"
  echo -e "${YELLOW}               docker volume rm, docker system prune --volumes, drop database, reboot${RESET}"
  echo -e "${YELLOW}  - Never modify .env, secrets, production configs, CI configs, lock files unless in plan${RESET}"
  echo -e "${YELLOW}  - Never expose secrets in output${RESET}"
  echo ""
}

# ---------------------------------------------------------------------------
# Commands
# ---------------------------------------------------------------------------

cmd_start() {
  local description="${1:-}"
  if [[ -z "${description}" ]]; then
    error "Usage: dev-cycle.sh start \"<task description>\""
    exit 1
  fi

  detect_repo_root
  phase "Starting task: ${description}"
  ensure_clean_context

  local branch commit_hash commit_msg
  branch="$(git -C "${REPO_ROOT}" branch --show-current)"
  commit_hash="$(git -C "${REPO_ROOT}" rev-parse --short HEAD)"
  commit_msg="$(git -C "${REPO_ROOT}" log -1 --pretty=format:'%s')"
  local timestamp
  timestamp="$(date '+%Y-%m-%d %H:%M:%S %Z')"

  local slug
  slug="$(create_task_slug "${description}")"
  local folder
  folder="$(create_task_folder "${slug}")"

  cat > "${folder}/task.md" <<EOF
# Task: ${description}

**Timestamp:** ${timestamp}
**Branch:** ${branch}
**Latest Commit:** ${commit_hash} — ${commit_msg}

## Git Status at Start

\`\`\`
$(summarize_git_status)
\`\`\`

## Phase Checklist

- [ ] 01-task-intake
- [ ] 02-specification
- [ ] 03-impact-analysis
- [ ] 04-implementation-plan
- [ ] 05-implementation
- [ ] 06-local-validation
- [ ] 07-ci-investigation
- [ ] 08-pr-comments
- [ ] 09-refactor
- [ ] 10-final-review
- [ ] done
EOF

  update_phase_checklist "${folder}/task.md" "01-task-intake"

  success "Task folder created: ${folder}"
  echo ""
  info "Next step: run 'dev-cycle.sh spec ${folder}' to produce the specification."
}

cmd_spec() {
  local task_folder
  detect_repo_root
  task_folder="$(resolve_task_folder "${1:-}")"
  phase "Phase 02 — Specification"

  info "Input: ${task_folder}/task.md"
  info "Output: ${task_folder}/spec.md"
  echo ""
  echo "Read ${task_folder}/task.md and produce ${task_folder}/spec.md following the template in"
  echo "${REPO_ROOT}/.nanoclaw/workflows/prompts/02-specification.md"
  echo ""
  info "Required sections in spec.md:"
  for s in "Problem Statement" "Expected Behavior" "Non-Goals" "Assumptions" "Edge Cases" "Acceptance Criteria" "Test Strategy" "Validation Strategy"; do
    echo "  - ${s}"
  done
  echo ""
  update_phase_checklist "${task_folder}/task.md" "02-specification"
  info "When done, verify spec.md is complete and update the phase checklist in task.md."
}

cmd_impact() {
  local task_folder
  detect_repo_root
  task_folder="$(resolve_task_folder "${1:-}")"
  phase "Phase 03 — Impact Analysis"

  info "Gathering repository context..."
  echo ""
  echo "=== Repository Root ==="
  ls -la "${REPO_ROOT}/" 2>/dev/null | head -30
  echo ""

  if [[ -f "${REPO_ROOT}/Makefile" ]]; then
    echo "=== Makefile ==="
    cat "${REPO_ROOT}/Makefile"
    echo ""
  fi

  if [[ -f "${REPO_ROOT}/composer.json" ]]; then
    echo "=== composer.json ==="
    cat "${REPO_ROOT}/composer.json"
    echo ""
  fi

  echo "=== Docker Compose / Dockerfile files (first 5) ==="
  find "${REPO_ROOT}" -maxdepth 4 \( -name 'docker-compose*.yml' -o -name 'Dockerfile*' \) 2>/dev/null | head -5
  echo ""

  echo "=== Static Analysis Config files (first 5) ==="
  find "${REPO_ROOT}" -maxdepth 4 \( -name 'phpstan*' -o -name 'psalm*' -o -name '.php-cs-fixer*' \) 2>/dev/null | head -5
  echo ""

  echo "=== CI Workflow files (first 10) ==="
  find "${REPO_ROOT}/.github/workflows" -name '*.yml' 2>/dev/null | head -10
  echo ""

  echo "=== Test directories (first 5) ==="
  find "${REPO_ROOT}" -maxdepth 5 -type d \( -name 'tests' -o -name 'test' -o -name 'spec' \) 2>/dev/null | head -5
  echo ""

  info "Read ${task_folder}/task.md and produce ${task_folder}/impact-analysis.md following"
  info "${REPO_ROOT}/.nanoclaw/workflows/prompts/03-impact-analysis.md"
  echo ""
  info "Required sections:"
  for s in "Impacted Modules/Files" "Architecture Boundaries" "Dependencies" "Database/Migration Risks" "Backward Compatibility Risks" "Test Impact" "CI Impact" "Rollback Notes"; do
    echo "  - ${s}"
  done
  echo ""
  update_phase_checklist "${task_folder}/task.md" "03-impact-analysis"
}

cmd_plan() {
  local task_folder
  detect_repo_root
  task_folder="$(resolve_task_folder "${1:-}")"
  phase "Phase 04 — Implementation Plan"

  for f in task.md spec.md impact-analysis.md; do
    if [[ ! -f "${task_folder}/${f}" ]]; then
      error "Missing required input: ${task_folder}/${f}"
      error "Run previous phases first."
      exit 1
    fi
  done

  info "Read:"
  info "  ${task_folder}/task.md"
  info "  ${task_folder}/spec.md"
  info "  ${task_folder}/impact-analysis.md"
  info "Produce: ${task_folder}/plan.md"
  info "Follow: ${REPO_ROOT}/.nanoclaw/workflows/prompts/04-implementation-plan.md"
  echo ""
  info "Required sections in plan.md:"
  for s in "Step-by-step Implementation Steps" "Proposed Files to Change" "Proposed Tests to Add/Update" "Validation Commands" "Expected Risks" "Rollback Plan" "Definition of Done"; do
    echo "  - ${s}"
  done
  echo ""
  update_phase_checklist "${task_folder}/task.md" "04-implementation-plan"
}

cmd_implement() {
  local task_folder
  detect_repo_root
  task_folder="$(resolve_task_folder "${1:-}")"
  phase "Phase 05 — Implementation"

  print_safety_reminder

  if [[ ! -f "${task_folder}/plan.md" ]]; then
    error "Missing: ${task_folder}/plan.md — run 'plan' phase first."
    exit 1
  fi

  info "Enforced constraints:"
  echo "  - No push to remote"
  echo "  - No rm -rf or destructive git/docker commands"
  echo "  - No modification of .env, lock files, CI configs, static analysis configs (unless explicitly in plan)"
  echo "  - No secrets in output"
  echo ""
  info "Read: ${task_folder}/plan.md"
  info "Follow: ${REPO_ROOT}/.nanoclaw/workflows/prompts/05-implementation.md"
  info "Update: ${task_folder}/implementation-log.md with files changed, commands run, unresolved questions"
  echo ""
  update_phase_checklist "${task_folder}/task.md" "05-implementation"
}

cmd_validate() {
  local task_folder
  detect_repo_root
  task_folder="$(resolve_task_folder "${1:-}")"
  phase "Phase 06 — Local Validation"

  local validation_log="${task_folder}/validation.md"
  echo "# Validation Log" > "${validation_log}"
  echo "" >> "${validation_log}"
  echo "**Date:** $(date '+%Y-%m-%d %H:%M:%S')" >> "${validation_log}"
  echo "" >> "${validation_log}"

  # Check Docker
  if command -v docker &>/dev/null; then
    info "Checking Docker..."
    if docker info &>/dev/null; then
      success "Docker is running."
      write_markdown_section "${validation_log}" "Docker Status" "Docker daemon is running."
    else
      warn "Docker daemon not responding. Some commands may fail."
      write_markdown_section "${validation_log}" "Docker Status" "Docker daemon not responding — some commands may fail."
    fi
  fi

  local cmds=()
  while IFS= read -r line; do
    [[ -n "${line}" ]] && cmds+=("${line}")
  done < <(detect_validation_commands)

  if [[ ${#cmds[@]} -eq 0 ]]; then
    warn "No validation commands detected (no Makefile targets or composer scripts matched)."
    write_markdown_section "${validation_log}" "Validation Commands" "No matching validation commands found."
    exit 1
  fi

  info "Detected validation commands:"
  for cmd in "${cmds[@]}"; do
    echo "  ${cmd}"
  done
  echo ""

  local overall_status=0
  local attempts=0

  # Primary gate: make ci
  if printf '%s\n' "${cmds[@]}" | grep -q '^make ci$'; then
    local ci_logfile="${task_folder}/ci-run.log"
    info "Running primary gate: make ci (max ${MAX_FIX_ATTEMPTS} fix attempts)"

    while [[ "${attempts}" -lt "${MAX_FIX_ATTEMPTS}" ]]; do
      attempts=$(( attempts + 1 ))
      info "Attempt ${attempts}/${MAX_FIX_ATTEMPTS}"

      if run_and_log "${ci_logfile}" "make ci" bash -c "cd '${REPO_ROOT}' && make ci"; then
        success "make ci passed on attempt ${attempts}."
        write_markdown_section "${validation_log}" "make ci" "**Status:** PASSED (attempt ${attempts})"
        overall_status=0
        break
      else
        warn "make ci failed on attempt ${attempts}."
        local tail_output
        tail_output="$(tail -30 "${ci_logfile}" 2>/dev/null)"
        write_markdown_section "${validation_log}" "make ci (attempt ${attempts})" "**Status:** FAILED\n\n\`\`\`\n${tail_output}\n\`\`\`"
        overall_status=1

        if [[ "${attempts}" -lt "${MAX_FIX_ATTEMPTS}" ]]; then
          info "Follow prompt: ${REPO_ROOT}/.nanoclaw/workflows/prompts/06-local-validation.md"
          info "Investigate the failure above and attempt a fix before retrying."
          echo ""
        fi
      fi
    done

    if [[ "${overall_status}" -ne 0 ]]; then
      error "make ci failed after ${MAX_FIX_ATTEMPTS} attempts."
      write_markdown_section "${validation_log}" "Final Status" "FAILED after ${MAX_FIX_ATTEMPTS} attempts."
      exit 1
    fi
  else
    # Run all detected commands
    for cmd in "${cmds[@]}"; do
      local cmd_log="${task_folder}/$(echo "${cmd}" | tr ' ' '-').log"
      if run_and_log "${cmd_log}" "${cmd}" bash -c "cd '${REPO_ROOT}' && ${cmd}"; then
        write_markdown_section "${validation_log}" "${cmd}" "**Status:** PASSED"
      else
        write_markdown_section "${validation_log}" "${cmd}" "**Status:** FAILED"
        overall_status=1
      fi
    done
  fi

  update_phase_checklist "${task_folder}/task.md" "06-local-validation"
  write_markdown_section "${validation_log}" "Final Status" "$( [[ "${overall_status}" -eq 0 ]] && echo "ALL PASSED" || echo "FAILURES DETECTED" )"

  [[ "${overall_status}" -eq 0 ]] && success "Validation complete — all commands passed." || { error "Validation complete — failures detected. See ${validation_log}"; exit 1; }
}

cmd_ci() {
  local task_folder pr_input pr_number
  detect_repo_root
  task_folder="$(resolve_task_folder "${1:-}")"
  pr_input="${2:-}"

  if [[ -z "${pr_input}" ]]; then
    error "Usage: dev-cycle.sh ci <task-folder> <pr-number-or-url>"
    exit 1
  fi

  require_command gh "brew install gh  OR  apt install gh  OR  https://cli.github.com"

  pr_number="$(extract_pr_number "${pr_input}")"
  phase "Phase 07 — CI Investigation (PR #${pr_number})"

  local ci_dir="${REPO_ROOT}/.ai/ci/pr-${pr_number}"
  mkdir -p "${ci_dir}"

  info "Fetching PR metadata..."
  fetch_pr_metadata "${pr_number}" > "${ci_dir}/metadata.json" 2>/dev/null || warn "Could not fetch PR metadata."

  info "Fetching PR checks..."
  fetch_pr_checks "${pr_number}" > "${ci_dir}/checks.txt" 2>/dev/null || warn "Could not fetch PR checks."

  info "Fetching failed workflow run logs..."
  gh run list --limit 10 --json databaseId,status,conclusion,workflowName,headBranch > "${ci_dir}/runs.json" 2>/dev/null || warn "Could not fetch run list."

  info "CI data stored in: ${ci_dir}/"
  info "Produce: ${task_folder}/ci.md"
  info "Follow: ${REPO_ROOT}/.nanoclaw/workflows/prompts/07-ci-investigation.md"
  echo ""
  echo "PR Checks:"
  cat "${ci_dir}/checks.txt" 2>/dev/null || echo "(no data)"
  echo ""
  update_phase_checklist "${task_folder}/task.md" "07-ci-investigation"
}

cmd_pr_comments() {
  local task_folder pr_input pr_number
  detect_repo_root
  task_folder="$(resolve_task_folder "${1:-}")"
  pr_input="${2:-}"

  if [[ -z "${pr_input}" ]]; then
    error "Usage: dev-cycle.sh pr-comments <task-folder> <pr-number-or-url>"
    exit 1
  fi

  require_command gh "brew install gh  OR  apt install gh  OR  https://cli.github.com"

  pr_number="$(extract_pr_number "${pr_input}")"
  phase "Phase 08 — PR Comments (PR #${pr_number})"

  local review_dir="${REPO_ROOT}/.ai/reviews/pr-${pr_number}"
  mkdir -p "${review_dir}"

  info "Fetching PR metadata..."
  fetch_pr_metadata "${pr_number}" > "${review_dir}/metadata.json" 2>/dev/null || warn "Could not fetch PR metadata."

  info "Fetching review comments..."
  fetch_pr_review_comments "${pr_number}" > "${review_dir}/review-comments.json" 2>/dev/null || warn "Could not fetch review comments."

  info "Fetching issue comments..."
  fetch_pr_issue_comments "${pr_number}" > "${review_dir}/issue-comments.json" 2>/dev/null || warn "Could not fetch issue comments."

  info "Raw data stored in: ${review_dir}/"
  info "Produce:"
  info "  ${task_folder}/pr-comments.md  — grouped by file/reviewer/severity"
  info "  ${task_folder}/pr-action-plan.md  — required actions, status, owner"
  info "Follow: ${REPO_ROOT}/.nanoclaw/workflows/prompts/08-pr-comments.md"
  echo ""
  warn "Do NOT implement comments at this phase — analysis and grouping only."
  echo ""
  update_phase_checklist "${task_folder}/task.md" "08-pr-comments"
}

cmd_refactor() {
  local task_folder pr_input pr_number
  detect_repo_root
  task_folder="$(resolve_task_folder "${1:-}")"
  pr_input="${2:-}"

  if [[ -z "${pr_input}" ]]; then
    error "Usage: dev-cycle.sh refactor <task-folder> <pr-number-or-url>"
    exit 1
  fi

  phase "Phase 09 — Refactor from PR Comments"
  print_safety_reminder

  pr_number="$(extract_pr_number "${pr_input}")"

  if [[ ! -f "${task_folder}/pr-action-plan.md" ]]; then
    error "Missing: ${task_folder}/pr-action-plan.md — run 'pr-comments' phase first."
    exit 1
  fi

  info "Read: ${task_folder}/pr-action-plan.md"
  info "Follow: ${REPO_ROOT}/.nanoclaw/workflows/prompts/09-refactor.md"
  echo ""
  info "Rules:"
  echo "  - Implement only clear and safe comments"
  echo "  - Ambiguous comments → document in needs-human-decision.md"
  echo "  - Conflicts with spec/architecture → document before changing"
  echo "  - Run targeted validation after each meaningful refactor"
  echo "  - Run full validation at end"
  echo "  - If make ci exists, run it before declaring refactor complete"
  echo ""
  info "Update: ${task_folder}/implementation-log.md"
  echo ""

  # Run validation at end
  info "After implementing, run: dev-cycle.sh validate ${task_folder}"
  update_phase_checklist "${task_folder}/task.md" "09-refactor"
}

cmd_review() {
  local task_folder
  detect_repo_root
  task_folder="$(resolve_task_folder "${1:-}")"
  phase "Phase 10 — Final Review"

  info "Git diff stat:"
  summarize_git_diff HEAD~1 2>/dev/null || summarize_git_diff HEAD 2>/dev/null

  echo ""
  info "Checklist — inspect for:"
  for item in \
    "Scope creep (changes beyond what was planned)" \
    "Debug code, print statements, commented-out blocks" \
    "Secrets or credentials in code/output" \
    "Risky destructive operations" \
    "Missing tests for new behavior" \
    "Architecture boundary violations" \
    "Coding standard issues" \
    "Unresolved TODOs or FIXMEs" \
    "Failing validation gates" \
    "Failing CI checks"; do
    echo "  [ ] ${item}"
  done

  echo ""
  info "Produce:"
  info "  ${task_folder}/review.md"
  info "  ${task_folder}/final-summary.md (must include all required sections)"
  info "Follow: ${REPO_ROOT}/.nanoclaw/workflows/prompts/10-final-review.md"
  echo ""
  info "final-summary.md required sections:"
  for s in "Task Summary" "Implementation Summary" "Changed Files" "Validation Commands Run" "Validation Status" "CI Status" "PR Comments Handled" "Unresolved Risks" "Recommended Commit Message" "Recommended PR Response"; do
    echo "  - ${s}"
  done
  echo ""
  update_phase_checklist "${task_folder}/task.md" "10-final-review"
  update_phase_checklist "${task_folder}/task.md" "done"
}

cmd_full() {
  local description="${1:-}"
  if [[ -z "${description}" ]]; then
    error "Usage: dev-cycle.sh full \"<task description>\""
    exit 1
  fi

  cmd_start "${description}"

  # Capture the folder created by start
  detect_repo_root
  local slug
  slug="$(create_task_slug "${description}")"
  # Find most recently created matching folder
  local folder
  folder="$(find "${REPO_ROOT}/.ai/tasks" -maxdepth 1 -type d -name "*${slug:16}*" 2>/dev/null | sort | tail -1)"

  if [[ -z "${folder}" ]]; then
    # Fall back to latest folder
    folder="$(find "${REPO_ROOT}/.ai/tasks" -maxdepth 1 -mindepth 1 -type d 2>/dev/null | sort | tail -1)"
  fi

  [[ -z "${folder}" ]] && { error "Could not find task folder after start."; exit 1; }

  cmd_spec "${folder}"
  cmd_impact "${folder}"
  cmd_plan "${folder}"

  echo ""
  success "Planning phases complete."
  echo ""
  echo "Task folder: ${folder}"
  echo "Review ${folder}/plan.md and run 'implement' when ready."
  echo ""
  echo "  dev-cycle.sh implement ${folder}"
}

# ---------------------------------------------------------------------------
# Help
# ---------------------------------------------------------------------------

usage() {
  cat <<USAGE
${BOLD}NanoClaw Dev Cycle${RESET} — deterministic development workflow for user-service

${BOLD}USAGE${RESET}
  dev-cycle.sh <command> [arguments]

${BOLD}COMMANDS${RESET}

  ${CYAN}start${RESET} "<description>"
      Begin a new task. Creates .ai/tasks/<slug>/ with all artifact files.
      Example: dev-cycle.sh start "add email verification endpoint"

  ${CYAN}spec${RESET} <task-folder>
      Phase 02: Produce spec.md from task description.
      Example: dev-cycle.sh spec .ai/tasks/20240101-120000-add-email-verification

  ${CYAN}impact${RESET} <task-folder>
      Phase 03: Gather repo context and produce impact-analysis.md.

  ${CYAN}plan${RESET} <task-folder>
      Phase 04: Read spec + impact, produce implementation plan.

  ${CYAN}implement${RESET} <task-folder>
      Phase 05: Follow plan.md, update implementation-log.md.

  ${CYAN}validate${RESET} <task-folder>
      Phase 06: Run all available validation commands, log results.

  ${CYAN}ci${RESET} <task-folder> <pr-number-or-url>
      Phase 07: Fetch CI checks, failed job info for a PR.
      Example: dev-cycle.sh ci .ai/tasks/... 42
               dev-cycle.sh ci .ai/tasks/... https://github.com/org/repo/pull/42

  ${CYAN}pr-comments${RESET} <task-folder> <pr-number-or-url>
      Phase 08: Fetch and group PR review comments for analysis.

  ${CYAN}refactor${RESET} <task-folder> <pr-number-or-url>
      Phase 09: Implement safe PR comments, run validation.

  ${CYAN}review${RESET} <task-folder>
      Phase 10: Final code review, produce final-summary.md.

  ${CYAN}full${RESET} "<description>"
      Convenience: runs start → spec → impact → plan, then stops.
      Example: dev-cycle.sh full "refactor auth middleware"

${BOLD}DIRECTORY LAYOUT${RESET}

  .ai/
    tasks/<slug>/          Task-specific artifacts
      task.md              Task description + phase checklist
      spec.md              Specification
      impact-analysis.md   Impact analysis
      plan.md              Implementation plan
      implementation-log.md Change log during implementation
      validation.md        Validation results
      ci.md                CI investigation notes
      review.md            Code review notes
      final-summary.md     Final summary for PR
    reviews/pr-<N>/        Raw PR comment JSON
    ci/pr-<N>/             Raw CI check data

  .nanoclaw/workflows/
    dev-cycle.sh           This script
    prompts/               Phase-specific agent prompt files
    config.example.env     Configuration template

${BOLD}CONFIGURATION${RESET}

  Copy .nanoclaw/workflows/config.example.env to .nanoclaw/workflows/config.env
  and source it, or export variables directly:

    NANOCLAW_WORKFLOW_MAX_FIX_ATTEMPTS=3   Max CI fix loops
    NANOCLAW_WORKFLOW_LOG_LEVEL=info       debug | info | warn
    NANOCLAW_WORKFLOW_COLORS=auto          auto | always | never
    NANOCLAW_WORKFLOW_REPO_ROOT=           Override repo root (auto-detected)
    NANOCLAW_WORKFLOW_GH_REMOTE=origin     Git remote for gh commands

${BOLD}PREREQUISITES${RESET}

  bash >= 4, git, make, composer, docker, gh (GitHub CLI)

${BOLD}SAFETY RULES${RESET}

  - Never push to remote without explicit user approval
  - Never run destructive commands (rm -rf, git reset --hard, etc.)
  - Never modify .env, lock files, CI configs unless explicitly planned

USAGE
}

# ---------------------------------------------------------------------------
# Main dispatcher
# ---------------------------------------------------------------------------

main() {
  local command="${1:-}"

  case "${command}" in
    --help|-h|"") usage; exit 0 ;;
    start)        shift; cmd_start "$@" ;;
    spec)         shift; cmd_spec "$@" ;;
    impact)       shift; cmd_impact "$@" ;;
    plan)         shift; cmd_plan "$@" ;;
    implement)    shift; cmd_implement "$@" ;;
    validate)     shift; cmd_validate "$@" ;;
    ci)           shift; cmd_ci "$@" ;;
    pr-comments)  shift; cmd_pr_comments "$@" ;;
    refactor)     shift; cmd_refactor "$@" ;;
    review)       shift; cmd_review "$@" ;;
    full)         shift; cmd_full "$@" ;;
    *)
      error "Unknown command: ${command}"
      echo ""
      usage
      exit 1
      ;;
  esac
}

main "$@"
