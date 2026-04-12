---
name: pm
description: >
  PRDs, epics, stories. Use when the user asks about pm.
metadata:
  managed-by: bmalph
---

This wrapper requires local BMALPH assets under `_bmad/`, which this repository intentionally keeps out of git.
If `_bmad/` is missing in a fresh clone or workspace, run `make bmalph-setup` first.
If BMALPH is already installed and you need to restore local files or reapply this repository's planning artifacts under `specs/`, rerun `make bmalph-setup`.

Read and follow the agent defined in `_bmad/bmm/agents/pm.agent.yaml`.
