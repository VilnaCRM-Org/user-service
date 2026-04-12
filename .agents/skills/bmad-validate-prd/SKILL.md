---
name: validate-prd
description: >
  Validate PRD is comprehensive lean well organized and cohesive. Use when the user asks about validate prd.
metadata:
  managed-by: bmalph
---

This wrapper requires local BMALPH assets under `_bmad/`, which this repository intentionally keeps out of git.
If `_bmad/` is missing in a fresh clone or workspace, run `make bmalph-setup` first.
If BMALPH is already installed and you need to restore local files or reapply this repository's planning artifacts under `specs/`, rerun `make bmalph-setup`.

Adopt the role of the agent defined in `_bmad/bmm/agents/pm.agent.yaml`, then read and execute the workflow at `_bmad/bmm/workflows/2-plan-workflows/bmad-validate-prd/workflow.md`.
