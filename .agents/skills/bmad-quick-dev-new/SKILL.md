---
name: quick-dev-new
description: >
  Unified quick flow (experimental): clarify intent plan implement review and present in a single workflow. Use when the user asks about quick dev new.
metadata:
  managed-by: bmalph
---

This wrapper requires local BMALPH assets under `_bmad/`, which this repository intentionally keeps out of git.
If `_bmad/` is missing in a fresh clone or workspace, run `make bmalph-setup` first.
If BMALPH is already installed and you need to restore local files or reapply this repository's planning artifacts under `specs/`, rerun `make bmalph-setup`.

Adopt the role of the agent defined in `_bmad/bmm/agents/quick-flow-solo-dev.agent.yaml`, then read and execute the workflow at `_bmad/bmm/workflows/bmad-quick-flow/bmad-quick-dev-new-preview/workflow.md` in Create mode.
