# Fix Infection Failures Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Bring `make infection` to 100% MSI with zero escaped mutants in the User Service.

**Architecture:** Follow existing Hexagonal/DDD boundaries (Domain stays framework-free, validation in Application). Run all PHP tooling via `make` inside containers; do not execute PHP directly on the host.

**Tech Stack:** PHP 8.3, Symfony 7.2, API Platform 4.1, PHPUnit, Infection, Docker/Make.

### Task 1: Reproduce Mutation Failures

**Files:**
- Review logs only (no code changes yet).

**Step 1: Run infection**

```bash
make infection
```

**Step 2: Capture escaped mutants**

- Copy the summary and per-mutant file paths/line numbers from the output into notes for the next task.

**Step 3: Identify target files/tests**

- Map each escaped mutant to its source class (e.g., `src/<Context>/...`) and the corresponding test suite file under `tests/Unit/...` or `tests/Integration/...` that should cover the behavior.

### Task 2: Strengthen Tests for Each Escaped Mutant

**Files:**
- Modify: `tests/Unit/<context>/<target_test>.php` (or closest existing test file identified in Task 1).
- If missing, create: `tests/Unit/<context>/<new_test>.php`.

**Step 1: Reproduce the mutation scenario**

- Read the mutation diff in the infection output for the target file/line.
- Understand the behavioral change (e.g., `>` → `>=`, removed method call, negated condition).

**Step 2: Add/strengthen assertions**

- Extend the relevant test to cover the boundary condition the mutant exploits.
- Use Faker data (no hardcoded emails/passwords/UUIDs); prefer data providers for edge cases.

**Step 3: Run focused tests**

```bash
make unit-tests
```

- Ensure the new/updated tests fail if the mutant were applied (conceptually) and pass with current code.

### Task 3: Validate Full Mutation Suite

**Files:**
- None beyond those updated in Task 2.

**Step 1: Re-run infection**

```bash
make infection
```

- Expect 0 escaped mutants and MSI at target level.

**Step 2: Final checks**

- If any mutants remain, loop back to Task 2 for those specific files.
- Once clean, note the green output for the final report.

**Step 3: Optional broader verification**

```bash
make all-tests
```

- Confirms no regressions introduced by the strengthened tests.
