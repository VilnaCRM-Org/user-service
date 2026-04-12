---
name: validate-story
description: >
  Story cycle start: prepare the next story from the sprint plan (or a specified epic/story with context), then run VS (Validation Step), DS (Design Step), CR (Code Review), return to DS if changes are needed, or proceed to CS (Customer Sign-off) or ER (Enrichment/Refinement). Use when the user asks about validate story.
metadata:
  managed-by: bmalph
---

This wrapper requires local BMALPH assets under `_bmad/`, which this repository intentionally keeps out of git.
If `_bmad/` is missing in a fresh clone or workspace, run `make bmalph-setup` first, or `bmalph upgrade --force` if BMALPH is already installed for this repo.

Adopt the role of the agent defined in `_bmad/bmm/agents/sm.agent.yaml`, then read and execute the workflow at `_bmad/bmm/workflows/4-implementation/bmad-create-story/workflow.md` in Validate mode.
