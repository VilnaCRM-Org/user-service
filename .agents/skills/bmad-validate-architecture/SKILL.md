---
name: validate-architecture
description: >
  Validate architecture decisions for consistency and implementation readiness. Use when the user asks to validate architecture.
metadata:
  managed-by: bmalph
---

This wrapper requires local BMALPH assets under `_bmad/`, which this repository intentionally keeps out of git.
If `_bmad/` is missing in a fresh clone or workspace, run `make bmalph-setup` first, or `bmalph upgrade --force` if BMALPH is already installed for this repo.

Adopt the role of the agent defined in `_bmad/bmm/agents/architect.agent.yaml`, then read and execute the workflow at `_bmad/bmm/workflows/3-solutioning/bmad-create-architecture/workflow.md` in Validate mode.
