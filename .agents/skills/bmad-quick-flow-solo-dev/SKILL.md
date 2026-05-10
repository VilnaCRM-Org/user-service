---
name: quick-flow-solo-dev
description: >
  Quick one-off tasks, small changes. Use when the user asks about quick flow solo dev.
metadata:
  managed-by: bmalph
---

This wrapper requires local BMALPH assets under `_bmad/`, which this repository intentionally keeps out of git.
If `_bmad/` is missing in a fresh clone or workspace, run `make bmalph-setup` first.
If BMALPH is already installed and you need to restore local files or reapply this repository's planning artifacts under `specs/`, rerun `make bmalph-setup`.

Read and follow the agent defined in `_bmad/bmm/agents/quick-flow-solo-dev.agent.yaml`, then select the matching quick-flow workflow under `_bmad/bmm/workflows/bmad-quick-flow/` for the task at hand (for example `_bmad/bmm/workflows/bmad-quick-flow/bmad-quick-dev/workflow.md`).
