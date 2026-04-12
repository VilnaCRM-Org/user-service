---
name: shard-doc
description: >
  shard doc. Use when the user asks about shard doc.
metadata:
  managed-by: bmalph
---

This wrapper requires local BMALPH assets under `_bmad/`, which this repository intentionally keeps out of git.
If `_bmad/` is missing in a fresh clone or workspace, run `make bmalph-setup` first, or `bmalph upgrade --force` if BMALPH is already installed for this repo.

Read and execute the workflow/task at `_bmad/core/skills/bmad-shard-doc/workflow.md`.
