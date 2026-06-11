# Architecture: BMAD/AI Review Loop Design Coverage

## Context

The local AI review loop loads `scripts/ai-review-prompts/review.md` and
`scripts/ai-review-prompts/fix.md`, runs configured agents, writes logs under
`var/ai-review`, parses review status, invokes a fixer on failures, then
verifies with `make ci`.

The parser contract is authoritative: reviewer output must expose
`STATUS: PASS` or `STATUS: FAIL` in the first 10 lines, and the prompt requires
that value on the first line. Repository architecture rules override external
design guidance.

## Decision

Add FR/NFR, system design, design-pattern, software-engineering, and code-smell
review criteria through prompt policy. Keep the status parser and fix-loop
mechanics unchanged.

Codex already consumes `review.md` directly. Claude uses its built-in `/review`
path, so the loop appends the same repository review policy through
`--append-system-prompt` while preserving `/review` and the first-line status
contract.

The fix prompt constrains remediation to the current PR scope and requires the
smallest coherent change for design, pattern, and smell findings.

## Components

- `scripts/ai-review-prompts/review.md`: primary review policy, including
  FR/NFR, system design, pattern fit, code smells, repository architecture, and
  applicability rules.
- `scripts/ai-review-prompts/fix.md`: remediation policy for failed reviews and
  CI output.
- `scripts/ai-review-loop.sh`: orchestration, status parsing, logging, fix loop,
  CI verification, and Claude `/review` prompt-policy bridging.
- `tests/CLI/bats/make_ai_review_loop_tests.bats`: contract tests for prompt
  criteria, parser-compatible loop behavior, and Claude prompt-policy bridging.
- `.claude/skills/code-review/SKILL.md` and `docs/onboarding.md`: contributor
  documentation.

## Data And Control Flow

1. Developer runs `make ai-review-loop`.
2. The loop resolves the base branch and configured review agents.
3. For Codex, the loop loads `review.md`, substitutes `{BASE_REF}`, and runs the
   agent in read-only mode.
4. For Claude, the loop runs `/review` and appends the same substituted
   repository review policy as a system prompt.
5. Review output is written to `var/ai-review`.
6. The parser scans the first 10 lines for `STATUS: PASS` or `STATUS: FAIL`.
7. On PASS, the loop exits successfully if the last verification succeeded.
8. On FAIL, the loop builds a fix prompt from `fix.md`, failed review output,
   and optional CI output.
9. The fix agent applies scoped changes.
10. The loop runs the verification command, defaulting to `make ci`.
11. The process repeats until PASS or `AI_REVIEW_MAX_ITER` is reached.

## Constraints

- Preserve the exact status output contract.
- Review only changed code or directly affected behavior.
- Do not fail trivial localized changes for irrelevant heavyweight design
  concerns.
- Do not introduce patterns just to satisfy checklist coverage.
- Domain remains framework-free; Application owns use cases/contracts;
  Infrastructure owns external adapters.
- PHP tooling must be run through make targets or the PHP container.

## Tradeoffs

Prompt-policy implementation has low blast radius and preserves parser
compatibility, but it is less deterministic than hard-coded policy checks.

Keeping the status parser simple avoids schema churn, but prevents richer
structured review output without a future compatibility layer.

Passing the repository prompt to Claude improves reviewer parity, but the
Claude path still depends on `/review` behavior. The Bats fake-agent test guards
that the expanded criteria are sent.

## Test Strategy

- Bats tests assert `review.md` contains system design, design pattern, code
  smell, and FR/NFR criteria.
- Bats tests assert `fix.md` requires scoped remediation with the smallest
  coherent change.
- Existing fake-agent tests validate parser-compatible PASS behavior.
- A Claude fake-agent test validates `/review` still runs and receives the code
  smell review criteria.
- CI validation remains `make ci`.
- Final readiness requires `make ai-review-loop` to return parseable PASS.

## Operational Notes

- Default reviewer remains Codex.
- Review logs continue under `var/ai-review`.
- If fixes are applied, rerun `make ci` and `make ai-review-loop` until PASS.
