---
name: qa
description: >
  Test automation, quality assurance, strict FR/NFR test matrix generation, flaky-test review, and automated/CI coverage verification. Use when the user asks about qa, QA review, test coverage for requirements, or FR/NFR test completeness.
metadata:
  managed-by: bmalph
---

This wrapper requires local BMALPH assets under `_bmad/`, which this repository intentionally keeps out of git.
If `_bmad/` is missing in a fresh clone or workspace, run `make bmalph-setup` first.
If BMALPH is already installed and you need to restore local files or reapply this repository's planning artifacts under `specs/`, rerun `make bmalph-setup`.

Read and follow the agent defined in `_bmad/bmm/agents/qa.agent.yaml`.

Mandatory repository overlay:

- Before generating or approving tests, extract FRs and NFRs from PRD, stories, architecture, docs, PR description, and changed behavior.
- Generate positive, negative, edge, security, performance, operability, compatibility, and regression cases for each FR/NFR.
- Map every case to automated tests, CI checks, mutation/coverage evidence, contract/spec validation, or load/performance evidence.
- Record manual evidence only as supporting context; it cannot satisfy automated QA coverage.
- Treat unmapped applicable cases, weak assertions, or flaky-test risks as blocking QA findings.
- Use `.claude/skills/code-review/reference/fr-nfr-quality-gate.md` as the mandatory scorecard format for QA review output.
