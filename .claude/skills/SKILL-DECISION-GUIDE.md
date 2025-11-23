# Skill Decision Guide

**Choose the right skill for your task based on what you're trying to accomplish.**

## Quick Decision Tree

```
What are you trying to do?
│
├─ Fix something broken
│   ├─ Deptrac violation → deptrac-fixer
│   ├─ High complexity → complexity-management
│   ├─ Test failures → testing-workflow
│   ├─ PHPInsights fails → complexity-management
│   └─ CI checks failing → ci-workflow
│
├─ Create something new
│   ├─ New entity/value object → implementing-ddd-architecture
│   ├─ New API endpoint → developing-openapi-specs
│   ├─ New load test → load-testing
│   ├─ New database entity → database-migrations
│   └─ New test cases → testing-workflow
│
├─ Review/validate work
│   ├─ Before committing → ci-workflow
│   ├─ PR feedback → code-review
│   └─ Quality thresholds → quality-standards
│
└─ Update documentation
    └─ Any code change → documentation-sync
```

## Scenario-Based Guide

### "Deptrac is failing with violations"

**Use**: [deptrac-fixer](deptrac-fixer/SKILL.md)

This skill parses violation messages and provides exact fix patterns.

**NOT**: implementing-ddd-architecture (that's for designing new patterns)
**NOT**: quality-standards (that's just an overview)

---

### "I need to create a new entity with value objects"

**Use**: [implementing-ddd-architecture](implementing-ddd-architecture/SKILL.md)

This skill guides proper DDD structure and file placement.

**NOT**: deptrac-fixer (that's for fixing violations)
**NOT**: database-migrations (that's for the database side)

---

### "PHPInsights complexity score is too low"

**Use**: [complexity-management](complexity-management/SKILL.md)

This skill provides refactoring strategies to reduce complexity.

**NOT**: quality-standards (that's just an overview of thresholds)

---

### "I need to write K6 load tests"

**Use**: [load-testing](load-testing/SKILL.md)

This skill has REST and GraphQL load test patterns.

**NOT**: testing-workflow (that's for functional tests only)

---

### "Tests are failing and I need to debug"

**Use**: [testing-workflow](testing-workflow/SKILL.md)

This skill covers PHPUnit, Behat, and Infection debugging.

**NOT**: load-testing (that's for performance tests)
**NOT**: ci-workflow (that runs tests but doesn't debug)

---

### "I need to understand what quality metrics are protected"

**Use**: [quality-standards](quality-standards/SKILL.md)

This skill documents all thresholds and directs to specialized skills.

**NOT**: complexity-management (that's specifically for complexity)

---

### "I'm addressing PR review comments"

**Use**: [code-review](code-review/SKILL.md)

This skill systematically handles review feedback.

**NOT**: ci-workflow (that's for running checks)

---

### "I made code changes and need to validate before committing"

**Use**: [ci-workflow](ci-workflow/SKILL.md)

This skill runs comprehensive CI checks.

**NOT**: testing-workflow (that's specifically for tests)

---

### "I added a new feature and need to update docs"

**Use**: [documentation-sync](documentation-sync/SKILL.md)

This skill identifies which documentation files need updating.

---

### "I need to add a new field to an entity"

**Use**: [database-migrations](database-migrations/SKILL.md)

This skill guides entity modification with Doctrine ODM.

**ALSO**: Check [implementing-ddd-architecture](implementing-ddd-architecture/SKILL.md) for proper DDD patterns.

---

### "I'm adding OpenAPI endpoint documentation"

**Use**: [developing-openapi-specs](developing-openapi-specs/SKILL.md)

This skill covers processor patterns for OpenAPI.

---

## Skill Relationship Map

```
                          quality-standards
                         (overview & routing)
                                 │
                    ┌────────────┼────────────┐
                    ▼            ▼            ▼
           complexity-    deptrac-fixer   testing-workflow
           management                            │
                              │                  │
                              ▼                  ▼
                    implementing-ddd-      load-testing
                      architecture         (performance)
                              │
                              ▼
                    database-migrations
```

## Common Confusions

| Confusion                                      | Clarification                                                                                                 |
| ---------------------------------------------- | ------------------------------------------------------------------------------------------------------------- |
| deptrac-fixer vs implementing-ddd-architecture | **Fix violations** → deptrac-fixer<br>**Design new patterns** → implementing-ddd-architecture                 |
| testing-workflow vs load-testing               | **Functional tests** (unit, integration, E2E) → testing-workflow<br>**Performance tests** (K6) → load-testing |
| quality-standards vs complexity-management     | **Overview of all metrics** → quality-standards<br>**Fix complexity specifically** → complexity-management    |
| ci-workflow vs testing-workflow                | **Run all CI checks** → ci-workflow<br>**Debug specific test issues** → testing-workflow                      |

## Multiple Skills for One Task

Some tasks benefit from multiple skills:

### Creating a complete new feature:

1. **implementing-ddd-architecture** - Design domain model
2. **database-migrations** - Configure persistence
3. **testing-workflow** - Write tests
4. **documentation-sync** - Update docs
5. **ci-workflow** - Validate everything

### Fixing architecture issues:

1. **deptrac-fixer** - Fix the violations
2. **implementing-ddd-architecture** - Understand why (if needed)
3. **ci-workflow** - Verify fix

### Performance optimization:

1. **load-testing** - Create performance tests
2. **complexity-management** - Reduce code complexity
3. **ci-workflow** - Ensure quality maintained
