---
name: implementation-readiness
description: >
  Ensure PRD UX Architecture and Epics Stories are aligned. Use when the user asks about implementation readiness.
metadata:
  managed-by: bmalph
---

This wrapper requires local BMALPH assets under `_bmad/`, which this repository intentionally keeps out of git.
If `_bmad/` is missing in a fresh clone or workspace, run `make bmalph-setup` first, or `bmalph upgrade --force` if BMALPH is already installed for this repo.

Adopt the role of the agent defined in `_bmad/bmm/agents/architect.agent.yaml`, then read and execute the workflow at `_bmad/bmm/workflows/3-solutioning/bmad-check-implementation-readiness/workflow.md` in Validate mode.
