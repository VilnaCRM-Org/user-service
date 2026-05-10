---
name: create-ux
description: >
  Guidance through realizing the plan for your UX, strongly recommended if a UI is a primary piece of the proposed project. Use when the user asks about create ux.
metadata:
  managed-by: bmalph
---

This wrapper requires local BMALPH assets under `_bmad/`, which this repository intentionally keeps out of git.
If `_bmad/` is missing in a fresh clone or workspace, run `make bmalph-setup` first.
If BMALPH is already installed and you need to restore local files or reapply this repository's planning artifacts under `specs/`, rerun `make bmalph-setup`.

Adopt the role of the agent defined in `_bmad/bmm/agents/ux-designer.agent.yaml`, then read and execute the workflow at `_bmad/bmm/workflows/2-plan-workflows/bmad-create-ux-design/workflow.md` in Create mode.
