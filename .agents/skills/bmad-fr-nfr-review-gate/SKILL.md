---
name: bmad-fr-nfr-review-gate
description: >
  Codex entrypoint for post-implementation BMAD FR/NFR review gates. Use after
  a PR, feature, or bugfix has BMAD specs and must be checked against every
  functional requirement, non-functional requirement, expanded quality
  dimension, whole-codebase impact surface, manual-test expectation, GitHub
  review comment, approval, and CI check before completion.
---

This is the Codex wrapper. The canonical workflow lives in
`.claude/skills/bmad-fr-nfr-review-gate/SKILL.md`.

Use this skill after implementation, not during planning. It requires a BMAD
spec bundle or spec file under `specs/`.

Quick command:

```bash
BMAD_REVIEW_SPEC_PATH=specs/my-bundle make bmad-fr-nfr-review-gate
```

Optional inputs:

- `BMAD_REVIEW_MANUAL_EVIDENCE=path/to/evidence.md`
- `BMAD_REVIEW_PR=<number>`
- `BMAD_REVIEW_BASE=<base-ref>`
- `BMAD_REVIEW_AGENTS=codex,claude`
- `BMAD_REVIEW_VERIFY_CMD='make ci'`
- `BMAD_REVIEW_IMPACT_CONTEXT=path/to/graph-or-impact-context.md`
- `BMAD_REVIEW_POST_PR_COMMENT=true|false`
- `BMAD_REVIEW_POST_GITHUB_STATUS=true|false`
- `BMAD_REVIEW_STATUS_CONTEXT='BMAD FR/NFR Review Gate'`
- `BMAD_REVIEW_STATUS_EXCLUDED_CONTEXT=<check-context>`; defaults to the final
  status context.

The gate uses the tracked AI review loop and BMAD-specific prompts. It fails
unless every applicable FR, NFR, pinned NonFunctionals.com category, expanded
quality dimension, whole-codebase impact surface, QA checkpoint, manual-test
requirement, GitHub completion gate, and CI gate has 5/5 evidence or an
explicit not-applicable reason with source evidence.
Graph/relationship evidence is mandatory for whole-codebase impact scoring.
Graphify, codebase-memory MCP, Deptrac graph output, CodeQL, SCIP, or similar
tools can be supplied through `BMAD_REVIEW_IMPACT_CONTEXT`; otherwise the
wrapper creates a bounded local graph/relationship impact context.
For BMAD wrapper runs, PR comment and GitHub commit-status publishing default
to on, so final results remain visible on the pull request.

Read and follow `.claude/skills/bmad-fr-nfr-review-gate/SKILL.md` before
claiming completion.
