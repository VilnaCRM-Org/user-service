---
name: create-brief
description: >
  A guided experience to nail down your product idea. Use when the user asks about create brief.
metadata:
  managed-by: bmalph
---

This wrapper requires local BMALPH assets under `_bmad/`, which this repository intentionally keeps out of git.
If `_bmad/` is missing in a fresh clone or workspace, run `make bmalph-setup` first, or `bmalph upgrade --force` if BMALPH is already installed for this repo.

Adopt the role of the agent defined in `_bmad/bmm/agents/analyst.agent.yaml`, then read and execute the workflow at `_bmad/bmm/workflows/1-analysis/bmad-create-product-brief/workflow.md` in Create mode.
