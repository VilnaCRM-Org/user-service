# Implementation Readiness

## Alignment Matrix

| Area                                          | Requirement Source  | Implementation Evidence                                                           | Status                                                                                                                                           |
| --------------------------------------------- | ------------------- | --------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| FR/NFR review coverage                        | `prd.md`            | `scripts/ai-review-prompts/review.md`                                             | Aligned                                                                                                                                          |
| System design checks with applicability guard | `prd.md`            | `scripts/ai-review-prompts/review.md`                                             | Aligned                                                                                                                                          |
| Design-pattern fit                            | `prd.md`            | `scripts/ai-review-prompts/review.md`                                             | Aligned                                                                                                                                          |
| Code-smell review                             | `prd.md`            | `scripts/ai-review-prompts/review.md`                                             | Aligned                                                                                                                                          |
| Repository architecture rules                 | `prd.md`            | `scripts/ai-review-prompts/review.md`, `scripts/ai-review-prompts/fix.md`         | Aligned                                                                                                                                          |
| First-line status contract                    | `prd.md`            | `scripts/ai-review-prompts/review.md`, `scripts/ai-review-loop.sh`                | Aligned                                                                                                                                          |
| Scoped fixer behavior                         | `prd.md`            | `scripts/ai-review-prompts/fix.md`                                                | Aligned                                                                                                                                          |
| Claude policy parity                          | `prd.md`            | `scripts/ai-review-loop.sh`, `tests/CLI/bats/make_ai_review_loop_tests.bats`      | Aligned; fake-agent Bats proves invocation shape. Live Claude CLI evidence is unavailable in this environment because `claude` is not installed. |
| Bats coverage                                 | `epics.md`          | `tests/CLI/bats/make_ai_review_loop_tests.bats`                                   | Aligned                                                                                                                                          |
| Docs sync                                     | `epics.md`          | `.claude/skills/code-review/SKILL.md`, `docs/onboarding.md`                       | Aligned                                                                                                                                          |
| BMALPH trace                                  | `_bmad/COMMANDS.md` | Planning bundle includes research, brief, PRD, architecture, epics, and readiness | Aligned                                                                                                                                          |

## Gaps

- Final BMAD FR/NFR gate requires a committed PR head so the wrapper-generated
  impact context, GitHub completion gate, and CI corroboration all reference the
  same SHA.
- Live Claude CLI behavior cannot be collected in this environment because
  `claude` is not installed. Repository policy makes Claude optional
  (`AI_REVIEW_AGENTS=codex,claude`), while Bats proves the configured invocation
  shape and policy propagation.

## Risks

- Prompt-based policy is less deterministic than hard-coded static checks.
- Broader review criteria can increase false positives, mitigated by the
  applicability rule limiting failures to concrete changed-code issues.
- Bats tests assert representative prompt-policy strings, applicability
  guardrails, NFR checklist retention, and invocation shape; they do not replace
  the live AI reviewer and BMAD gate.

## Validation Evidence Needed

1. Focused Bats coverage for prompt scope, applicability, NFR checklist,
   Claude policy propagation, and base-ref precedence passes. Done: 7 tests.
2. Full Bats helper suite passes. Done: 94 tests.
3. `make ci` completes successfully. Done.
4. `make ai-review-loop` returns parseable `STATUS: PASS` under default Codex.
   Done: `STATUS: PASS`, `0 issues`.
5. If the loop applies fixes, rerun `make ci` and `make ai-review-loop` until
   PASS. Not applicable: no fixer phase ran.
6. After commit and PR creation, rerun the BMAD FR/NFR gate against the PR head
   and require PASS with GitHub completion and CI corroboration.

## Readiness Verdict

Ready for commit, PR creation, and PR-scoped BMAD/GitHub gates.

The implementation is aligned with the PRD, architecture, and epics for the
core behavior: expanded review prompt, scoped fix prompt, Claude policy
bridging, Bats coverage, documentation sync, and BMALPH trace. Completion still
depends on running the BMAD gate against a committed PR head and waiting for
GitHub CI and reviewer status.
