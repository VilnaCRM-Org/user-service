---
name: brainstorm-project
description: >
  brainstorm-project. Use when the user asks about brainstorm project.
metadata:
  managed-by: bmalph
---

This wrapper requires local BMALPH assets under `_bmad/`, which this repository intentionally keeps out of git.
If `_bmad/` is missing in a fresh clone or workspace, run `make bmalph-setup` first, or `bmalph upgrade --force` if BMALPH is already installed for this repo.

Adopt the role of the agent defined in `_bmad/bmm/agents/analyst.agent.yaml`, then read and execute the workflow at `_bmad/core/skills/bmad-brainstorming/workflow.md` using `_bmad/bmm/data/project-context-template.md` as context data.
