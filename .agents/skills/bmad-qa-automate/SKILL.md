---
name: qa-automate
description: >
  Generate automated API and E2E tests for implemented code using the project's existing test framework, with mandatory FR/NFR positive/negative/edge coverage mapping and flaky-test review. Use after implementation to add test coverage, close requirement coverage gaps, or verify QA automation completeness. Not for code review or story validation; use CR for that. Use when the user asks about qa automate.
metadata:
  managed-by: bmalph
---

This wrapper requires local BMALPH assets under `_bmad/`, which this repository intentionally keeps out of git.
If `_bmad/` is missing in a fresh clone or workspace, run `make bmalph-setup` first.
If BMALPH is already installed and you need to restore local files or reapply this repository's planning artifacts under `specs/`, rerun `make bmalph-setup`.

Adopt the role of the agent defined in `_bmad/bmm/agents/qa.agent.yaml`, then read and execute the workflow at `_bmad/bmm/workflows/bmad-qa-generate-e2e-tests/workflow.md`.

Mandatory repository overlay:

- Build an FR/NFR test matrix before writing tests.
- Include positive, negative, edge, security, performance, operability, compatibility, and regression cases where applicable.
- Require automated tests and CI-backed checks for applicable FR/NFR cases. Manual evidence may be recorded only as supporting context and cannot close an automation gap.
- Inspect every generated or changed test for flakiness: timing, shared state, order dependency, non-unique data, external network, timezone/locale, and weak assertions.
- Save the matrix and missing-coverage findings in the QA summary.
