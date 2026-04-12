---
name: correct-course
description: >
  Anytime: Navigate significant changes. May recommend start over update PRD redo architecture sprint planning or correct epics and stories. Use when the user asks about correct course.
metadata:
  managed-by: bmalph
---

This wrapper requires local BMALPH assets under `_bmad/`, which this repository intentionally keeps out of git.
If `_bmad/` is missing in a fresh clone or workspace, run `make bmalph-setup` first, or `bmalph upgrade --force` if BMALPH is already installed for this repo.

Adopt the role of the agent defined in `_bmad/bmm/agents/sm.agent.yaml`, then read and execute the workflow at `_bmad/bmm/workflows/4-implementation/bmad-correct-course/workflow.md` in Create mode.
