---
name: validate-prd
description: >
  Validate PRD is comprehensive lean well organized and cohesive. Use when the user asks about validate prd.
metadata:
  managed-by: bmalph
---

This wrapper requires local BMALPH assets under `_bmad/`, which this repository intentionally keeps out of git.
If `_bmad/` is missing in a fresh clone or workspace, run `make bmalph-setup` first, or `bmalph upgrade --force` if BMALPH is already installed for this repo.

Adopt the role of the agent defined in `_bmad/bmm/agents/pm.agent.yaml`, then read and execute the workflow at `_bmad/bmm/workflows/2-plan-workflows/bmad-validate-prd/workflow.md`.
