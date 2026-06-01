---
name: bmad-fr-nfr-review-gate
description: >
  Codex entrypoint for post-implementation BMAD FR/NFR review gates. Use after
  a PR, feature, or bugfix has BMAD specs and must be checked against every
  functional requirement, non-functional requirement, expanded quality
  dimension, Wikipedia system quality attribute, generated positive/negative/
  edge test case, automated test and CI coverage expectation, flaky-test risk,
  whole-codebase impact surface, manual-test expectation, GitHub review
  comments and requested-changes state, and CI check before completion.
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
- `BMAD_REVIEW_POST_PR_COMMENT=true|false`; required and default-on for PR
  runs, disable only for local-only dry runs or test harnesses.
- `BMAD_REVIEW_POST_GITHUB_STATUS=true|false`; required and default-on for PR
  runs, disable only for local-only dry runs or test harnesses.
- `BMAD_REVIEW_STATUS_CONTEXT='BMAD FR/NFR Review Gate'`
- `BMAD_REVIEW_STATUS_EXCLUDED_CONTEXT=<check-context>`; defaults to the final
  status context.

The gate uses the tracked AI review loop and BMAD-specific prompts. It fails
unless every applicable FR, NFR, pinned NonFunctionals.com category, expanded
quality dimension, Wikipedia system quality attribute, generated positive/
negative/edge test case, automated test and CI coverage row, flaky-test risk
row, whole-codebase impact surface, QA checkpoint, manual-test requirement,
GitHub completion gate, and CI gate has 5/5 evidence or an explicit
not-applicable reason with source evidence.
It fails if repeatable FR/NFR behavior lacks automated tests that run in CI, if
negative/edge/security/data-loss/concurrency cases are missing, or if changed
or impacted tests have unmitigated flaky-test risk. It also fails if the review
does not search for vulnerabilities, bugs, regressions, defects, operational
problems, and data-loss/privacy/security risks.
Graph/relationship evidence is mandatory for whole-codebase impact scoring.
Graphify, codebase-memory MCP, Deptrac graph output, CodeQL, SCIP, or similar
tools can be supplied through `BMAD_REVIEW_IMPACT_CONTEXT`; otherwise the
wrapper creates a bounded local graph/relationship impact context.
Every NFR catalog row, expanded quality row, and system quality attribute row
must cite graph/relationship evidence, or give a concrete source-backed reason
why graph evidence is irrelevant for that row. Automated evidence must cover
applicable unit, integration, E2E, Behat, PHPUnit, Schemathesis, K6/load,
mutation, static-analysis, security-scan, and contract/schema checks.
For BMAD wrapper PR runs, PR comment and GitHub commit-status publishing are
required low-risk review-gate writes that run without waiting for human
approval, so in-progress and final results remain visible on the pull request.

Read and follow `.claude/skills/bmad-fr-nfr-review-gate/SKILL.md` before
claiming completion.
