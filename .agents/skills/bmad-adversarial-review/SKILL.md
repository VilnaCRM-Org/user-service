---
name: adversarial-review
description: >
  adversarial review. Use when the user asks about adversarial review.
metadata:
  managed-by: bmalph
---

This wrapper requires local BMALPH assets under `_bmad/`, which this repository intentionally keeps out of git.
If `_bmad/` is missing in a fresh clone or workspace, run `make bmalph-setup` first.
If BMALPH is already installed and you need to restore local files or reapply this repository's planning artifacts under `specs/`, rerun `make bmalph-setup`.

Read and execute the workflow/task at `_bmad/core/skills/bmad-review-adversarial-general/workflow.md`.

Mandatory repository overlay:

- Load `.claude/skills/code-review/reference/fr-nfr-quality-gate.md` before delegating to `_bmad/`.
- Extract all FRs/NFRs from specs, docs, PR description, changed behavior, tests, CI workflows, and graph/code-search impact.
- Generate positive, negative, edge, abuse, security, performance, operability, compatibility, and regression cases.
- Treat missing automated/CI-backed evidence, manual-only coverage, flaky-test risk, stale SHA evidence, or missing expected checks as blocking adversarial findings.
- Post/update the GitHub `BMAD FR/NFR Review Gate` status when reviewing a PR. Do not wait for human/codeowner approvals; report them separately from the automated BMAD gate.
