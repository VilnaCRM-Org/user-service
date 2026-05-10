---
name: create-epics-stories
description: >
  Create the Epics and Stories Listing. Use when the user asks about create epics stories.
metadata:
  managed-by: bmalph
---

This wrapper requires local BMALPH assets under `_bmad/`, which this repository intentionally keeps out of git.
If `_bmad/` is missing in a fresh clone or workspace, run `make bmalph-setup` first.
If BMALPH is already installed and you need to restore local files or reapply this repository's planning artifacts under `specs/`, rerun `make bmalph-setup`.

Adopt the role of the agent defined in `_bmad/bmm/agents/pm.agent.yaml`, then read and execute the workflow at `_bmad/bmm/workflows/3-solutioning/bmad-create-epics-and-stories/workflow.md` in Create mode.
