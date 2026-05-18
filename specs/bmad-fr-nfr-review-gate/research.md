---
workflowType: research
project_name: BMAD FR/NFR Review Gate
author: Codex
date: 2026-05-17
revision: 1
---

# Research: BMAD FR/NFR Review Gate

## Current State

The repository already has a local AI review loop through
`scripts/ai-review-loop.sh` and `make ai-review-loop`. The loop supports Codex
and Claude reviewers, parses `STATUS: PASS` or `STATUS: FAIL`, runs a fixer
when review fails, and verifies fixes with a configurable command.

The existing prompt is a generic code review prompt. It does not read BMAD
specs, inventory FR/NFR requirements, score implementation evidence, require
manual test evidence, or check the NonFunctionals.com catalog categories.

BMALPH is installed through the repo's `make bmalph-setup` path. The generated
`_bmad/` assets are local and ignored. Durable automation must therefore live in
tracked repo files such as `.claude/skills/`, `.agents/skills/`, `scripts/`,
`scripts/ai-review-prompts/`, `Makefile`, tests, docs, and `specs/`.

## NFR Source

The review gate pins the NonFunctionals.com catalog categories visible on
2026-05-17:

- Performance
- Usability
- Maintainability
- Availability
- Interoperability
- Security
- Manageability
- Automatability
- Dependability

Source: https://nonfunctionals.com/catalog.html

## Integration Points

- `Makefile`: add a discoverable post-implementation gate target.
- `scripts/ai-review-loop.sh`: reuse the existing agent loop, status parser,
  fixer phase, and verification phase.
- `scripts/ai-review-prompts/`: add BMAD-specific review and fix prompts.
- `.claude/skills/code-review/SKILL.md`: the new gate complements PR comment
  handling.
- `.claude/skills/ci-workflow/SKILL.md`: `make ci` remains the local CI gate.
- `.agents/skills/`: Codex skill wrapper for discoverability.

## Constraints

- Do not depend on generated `_bmad/` files at runtime.
- Keep `make ai-review-loop` backward compatible.
- Keep first-line review output as `STATUS: PASS` or `STATUS: FAIL`.
- Fail closed when evidence is missing, GitHub state cannot be verified, CI
  cannot be verified, or any requirement score is below 5/5.
- Do not fabricate manual test evidence.
- Do not lower repository quality thresholds.

## Risks

- Prompt-only scoring can overstate confidence. Mitigation: require source
  requirement evidence, implementation evidence, automated test evidence, and
  manual evidence where automation cannot prove behavior.
- GitHub or CI can be temporarily unavailable. Mitigation: report blocked or
  failed gate instead of passing.
- Overusing not-applicable decisions weakens the gate. Mitigation: require a
  concrete reason and source evidence for every not-applicable row.
- Agent availability may vary locally and in CI. Mitigation: keep the gate as a
  standalone target and configurable through environment variables.
