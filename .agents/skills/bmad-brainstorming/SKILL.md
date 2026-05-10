---
name: brainstorming
description: >
  brainstorming. Use when the user asks about brainstorming.
metadata:
  managed-by: bmalph
---

This wrapper requires local BMALPH assets under `_bmad/`, which this repository intentionally keeps out of git.
If `_bmad/` is missing in a fresh clone or workspace, run `make bmalph-setup` first.
If BMALPH is already installed and you need to restore local files or reapply this repository's planning artifacts under `specs/`, rerun `make bmalph-setup`.

Read and execute the workflow/task at `_bmad/core/skills/bmad-brainstorming/workflow.md`.
