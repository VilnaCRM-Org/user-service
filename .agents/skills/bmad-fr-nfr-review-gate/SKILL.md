---
name: bmad-fr-nfr-review-gate
description: >
  Codex entrypoint for post-implementation BMAD FR/NFR review gates. Use after
  a PR, feature, or bugfix has BMAD specs and must be checked against every
  functional requirement, non-functional requirement, manual-test expectation,
  GitHub review comment, approval, and CI check before completion.
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

The gate uses the tracked AI review loop and BMAD-specific prompts. It fails
unless every applicable FR, NFR, pinned NonFunctionals.com category, QA
checkpoint, manual-test requirement, GitHub completion gate, and CI gate has
5/5 evidence or an explicit not-applicable reason with source evidence.

Read and follow `.claude/skills/bmad-fr-nfr-review-gate/SKILL.md` before
claiming completion.
