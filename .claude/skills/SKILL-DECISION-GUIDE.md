# Skill Decision Guide

**Choose the right skill for your task based on what you're trying to accomplish.**

## 🚨 Mandatory New Feature Verification Gate (ALL Skills)

If you created or modified a **NEW feature**, you MUST execute **every** skill in `.claude/skills/` **after implementation**. The decision tree below is for choosing the primary skill during the work. It does **not** replace this gate.

**Execution rules:**

1. Open each `SKILL.md` file listed below.
2. Follow its steps exactly. If a skill is not applicable, explicitly record **"Not applicable"** with a concrete reason.
3. Run required commands using `make` or `docker compose exec php ...` only.
4. Provide evidence in your response: commands run and outcomes. If you cannot run a command, stop and explain why.
5. Do not claim the feature is complete until this gate is finished.

**Skills to execute for every new feature:**

- `api-platform-crud`
- `cache-management`
- `ci-workflow`
- `code-organization`
- `code-review`
- `complexity-management`
- `database-migrations`
- `deptrac-fixer`
- `documentation-creation`
- `documentation-sync`
- `implementing-ddd-architecture`
- `load-testing`
- `observability-instrumentation`
- `openapi-development`
- `quality-standards`
- `query-performance-analysis`
- `structurizr-architecture-sync`
- `testing-workflow`

## Quick Decision Tree

```
What are you trying to do?
│
├─ Fix something broken
│   ├─ Deptrac violation → deptrac-fixer
│   ├─ High complexity → complexity-management
│   ├─ Test failures → testing-workflow
│   ├─ PHPInsights fails → complexity-management
│   ├─ N+1 queries → query-performance-analysis
│   ├─ Slow queries → query-performance-analysis
│   └─ CI checks failing → ci-workflow

├─ Create something new
│   ├─ New entity/value object → implementing-ddd-architecture
│   ├─ New API endpoint → api-platform-crud
│   ├─ New load test → load-testing
│   ├─ New database entity → database-migrations
│   ├─ Add caching / invalidation → cache-management
│   ├─ New test cases → testing-workflow
│   ├─ Add business metrics → observability-instrumentation
│   └─ Fix file placement / boundaries → code-organization

├─ Review/validate work
│   ├─ Before committing → ci-workflow
│   ├─ PR feedback → code-review
│   ├─ Quality thresholds → quality-standards
│   └─ Query performance → query-performance-analysis
│
├─ Update documentation
│   ├─ New project needs docs → documentation-creation
│   └─ Any code change → documentation-sync
│
└─ Architecture diagrams
    └─ Update workspace.dsl → structurizr-architecture-sync
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

### "I need to add caching / cache invalidation"

**Use**: [cache-management](cache-management/SKILL.md)

This skill covers cache key design, TTLs, tag-based invalidation, decorator-based cached repositories, and best-effort event-driven invalidation.

**NOT**: complexity-management (that’s for cyclomatic complexity)

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

### "Endpoint is slow or making too many queries"

**Use**: [query-performance-analysis](query-performance-analysis/SKILL.md)

This skill detects N+1 queries, analyzes slow queries with EXPLAIN, and identifies missing indexes.

**NOT**: load-testing (that's for performance under concurrent load)
**NOT**: testing-workflow (that's for functional tests)

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

### "I need to create documentation for a new project"

**Use**: [documentation-creation](documentation-creation/SKILL.md)

This skill guides creating a complete documentation suite from scratch.

**NOT**: documentation-sync (that's for updating existing docs)

---

### "I need to add a new field to an entity"

**Use**: [database-migrations](database-migrations/SKILL.md)

This skill guides entity modification with Doctrine ODM.

**ALSO**: Check [implementing-ddd-architecture](implementing-ddd-architecture/SKILL.md) for proper DDD patterns.

---

### "I'm adding OpenAPI endpoint documentation"

**Use**: [openapi-development](openapi-development/SKILL.md)

This skill covers OpenAPI factories/sanitizers/augmenters/cleaners and the repo's validation commands.

---

### "I need to add business metrics to track domain events"

**Use**: [observability-instrumentation](observability-instrumentation/SKILL.md)

This skill guides adding AWS EMF business metrics via event subscribers for CloudWatch dashboards.

**NOT**: load-testing (that's for performance under load)
**NOT**: testing-workflow (that's for functional tests)

---

### "I need to update architecture diagrams"

**Use**: [structurizr-architecture-sync](structurizr-architecture-sync/SKILL.md)

This skill guides updating workspace.dsl when adding components or changing architecture.

**ALSO**: Use after [implementing-ddd-architecture](implementing-ddd-architecture/SKILL.md) when creating new domain models.
**ALSO**: Use after [deptrac-fixer](deptrac-fixer/SKILL.md) when fixing layer violations.

---

## Skill Relationship Map

```
                          quality-standards
                         (overview & routing)
                                 │
                    ┌────────────┼────────────┐
                    ▼            ▼            ▼
           complexity-    deptrac-fixer   testing-workflow
           management           │               │
                                ▼               ▼
                      implementing-ddd-   load-testing
                        architecture      (performance)
                              │
              ┌───────────────┴───────────────┐
              ▼                               ▼
       database-                      structurizr-
        migrations                    architecture-sync
```

## Common Confusions

| Confusion                                      | Clarification                                                                                                 |
| ---------------------------------------------- | ------------------------------------------------------------------------------------------------------------- |
| deptrac-fixer vs implementing-ddd-architecture | **Fix violations** → deptrac-fixer<br>**Design new patterns** → implementing-ddd-architecture                 |
| testing-workflow vs load-testing               | **Functional tests** (unit, integration, E2E) → testing-workflow<br>**Performance tests** (K6) → load-testing |
| quality-standards vs complexity-management     | **Overview of all metrics** → quality-standards<br>**Fix complexity specifically** → complexity-management    |
| ci-workflow vs testing-workflow                | **Run all CI checks** → ci-workflow<br>**Debug specific test issues** → testing-workflow                      |
| query-performance-analysis vs load-testing     | **Query optimization** (N+1, indexes) → query-performance-analysis<br>**Concurrent load** (K6) → load-testing |
| implementing-ddd vs structurizr-architecture   | **Create code** → implementing-ddd-architecture<br>**Document diagrams** → structurizr-architecture-sync      |

## Multiple Skills for One Task

Some tasks benefit from multiple skills:

### Creating a complete new feature:

1. **implementing-ddd-architecture** - Design domain model
2. **api-platform-crud** - Create API endpoints
3. **database-migrations** - Configure persistence
4. **observability-instrumentation** - Add business metrics
5. **testing-workflow** - Write tests
6. **structurizr-architecture-sync** - Update architecture diagrams
7. **documentation-sync** - Update docs
8. **ci-workflow** - Validate everything

### Fixing architecture issues:

1. **deptrac-fixer** - Fix the violations
2. **implementing-ddd-architecture** - Understand why (if needed)
3. **structurizr-architecture-sync** - Update diagrams to match
4. **ci-workflow** - Verify fix

### Performance optimization:

1. **query-performance-analysis** - Fix N+1 queries, add indexes
2. **load-testing** - Create performance tests
3. **complexity-management** - Reduce code complexity
4. **ci-workflow** - Ensure quality maintained
