# Phase 04 — Implementation Plan

**Goal:** Produce a step-by-step implementation plan precise enough that following it mechanically would produce a correct result.

## Inputs

- `.ai/tasks/<slug>/task.md`
- `.ai/tasks/<slug>/spec.md`
- `.ai/tasks/<slug>/impact-analysis.md`

## Output

- `.ai/tasks/<slug>/plan.md`

## Required Sections

### 1. Step-by-step Implementation Steps
Numbered list. Each step must be a concrete action (e.g., "Create class `UserEmailVerifier` in `src/Domain/User/`"). Group steps by logical phase (e.g., "Domain Layer", "Application Layer", "Tests").

### 2. Proposed Files to Change
Table or list: file path → type of change (create / modify / delete) → one-line reason.

### 3. Proposed Tests to Add/Update
Table or list: test file path → test class/method → what behavior it verifies.

### 4. Validation Commands
Ordered list of commands to run locally to verify correctness. Must match what `detect_validation_commands` would find (e.g., `make ci`, `make phpstan`, `make cs-check`).

### 5. Expected Risks
Itemized list of risks with mitigations. Reference the impact analysis.

### 6. Rollback Plan
Step-by-step: how to revert if something goes wrong after merge.

### 7. Definition of Done
Numbered checklist. Each item is verifiable. The task is not done until all items are checked.

## Quality Gates

- [ ] Implementation steps are numbered and concrete (no vague "implement X")
- [ ] Every file in Proposed Files to Change has a stated reason
- [ ] At least one validation command is listed
- [ ] Definition of Done references acceptance criteria from spec.md
- [ ] Rollback Plan is present (even if it is just "revert the commit")
- [ ] No application code was modified

When done, update the phase checklist in `task.md`.
