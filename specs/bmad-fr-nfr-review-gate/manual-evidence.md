---
workflowType: manual-evidence
project_name: BMAD FR/NFR Review Gate
author: Codex
date: 2026-05-18
revision: 16
---

# Manual Evidence: BMAD FR/NFR Review Gate

## Session

- Tester: Codex
- Date: 2026-05-18
- Workspace: `codex/bmad-review-gate`; the current PR head is verified by
  GitHub gate checks before completion.
- Related requirements: FR-01 through FR-11, NFR-01 through NFR-03, NFR-05
  through NFR-07, NFR-09, AC-01 through AC-11

## Scenarios

| Scenario                        | Steps                                                                                                                  | Observed Result                                                                         | Related IDs          |
| ------------------------------- | ---------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------- | -------------------- |
| Make discovery                  | Run the Make discovery command from Verification Commands.                                                             | Both Make targets are listed.                                                           | AC-01, FR-10         |
| Missing spec fails closed       | Run `./scripts/bmad-fr-nfr-review-gate.sh` without `BMAD_REVIEW_SPEC_PATH`.                                            | Command exits non-zero with `Error: --spec or BMAD_REVIEW_SPEC_PATH is required.`       | AC-02, NFR-03        |
| Missing option value            | Run Bats fake-agent test `bmad-fr-nfr-review-gate requires option values`.                                             | Command exits non-zero with a clear option-specific error.                              | AC-02, NFR-03        |
| Missing manual evidence path    | Run Bats fake-agent test `bmad-fr-nfr-review-gate fails before agent invocation when manual evidence path is missing`. | Command exits non-zero before invoking the fake Codex command.                          | AC-03, NFR-03        |
| Fake PASS with all markers      | Run Bats fake-agent test `bmad-fr-nfr-review-gate make target forces pinned BMAD constants`.                           | Command exits successfully with `AI review PASS.`                                       | AC-04, AC-05, AC-07  |
| Verify on pass default          | Run Bats fake-agent test `ai-review-loop verifies after PASS by default`.                                              | A first-pass `STATUS: PASS` still runs the default verification command.                | FR-08, NFR-03        |
| Ambient AI review vars ignored  | Run Bats fake-agent test `bmad-fr-nfr-review-gate ignores ambient AI_REVIEW target vars`.                              | BMAD wrapper uses BMAD inputs/defaults, not unrelated ambient AI review targets.        | FR-08, NFR-03        |
| Existing spec env fallback      | Run Bats fake-agent test `make bmad-fr-nfr-review-gate accepts AI_REVIEW_SPEC_PATH fallback`.                          | Make target delegates successfully when only `AI_REVIEW_SPEC_PATH` is set.              | FR-02, NFR-05        |
| Direct wrapper path resolution  | Run Bats fake-agent test `bmad-fr-nfr-review-gate resolves paths outside the repo cwd`.                                | Wrapper finds prompts and the delegate review loop when called from another cwd.        | NFR-06, NFR-07       |
| Branch/tag base ambiguity       | Run Bats fake-agent test `ai-review-loop resolves local base branch before same-named tag`.                            | Review prompt receives the fully qualified `refs/heads/...` branch ref.                 | FR-02, NFR-07        |
| Hex-like remote base branch     | Run Bats fake-agent test `ai-review-loop fetches hex-like branch names before treating them as commits`.               | Remote branch `deadbeef` resolves as `refs/remotes/origin/deadbeef`.                    | FR-02, NFR-07        |
| Prompt placeholders with `&`    | Run Bats fake-agent test `ai-review-loop substitutes BMAD review gate placeholders` with `&` in spec/evidence paths.   | Prompt output preserves literal `&` characters instead of expanding pattern matches.    | AC-04, NFR-07        |
| Explicit HEAD ancestry base     | Run Bats fake-agent test `ai-review-loop accepts explicit HEAD ancestry base refs`.                                    | Explicit `HEAD~` base refs are accepted and passed to the review prompt.                | FR-02, NFR-07        |
| Claude gate marker routing      | Run Bats fake-agent test `ai-review-loop claude agent skips built-in review when gate markers are required`.           | Claude uses the composed prompt path instead of `/review` when marker validation is on. | FR-07, NFR-03        |
| CRLF marker output              | Run Bats fake-agent test `ai-review-loop accepts CRLF gate markers`.                                                   | CRLF reviewer output is accepted when all required markers are present.                 | FR-07, NFR-03        |
| Dash-leading gate marker        | Run Bats fake-agent test `ai-review-loop accepts required gate markers that begin with a dash`.                        | Marker validation treats `-CUSTOM_MARKER: PASS` as data, not a `grep` option.           | FR-07, NFR-03        |
| Shortened marker override       | Run Bats fake-agent test `bmad-fr-nfr-review-gate rejects shortened required marker override`.                         | Command exits non-zero and reports the missing `NFR_CATALOG_SCORECARD: PASS` marker.    | AC-06, AC-07, NFR-03 |
| Leading prose before status     | Run Bats fake-agent test `bmad-fr-nfr-review-gate requires STATUS on the first line`.                                  | Command exits non-zero and reports the agent did not produce a valid status line.       | AC-08, NFR-03        |
| Verification failure after PASS | Run Bats fake-agent test `bmad-fr-nfr-review-gate fails when verification fails after PASS`.                           | Command exits non-zero with `Verification failed after AI review PASS`.                 | FR-08, NFR-03        |
| No required CI checks fallback  | Run Bats fake-agent test `bmad-fr-nfr-review-gate falls back to visible checks when required check rollup is empty`.   | Command exits successfully after verifying every visible PR check is passing.           | AC-11, NFR-09        |
| Empty visible CI check rollup   | Run Bats fake-agent test `bmad-fr-nfr-review-gate rejects PASS when visible GitHub check rollup is empty`.             | Command exits non-zero with `Warning: GitHub PR check rollup is empty.`                 | AC-11, NFR-03        |
| GitHub hard-gate fail-fast      | Run Bats fake-agent test `bmad-fr-nfr-review-gate rejects PASS when GitHub checks are not passing`.                    | Command exits before AI review with `GitHub corroboration failed before AI review.`     | AC-11, NFR-03        |
| GitHub/CI evidence markers      | Run Bats fake-agent marker and scorecard validation scenarios.                                                         | PASS output must include approved GitHub state and passing CI rollup evidence markers.  | FR-07, AC-05         |
| Markdown NFR scorecard rows     | Run Bats fake-agent test `ai-review-loop accepts pinned NFR category coverage in markdown table rows`.                 | Each pinned NFR category is accepted from a markdown table row with an explicit 5/5.    | FR-06, FR-07, AC-05  |
| Markdown formatting             | Run the targeted Prettier check for touched docs/spec/prompt files.                                                    | Command exits successfully with `All matched files use Prettier code style!`.           | NFR-01, NFR-07       |

## Verification Commands

```bash
make help | rg "bmad-fr-nfr-review-gate|ai-review-loop"
bash -n scripts/ai-review-loop.sh
bash -n scripts/bmad-fr-nfr-review-gate.sh
test -d tests/CLI/bats/bats-support || \
  git clone --depth 1 https://github.com/bats-core/bats-support.git tests/CLI/bats/bats-support
test -d tests/CLI/bats/bats-assert || \
  git clone --depth 1 https://github.com/bats-core/bats-assert.git tests/CLI/bats/bats-assert
env \
  -u AI_REVIEW_VERIFY_CMD \
  -u AI_REVIEW_VERIFY_ON_PASS \
  -u AI_REVIEW_REVIEW_PROMPT \
  -u AI_REVIEW_FIX_PROMPT \
  -u AI_REVIEW_SPEC_PATH \
  -u AI_REVIEW_MANUAL_EVIDENCE \
  -u AI_REVIEW_PR_NUMBER \
  -u AI_REVIEW_SCORE_THRESHOLD \
  -u AI_REVIEW_NFR_CATEGORIES \
  -u AI_REVIEW_REQUIRE_GATE_MARKERS \
  -u AI_REVIEW_REQUIRED_GATE_MARKERS \
  -u AI_REVIEW_REQUIRE_SCORECARD_VALIDATION \
  -u AI_REVIEW_REQUIRE_GITHUB_CI_CORROBORATION \
  -u AI_REVIEW_BASE_REF \
  -u AI_REVIEW_MAX_ITER \
  -u AI_REVIEW_AGENT \
  -u AI_REVIEW_AGENTS \
  -u AI_REVIEW_LOG_DIR \
  -u AI_REVIEW_BASE \
  -u AI_REVIEW_CLAUDE_USE_BUILTIN_REVIEW \
  -u AI_REVIEW_REVIEW_SANDBOX \
  -u AI_REVIEW_FIX_SANDBOX \
  -u BMAD_REVIEW_VERIFY_CMD \
  -u BMAD_REVIEW_SPEC_PATH \
  -u BMAD_REVIEW_MANUAL_EVIDENCE \
  -u BMAD_REVIEW_PR \
  -u BMAD_REVIEW_BASE \
  -u BMAD_REVIEW_MAX_ITER \
  -u BMAD_REVIEW_LOG_DIR \
  -u BMAD_REVIEW_AGENTS \
  TMPDIR=/dev/shm bats tests/CLI/bats/make_ai_review_loop_tests.bats
env TMPDIR=/dev/shm bats tests/CLI/bats/make_bmalph_tests.bats
git diff --check
npx --yes prettier --check \
  .claude/skills/AI-AGENT-GUIDE.md \
  .claude/skills/README.md \
  .claude/skills/SKILL-DECISION-GUIDE.md \
  .claude/skills/bmad-fr-nfr-review-gate/SKILL.md \
  .agents/skills/bmad-fr-nfr-review-gate/SKILL.md \
  AGENTS.md CLAUDE.md README.md \
  docs/getting-started.md docs/onboarding.md \
  scripts/ai-review-prompts/bmad-fr-nfr-review.md \
  scripts/ai-review-prompts/bmad-fr-nfr-fix.md \
  specs/bmad-fr-nfr-review-gate/*.md
```

## Artifacts

- `tests/CLI/bats/make_ai_review_loop_tests.bats`: deterministic fake-agent
  scenarios. The Bats helper clones are temporary local test dependencies and
  are removed before committing.
- `scripts/bmad-fr-nfr-review-gate.sh`: wrapper behavior under test.
- `scripts/ai-review-prompts/bmad-fr-nfr-review.md`: required marker and
  scorecard contract.
- GitHub PR checks and approval evidence are intentionally not recorded here;
  the gate must verify those from the open PR before completion.
