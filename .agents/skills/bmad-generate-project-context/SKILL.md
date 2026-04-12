---
name: generate-project-context
description: >
  Scan the existing codebase to generate a lean, LLM-optimized `project-context.md` with critical implementation rules, patterns, and conventions for AI agents. Use this for brownfield projects and when the user asks about generate project context.
metadata:
  managed-by: bmalph
---

This wrapper requires local BMALPH assets under `_bmad/`, which this repository intentionally keeps out of git.
If `_bmad/` is missing in a fresh clone or workspace, run `make bmalph-setup` first, or `bmalph upgrade --force` if BMALPH is already installed for this repo.

Adopt the role of the agent defined in `_bmad/bmm/agents/analyst.agent.yaml`, then read and execute the workflow at `_bmad/bmm/workflows/bmad-generate-project-context/workflow.md`.
