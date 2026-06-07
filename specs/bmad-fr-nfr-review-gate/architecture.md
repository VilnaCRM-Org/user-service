---
workflowType: architecture
project_name: BMAD FR/NFR Review Gate
author: Codex
date: 2026-05-17
revision: 1
---

# Architecture: BMAD FR/NFR Review Gate

## Decision

Implement the gate as a tracked wrapper around `scripts/ai-review-loop.sh`.
This preserves the existing reviewer/fixer/verification behavior and avoids a
second agent orchestration implementation.

## Components

- `scripts/bmad-fr-nfr-review-gate.sh`: validates inputs, selects BMAD review
  and fix prompts, enables required PASS markers, enables verification after
  PASS, forces the 5/5 threshold, pinned NFR catalog, expanded quality
  dimensions, and whole-codebase impact surfaces, creates or passes impact
  context, then delegates to `scripts/ai-review-loop.sh`.
- `scripts/ai-review-loop.sh`: adds generic placeholder substitution, optional
  spec/manual/PR/impact values, required marker validation, scorecard
  validation for NFR, expanded-quality, and impact sections, and
  verification-on-PASS. In BMAD mode it can also publish bounded PR comments
  and a GitHub commit status for pending, failed, and passed gate outcomes.
- `scripts/ai-review-prompts/bmad-fr-nfr-review.md`: strict reviewer contract.
- `scripts/ai-review-prompts/bmad-fr-nfr-fix.md`: fix-agent contract.
- `Makefile`: adds `bmad-fr-nfr-review-gate`.
- `.claude/skills/bmad-fr-nfr-review-gate/SKILL.md`: canonical workflow.
- `.agents/skills/bmad-fr-nfr-review-gate/SKILL.md`: Codex entrypoint.
- `tests/CLI/bats/make_ai_review_loop_tests.bats`: fake-agent tests.

## Runtime Flow

1. User sets `BMAD_REVIEW_SPEC_PATH`.
2. Make invokes `scripts/bmad-fr-nfr-review-gate.sh`.
3. Wrapper validates spec/manual evidence paths.
4. Wrapper exports BMAD-specific `AI_REVIEW_*` variables, including expanded
   quality dimensions and whole-codebase impact surfaces.
5. `scripts/ai-review-loop.sh` runs configured reviewer agents.
6. Review output must use `STATUS: PASS` or `STATUS: FAIL` as the exact first
   line in BMAD mode.
7. PASS must include required scorecard markers, including
   `EXPANDED_QUALITY_SCORECARD: PASS`, `WHOLE_CODEBASE_IMPACT: PASS`, and
   `CI_GATE: PASS`.
8. PASS triggers verification command before successful exit.
9. Failed review iterations can publish a failure status, then trigger existing
   fix and verify iterations until PASS or max iteration.
10. Terminal PASS/FAIL can publish a concise PR comment and final commit status.

## Configuration

| Variable                              | Purpose                                                                              |
| ------------------------------------- | ------------------------------------------------------------------------------------ |
| `BMAD_REVIEW_SPEC_PATH`               | BMAD spec bundle or file; falls back to `AI_REVIEW_SPEC_PATH`.                       |
| `BMAD_REVIEW_MANUAL_EVIDENCE`         | Optional manual evidence file or directory.                                          |
| `BMAD_REVIEW_PR`                      | Optional PR number.                                                                  |
| `BMAD_REVIEW_BASE`                    | Optional base ref.                                                                   |
| `BMAD_REVIEW_AGENTS`                  | Optional comma-separated agents.                                                     |
| `BMAD_REVIEW_MAX_ITER`                | Optional max loop iterations.                                                        |
| `BMAD_REVIEW_VERIFY_CMD`              | Optional trusted verification command.                                               |
| `BMAD_REVIEW_LOG_DIR`                 | Optional log directory.                                                              |
| `BMAD_REVIEW_IMPACT_CONTEXT`          | Graphify/codebase-memory/Deptrac or manual graph impact context file.                |
| `BMAD_REVIEW_POST_PR_COMMENT`         | Optional PR comment publishing toggle, default `true`.                               |
| `BMAD_REVIEW_POST_GITHUB_STATUS`      | Optional GitHub commit-status publishing toggle, default `true`.                     |
| `BMAD_REVIEW_STATUS_CONTEXT`          | Optional commit-status context, default `BMAD FR/NFR Review Gate`.                   |
| `BMAD_REVIEW_STATUS_EXCLUDED_CONTEXT` | Optional PR-check context excluded during corroboration; defaults to status context. |

## Backward Compatibility

- `make ai-review-loop` keeps its existing default prompt and behavior.
- Claude still uses built-in `/review` for the generic prompt.
- BMAD mode disables built-in Claude review so the spec prompt is used.
- No generated `_bmad/` file is required at runtime.
- No PHP dependencies are added for the BMAD gate. Adjacent PR completion
  remediation, such as a lockfile-only security update within existing
  `composer.json` constraints, must be traced in manual evidence instead of
  being treated as gate implementation.
- Graphify, codebase-memory MCP, Deptrac graph output, CodeQL, SCIP, and
  similar graph/index tools are context providers. The wrapper creates a
  bounded local graph/relationship impact context when no graph artifact is
  supplied, so graph-backed impact analysis remains usable in minimal local and
  CI environments.

## Security

The prompt explicitly reviews evidence paths and does not require secrets. Full
diffs are not embedded by the wrapper; agents inspect repository context using
their normal tool access.
