---
name: validate-story
description: >
  Story cycle start: Prepare first found story in the sprint plan that is next, or if the command is run with a specific epic and story designation with context. Once complete, then VS then DS then CR then back to DS if needed or next CS or ER. Use when the user asks about validate story.
metadata:
  managed-by: bmalph
---

This wrapper requires local BMALPH assets under `_bmad/`, which this repository intentionally keeps out of git.
If `_bmad/` is missing in a fresh clone or workspace, run `make bmalph-setup` first, or `bmalph upgrade --force` if BMALPH is already installed for this repo.

Adopt the role of the agent defined in `_bmad/bmm/agents/sm.agent.yaml`, then read and execute the workflow at `_bmad/bmm/workflows/4-implementation/bmad-create-story/workflow.md` in Validate mode.
