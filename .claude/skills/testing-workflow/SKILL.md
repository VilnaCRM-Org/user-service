---
name: testing-workflow
description: Run and manage functional tests (unit, integration, E2E, mutation) and verify FR/NFR test completeness. Use when running tests, debugging test failures, ensuring test coverage, fixing mutation testing issues, checking flaky-test risk, or mapping requirements to positive/negative/edge automated coverage. Covers PHPUnit, Behat, and Infection. For K6 load/performance tests, use the load-testing skill instead.
---

# Testing Workflow Skill

## Context (Input)

- Code changes require test validation
- Test failures need debugging
- Coverage/mutation targets must be met
- FR/NFR positive, negative, edge, security, performance, and operability cases must be covered

## Task (Function)

Execute appropriate test suite and ensure 100% pass rate with required coverage,
mutation strength, deterministic tests, and explicit FR/NFR case coverage.

**Note**: For K6 load/performance testing, see [load-testing skill](../load-testing/SKILL.md).

## Test Commands Quick Reference

| Test Type   | Command                  | Runtime  | Coverage | Location           |
| ----------- | ------------------------ | -------- | -------- | ------------------ |
| Unit        | `make unit-tests`        | 2-3 min  | 100%     | tests/Unit/        |
| Integration | `make integration-tests` | 3-5 min  | Full     | tests/Integration/ |
| E2E (Behat) | `make behat`             | 5-10 min | BDD      | features/          |
| All Tests   | `make all-tests`         | 8-15 min | 100%     | All                |
| Mutation    | `make infection`         | Variable | 100% MSI | Unit tests         |

**Load Testing**: Use [load-testing skill](../load-testing/SKILL.md) for K6 performance tests.

## Execution Workflow

### Step 0: Build Requirement Test Matrix

Before adding or accepting tests for a PR:

1. Extract FRs/NFRs from PRD, stories, architecture, docs, PR description, and changed behavior.
2. Generate positive, negative, and edge cases for each requirement.
3. Add NFR-specific cases for security, availability, performance, scalability, interoperability, observability, operability, maintainability, and compatibility.
4. Map each case to an automated test, CI check, mutation/coverage evidence, contract/spec validation, or load/performance run.
5. Record manual evidence only as supporting context. It does not satisfy automated coverage.
6. Treat unmapped applicable cases as missing coverage. Implement tests or mark the review blocked.

### Step 1: Run Tests

```bash
make unit-tests           # For quick validation
make all-tests            # For comprehensive check
```

### Step 2: Check Results

- ✅ **All Pass + 100% coverage** → Complete
- ⚠️ **All Pass but matrix gaps** → Add missing cases before completion
- ❌ **Failures detected** → Go to Step 3

### Step 3: Debug Failures

Identify failure type and apply fix:

| Failure Type      | Debug Command           | Common Fixes                              |
| ----------------- | ----------------------- | ----------------------------------------- |
| Assertion failure | PHPUnit output          | Fix logic, update test expectations       |
| Coverage < 100%   | Coverage report         | Add missing test cases                    |
| Escaped mutants   | `make infection` output | Test edge cases, strengthen assertions    |
| Behat scenario    | Feature output          | Fix application logic or step definitions |
| Type error        | Stack trace             | Fix type hints, mock returns              |
| Matrix gap        | FR/NFR matrix           | Add positive, negative, edge, or NFR case |
| Flaky risk        | Test inspection         | Remove timing/order/env/shared-state risk |

### Step 4: Fix and Re-test

```bash
# Fix the code/tests
make unit-tests           # Re-run to verify fix
```

Repeat Steps 2-4 until all tests pass with 100% coverage.

## Mutation Testing (Infection)

**Goal**: 100% Mutation Score Indicator (MSI) - Zero escaped mutants

### Run Mutation Tests

```bash
make infection
```

### Fix Escaped Mutants

1. Review mutation diff in output
2. Add test case for uncaught mutation
3. Strengthen assertion specificity
4. Consider refactoring for testability

**Example**: If mutant changes `>` to `>=`, add boundary test case.

## Faker Usage in Tests

**Setup**: Tests extend `UnitTestCase` which provides `$this->faker`

```php
// Good - Dynamic test data
$this->faker->email();
$this->faker->lexify('??');  // 2 random letters
$this->faker->unique()->ulid();

// Bad - Hardcoded values
'test@example.com'
'AB'
```

**Available**:

- `$this->faker->ulid()` - Domain ULID via custom provider
- All standard Faker methods (email, name, word, etc.)

## Load Testing

**Commands**:

```bash
make smoke-load-tests      # Minimal load, 5-10 min
make average-load-tests    # Normal traffic, 15-25 min
make stress-load-tests     # High load, 20-30 min
make spike-load-tests      # Extreme spikes, 25-35 min
```

**Prerequisites**:

- Test database seeded (`make setup-test-db`)
- Docker containers running (`make start`)
- K6 Docker image built

## Flaky-Test Review

Every new or changed test must be inspected for deterministic behavior.

Reject or fix tests with:

- Sleeps, unbounded waits, wall-clock assumptions, or timezone/locale dependence.
- Shared mutable state, non-unique IDs, rate-limit leakage, or order dependency.
- External network calls in unit/integration tests.
- Random test data not generated through project helpers or not isolated.
- Weak assertions that only check "no exception" or status without payload/business effect.
- CI-only environment assumptions not documented in workflow configuration.

When a test must use time, async behavior, or external services, require a fake
clock, bounded polling with assertions, local stub, fixture isolation, or a
clear CI check that proves repeatability.

## Constraints (Parameters)

**NEVER**:

- Cancel long-running tests mid-execution
- Commit with failing tests
- Accept coverage < 100%
- Allow escaped mutants
- Accept green tests when FR/NFR positive, negative, or edge cases remain unmapped
- Accept flaky tests or weak assertions as coverage
- Run tests outside Docker (use `make` commands)

**ALWAYS**:

- Build and update the FR/NFR test matrix before claiming coverage
- Use Faker for dynamic test data
- Mock external dependencies in unit tests
- Use real DB in integration tests
- Ensure deterministic test results
- Map NFRs to the correct suite: security/contract/load/observability/operability checks as appropriate

## Format (Output)

**Unit Tests Success**:

```
OK (X tests, Y assertions)
✅ COVERAGE SUCCESS: Line coverage is 100%
```

**Mutation Testing Success**:

```
100% MSI
0 escaped mutants
```

## Verification Checklist

- [ ] All tests pass
- [ ] Coverage is 100%
- [ ] Zero escaped mutants (if running mutation tests)
- [ ] FR/NFR matrix has positive, negative, and edge cases mapped to evidence
- [ ] NFR-specific cases have CI, contract, mutation, load, or observability evidence
- [ ] Flaky-test risk reviewed and fixed
- [ ] No hardcoded test values (use Faker)
- [ ] Tests run in Docker container via `make`
