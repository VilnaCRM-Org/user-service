# Phase 02 — Specification

**Goal:** Produce a precise, unambiguous specification for the task before any code is written.

## Inputs

- `.ai/tasks/<slug>/task.md` — task description and context

## Output

- `.ai/tasks/<slug>/spec.md`

## Required Sections

Produce all eight sections. Write each concisely but completely. Do not leave a section empty.

### 1. Problem Statement

One paragraph. What is broken, missing, or needs to change, and why does it matter?

### 2. Expected Behavior

Describe the system's behavior after the change. Use concrete examples where possible (inputs → outputs, before → after).

### 3. Non-Goals

List what is explicitly out of scope. This prevents scope creep during implementation.

### 4. Assumptions

List the assumptions you are making about the environment, data, or existing behavior. Flag any that need verification.

### 5. Edge Cases

List specific edge cases that the implementation must handle correctly. For each, state the expected behavior.

### 6. Acceptance Criteria

A numbered list of verifiable conditions that must all be true for the task to be considered done. Each criterion must be testable.

### 7. Test Strategy

Describe what types of tests will cover this change (unit, integration, e2e, contract). Name specific test files or classes if they already exist or should be created.

### 8. Validation Strategy

Describe how you will verify the implementation locally before opening a PR. Include which `make` targets or `composer` scripts will be run and what passing looks like.

## Quality Gates

- [ ] All 8 sections are present and non-empty
- [ ] Acceptance criteria are numbered and each is independently verifiable
- [ ] Non-goals are listed (even if empty, write "None identified")
- [ ] Edge cases include at least one boundary condition
- [ ] No application code was modified

When done, update the phase checklist in `task.md`.
