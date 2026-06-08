---
name: edge-case-hunter
description: >
  Identify edge cases, failure paths, boundary-condition risks, and missing FR/NFR negative or edge test coverage. Use when the user asks for edge-case analysis or strict QA review.
metadata:
  managed-by: bmalph
---

This wrapper requires local BMALPH assets under `_bmad/`, which this repository intentionally keeps out of git.
If `_bmad/` is missing in a fresh clone or workspace, run `make bmalph-setup` first.
If BMALPH is already installed and you need to restore local files or reapply this repository's planning artifacts under `specs/`, rerun `make bmalph-setup`.

Read and execute the workflow/task at `_bmad/core/skills/bmad-review-edge-case-hunter/workflow.md`.

Mandatory repository overlay:

- If specs/docs are available, derive FR/NFR edge and negative cases before reviewing code paths.
- Report missing automated coverage for discovered edge cases as a finding.
- Include flaky-test risks when changed tests attempt to cover edge cases with timing, order, shared state, or weak assertions.
