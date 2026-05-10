---
name: bmad-autonomous-planning
description: >
  Portable autonomous BMALPH planning for Codex. Use when the user wants specs
  from a short task description and expects the current AI session to
  orchestrate BMALPH subagents without human interaction.
---

Use this skill from the current Codex session. Do not rely on repo-local bash
wrappers, `make` targets, or other launcher automation for the planning flow.

The canonical planning contract lives in
`.claude/skills/bmad-autonomous-planning/SKILL.md`. This wrapper is only the
Codex-specific handoff.

This wrapper requires local BMALPH assets under `_bmad/`, which this repository intentionally keeps out of git.
If `_bmad/` is missing in a fresh clone or workspace, run `make bmalph-setup` first.
If BMALPH is already installed and you need to restore local files or reapply this repository's planning artifacts under `specs/`, rerun `make bmalph-setup`.

After the local BMALPH assets are available:

1. Read the local `bmalph` skill wrapper, `_bmad/COMMANDS.md`, and the resolved
   BMAD config file first.
2. Read `.claude/skills/bmad-autonomous-planning/SKILL.md`.
3. Resolve or create a bundle directory under the configured planning artifacts
   folder, typically `autonomous/<timestamp>-<task-slug>/`.
4. Run each BMALPH planning stage in a dedicated subagent, using the BMALPH
   command surface as the handoff contract:
   - research and repository context: `analyst`
   - product brief: `create-brief`
   - PRD: `create-prd`
   - architecture: `create-architecture`
   - epics and stories: `create-epics-stories`
   - implementation readiness: `implementation-readiness`
5. After each subagent returns, the main agent must decide the next step,
   answer workflow questions on the user's behalf, and only then hand the next
   stage to another subagent.
6. Validate and improve each artifact for up to three rounds without blocking on
   BMALPH approval menus. When another subagent pass is needed, prefer the
   matching BMALPH validation command from `_bmad/COMMANDS.md` when available.
7. For every spawned planning or validation subagent, set `model: gpt-5.4` and
   `reasoning_effort: xhigh`. Do not use `gpt-5.4-mini` for stage ownership in
   this workflow.

For each subagent, name the BMALPH command first and provide only the backing
workflow or agent files required by that command. Do not hand off raw
workflow-file paths without the command context.

Minimal Codex trigger example:

`Use the bmad-autonomous-planning skill to plan a new user-profile enrichment feature for the user service. Follow the repository's BMALPH autonomous planning skill, work in the current session, keep the flow fully autonomous, and run each planning subagent on gpt-5.4 with xhigh reasoning.`

The finished bundle should include:

- `research.md`
- `product-brief.md`
- `product-brief-distillate.md` when useful
- `prd.md`
- `architecture.md`
- `epics.md`
- `implementation-readiness.md`
- `run-summary.md`

Optional GitHub issue or specs-only PR work happens only after the planning
bundle is complete and only when the user explicitly asks for it.
