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
  PASS, forces the 5/5 threshold and pinned NFR catalog, then delegates to
  `scripts/ai-review-loop.sh`.
- `scripts/ai-review-loop.sh`: adds generic placeholder substitution, optional
  spec/manual/PR values, required marker validation, and verification-on-PASS.
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
4. Wrapper exports BMAD-specific `AI_REVIEW_*` variables.
5. `scripts/ai-review-loop.sh` runs configured reviewer agents.
6. Review output must use `STATUS: PASS` or `STATUS: FAIL` as the exact first
   line in BMAD mode.
7. PASS must include required scorecard markers, including `CI_GATE: PASS`.
8. PASS triggers verification command before successful exit.
9. FAIL triggers existing fix and verify iterations until PASS or max iteration.

## Configuration

| Variable                      | Purpose                                                        |
| ----------------------------- | -------------------------------------------------------------- |
| `BMAD_REVIEW_SPEC_PATH`       | BMAD spec bundle or file; falls back to `AI_REVIEW_SPEC_PATH`. |
| `BMAD_REVIEW_MANUAL_EVIDENCE` | Optional manual evidence file or directory.                    |
| `BMAD_REVIEW_PR`              | Optional PR number.                                            |
| `BMAD_REVIEW_BASE`            | Optional base ref.                                             |
| `BMAD_REVIEW_AGENTS`          | Optional comma-separated agents.                               |
| `BMAD_REVIEW_MAX_ITER`        | Optional max loop iterations.                                  |
| `BMAD_REVIEW_VERIFY_CMD`      | Optional trusted verification command.                         |
| `BMAD_REVIEW_LOG_DIR`         | Optional log directory.                                        |

## Backward Compatibility

- `make ai-review-loop` keeps its existing default prompt and behavior.
- Claude still uses built-in `/review` for the generic prompt.
- BMAD mode disables built-in Claude review so the spec prompt is used.
- No generated `_bmad/` file is required at runtime.
- No PHP dependencies are added.

## Security

The prompt explicitly reviews evidence paths and does not require secrets. Full
diffs are not embedded by the wrapper; agents inspect repository context using
their normal tool access.
