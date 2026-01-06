# Quality Standards Integration for Code Review

How to maintain quality standards when implementing PR review feedback.

> **For complete quality thresholds and commands, see the `quality-standards` skill**

## Core Principle

**Code reviews MUST maintain or improve quality metrics - NEVER decrease them.**

When implementing review feedback, quality standards are non-negotiable.

## PR Review Workflow

### 1. Apply Review Feedback

Implement changes as requested by reviewers.

### 2. Quick Verification After Each Change

```bash
make phpcsfixer && make psalm && make unit-tests
```

### 3. Final Comprehensive Check

```bash
make ci  # MUST show "✅ CI checks successfully passed!"
```

### 4. If CI Fails

**Invoke the appropriate skill** based on failure type:

| Failure                | Invoke Skill              |
| ---------------------- | ------------------------- |
| Complexity issues      | `complexity-management`   |
| Architecture violation | `deptrac-fixer`           |
| Test failures          | `testing-workflow`        |
| See complete mapping   | `quality-standards` skill |

## PR Review-Specific Scenarios

How to respond when review feedback conflicts with quality standards.

### Scenario 1: Review Suggests Complexity Increase

**Review Comment**: "Add complex validation logic here"

**How to Respond**:

```
Thank you for the suggestion. However, adding this logic would increase
cyclomatic complexity above our < 5 per method limit.

Instead, I'll:
1. Extract validation to separate validator class
2. Use strategy pattern for conditional logic
3. Maintain complexity standards while implementing the feature

This keeps our quality standards intact while addressing the concern.
```

### Scenario 2: Review Suggests Skipping Tests

**Review Comment**: "This is trivial, tests not needed"

**How to Respond**:

```
We maintain 100% test coverage and 100% MSI (mutation testing).
All code must be tested, including trivial cases, to ensure:

1. Mutations are caught (mutation testing requirement)
2. Behavior is documented via tests
3. Future changes don't break existing behavior

I'll add comprehensive tests including edge cases.
```

### Scenario 3: Review Suggests Lowering Standards

**Review Comment**: "Lower PHPInsights threshold to merge faster"

**How to Respond**:

```
Quality thresholds are protected and cannot be decreased.
These are enforced by `make ci`.

Instead, I'll address the quality issues by refactoring to meet standards.
This ensures long-term maintainability.

See the `quality-standards` skill for complete threshold details.
```

## Quick Checklist

**Before marking PR review as complete:**

- ✅ `make ci` shows "✅ CI checks successfully passed!"
- ✅ All review comments addressed
- ✅ No quality regressions introduced
- ✅ All conversations resolved

**Related Skills:**

- `quality-standards` - Complete thresholds and commands
- `ci-workflow` - Comprehensive CI process
- `complexity-management` - Fix complexity issues
- `testing-workflow` - Fix test coverage issues
- `deptrac-fixer` - Fix architecture violations
