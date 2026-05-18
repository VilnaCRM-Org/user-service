---
workflowType: epics
project_name: BMAD FR/NFR Review Gate
author: Codex
date: 2026-05-17
revision: 1
---

# Epics: BMAD FR/NFR Review Gate

## Epic 1: Gate Wrapper and Prompt Contract

- Add a Make target and wrapper for BMAD spec-driven review.
- Add review and fix prompts with the strict 5/5 scoring contract.
- Require pinned NFR catalog categories in every review.

## Epic 2: AI Loop Gate Controls

- Add prompt placeholders for spec path, manual evidence, PR number, threshold,
  and NFR categories.
- Add required PASS marker enforcement.
- Force BMAD mode to use the pinned 5/5 threshold, NFR categories, and marker
  list even when generic review-loop environment variables are present.
- Require exact first-line status parsing in BMAD mode.
- Add optional verification after PASS.
- Preserve existing generic review behavior.

## Epic 3: Skills and Documentation

- Add canonical Claude skill instructions.
- Add Codex wrapper skill instructions.
- Update AI agent guides, onboarding, README, and local guidance.

## Epic 4: Verification

- Add Bats coverage for required spec input.
- Add Bats coverage for placeholder substitution.
- Add Bats coverage for missing PASS markers.
- Add Bats coverage for wrapper/Make execution, env downgrade attempts,
  first-line status enforcement, and verification failure after PASS.
- Run shell syntax checks, Bats tests, and diff checks.

## Coverage Map

| Requirement                | Epic           |
| -------------------------- | -------------- |
| FR-01, FR-02               | Epic 1         |
| FR-03, FR-04, FR-05, FR-06 | Epic 1         |
| FR-07, FR-08, FR-09, FR-10 | Epic 2         |
| FR-11                      | Epic 3         |
| NFR-01, NFR-04             | Epic 1         |
| NFR-02, NFR-03, NFR-05     | Epic 2         |
| NFR-06, NFR-08, NFR-09     | Epic 2         |
| NFR-07                     | Epic 3         |
| AC-01 through AC-10        | Epic 4         |
| AC-11                      | Epic 2, Epic 4 |
