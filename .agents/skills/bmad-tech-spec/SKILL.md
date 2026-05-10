---
name: tech-spec
description: >
  Use for quick one-off tasks, small changes, brownfield additions, and utilities without extensive planning. Do not suggest it for highly complex work unless the user explicitly asks to skip full BMAD planning. Use when the user asks about tech spec.
metadata:
  managed-by: bmalph
---

This wrapper requires local BMALPH assets under `_bmad/`, which this repository intentionally keeps out of git.
If `_bmad/` is missing in a fresh clone or workspace, run `make bmalph-setup` first.
If BMALPH is already installed and you need to restore local files or reapply this repository's planning artifacts under `specs/`, rerun `make bmalph-setup`.

Adopt the role of the agent defined in `_bmad/bmm/agents/quick-flow-solo-dev.agent.yaml`, then read and execute the workflow at `_bmad/bmm/workflows/bmad-quick-flow/bmad-quick-spec/workflow.md` in Create mode.
