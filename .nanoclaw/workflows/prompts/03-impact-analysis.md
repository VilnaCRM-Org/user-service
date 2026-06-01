# Phase 03 — Impact Analysis

**Goal:** Map every part of the codebase, infrastructure, and process that this task could affect.

## Inputs

- `.ai/tasks/<slug>/task.md`
- `.ai/tasks/<slug>/spec.md`
- Repository context printed by `dev-cycle.sh impact <task-folder>` (Makefile, composer.json, Docker files, CI workflows, test directories, static analysis configs)

## Output

- `.ai/tasks/<slug>/impact-analysis.md`

## Required Sections

### 1. Impacted Modules/Files

List every file or directory likely to require changes. Group by layer (e.g., Domain, Application, Infrastructure, API, Tests).

### 2. Architecture Boundaries

Identify any hexagonal architecture or module boundaries crossed by this change. Flag boundary violations as risks.

### 3. Dependencies

List packages, services, or internal components that this change depends on or that depend on code being changed.

### 4. Database/Migration Risks

List any schema changes, new migrations, or data-transformations needed. Flag irreversible migrations explicitly.

### 5. Backward Compatibility Risks

Identify any public API contracts, event schemas, or service interfaces that might break. Note whether consumers exist.

### 6. Test Impact

List which existing test files cover the impacted code. Identify gaps where new tests are needed.

### 7. CI Impact

List which CI workflow jobs are affected (lint, unit tests, integration tests, static analysis). Estimate if any job will fail before any code changes.

### 8. Rollback Notes

Describe how to revert this change safely if it causes a production incident. Note any steps that are NOT reversible.

## Quality Gates

- [ ] All 8 sections are present
- [ ] Database/Migration Risks explicitly states "No DB changes" if none apply
- [ ] Backward Compatibility Risks explicitly states "No breaking changes" if none apply
- [ ] At least one test file is named in Test Impact
- [ ] No application code was modified

When done, update the phase checklist in `task.md`.
