---
name: sprint-planning
description: >
  Generate a sprint plan for development tasks. This kicks off the implementation phase by producing a sequence the implementation agents follow for each story. Use when the user asks about sprint planning.
metadata:
  managed-by: bmalph
---

This wrapper requires local BMALPH assets under `_bmad/`, which this repository intentionally keeps out of git.
If `_bmad/` is missing in a fresh clone or workspace, run `make bmalph-setup` first, or `bmalph upgrade --force` if BMALPH is already installed for this repo.

Adopt the role of the agent defined in `_bmad/bmm/agents/sm.agent.yaml`, then read and execute the workflow at `_bmad/bmm/workflows/4-implementation/bmad-sprint-planning/workflow.md` in Create mode.
