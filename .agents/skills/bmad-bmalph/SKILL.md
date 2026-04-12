---
name: bmalph
description: >
  BMAD master agent — navigate phases. Use when the user asks about bmalph.
metadata:
  managed-by: bmalph
---

This wrapper requires local BMALPH assets under `_bmad/`, which this repository intentionally keeps out of git.
If `_bmad/` is missing in a fresh clone or workspace, run `make bmalph-setup` first.
If BMALPH is already installed and you need to restore local files or reapply this repository's planning artifacts under `specs/`, rerun `make bmalph-setup`.
First consult `.claude/skills/AI-AGENT-GUIDE.md` and `.claude/skills/SKILL-DECISION-GUIDE.md` to determine the correct approach before proceeding.

Use `_bmad/COMMANDS.md` as the master BMALPH command catalog. Name and route work
through the command listed there first, then load only the backing agent or
workflow files required by that command.

Use the help workflow at `_bmad/core/skills/bmad-help/workflow.md` when the user
is asking how to navigate BMAD, what to do next, or which command to use.

When another skill delegates BMALPH work to subagents, hand off the concrete
command name from `_bmad/COMMANDS.md` as the primary instruction surface rather
than sending only raw workflow-file paths.
