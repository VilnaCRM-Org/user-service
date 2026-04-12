---
name: architect
description: >
  Technical design, architecture. Use when the user asks about architect.
metadata:
  managed-by: bmalph
---

This wrapper requires local BMALPH assets under `_bmad/`, which this repository intentionally keeps out of git.
If `_bmad/` is missing in a fresh clone or workspace, run `make bmalph-setup` first, or `bmalph upgrade --force` if BMALPH is already installed for this repo.

Read and follow the agent defined in `_bmad/bmm/agents/architect.agent.yaml`.
