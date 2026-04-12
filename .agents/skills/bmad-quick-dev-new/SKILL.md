---
name: quick-dev-new
description: >
  Unified quick flow (experimental): clarify intent plan implement review and present in a single workflow. Use when the user asks about quick dev new.
metadata:
  managed-by: bmalph
---

This wrapper requires local BMALPH assets under `_bmad/`, which this repository intentionally keeps out of git.
If `_bmad/` is missing in a fresh clone or workspace, run `make bmalph-setup` first, or `bmalph upgrade --force` if BMALPH is already installed for this repo.

Adopt the role of the agent defined in `_bmad/bmm/agents/quick-flow-solo-dev.agent.yaml`, then read and execute the workflow at `_bmad/bmm/workflows/bmad-quick-flow/bmad-quick-dev-new-preview/workflow.md` in Create mode.
