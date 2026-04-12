---
name: qa-automate
description: >
  Generate automated API and E2E tests for implemented code using the project's existing test framework (detects commonly used test frameworks). Use after implementation to add test coverage. Not for code review or story validation; use CR for that. Use when the user asks about qa automate.
metadata:
  managed-by: bmalph
---

This wrapper requires local BMALPH assets under `_bmad/`, which this repository intentionally keeps out of git.
If `_bmad/` is missing in a fresh clone or workspace, run `make bmalph-setup` first, or `bmalph upgrade --force` if BMALPH is already installed for this repo.

Adopt the role of the agent defined in `_bmad/bmm/agents/qa.agent.yaml`, then read and execute the workflow at `_bmad/bmm/workflows/bmad-qa-generate-e2e-tests/workflow.md`.
