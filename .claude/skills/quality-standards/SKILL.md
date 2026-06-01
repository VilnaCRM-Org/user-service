---
name: quality-standards
description: Overview of protected quality thresholds, FR/NFR quality evidence, and quick reference for all quality tools. Use when you need to understand quality metrics, run comprehensive quality checks, score system quality attributes, verify CI/test coverage for requirements, or learn which specialized skill to use. For specific issues, use dedicated skills (deptrac-fixer for Deptrac, complexity-management for PHPInsights, testing-workflow for coverage).
---

# Quality Standards Skill

## Context (Input)

- Need to understand protected quality thresholds
- Need to verify FR/NFR coverage and system quality attributes
- Running comprehensive quality checks before commit
- Determining which specialized skill to use for specific issues
- Quick reference for quality tool commands

## Task (Function)

Understand quality metrics, FR/NFR quality evidence, system quality attributes,
and route to appropriate specialized skill for fixes.

**Success Criteria**: Know which skill to use for your specific quality issue.

## Protected Quality Thresholds

**CRITICAL**: These thresholds are protected and must NEVER be lowered.

Green numeric thresholds are necessary but not sufficient. A PR also needs
requirement-level evidence: each applicable FR/NFR must have positive,
negative, and edge coverage mapped to automated tests, CI checks, mutation,
contract, or load/performance evidence. Manual evidence is supporting context
only and cannot replace automation. Use
[code-review/reference/fr-nfr-quality-gate.md](../code-review/reference/fr-nfr-quality-gate.md)
for the mandatory scorecard.

### PHPInsights (Source Code)

| Metric       | Required | Fix With                                                   |
| ------------ | -------- | ---------------------------------------------------------- |
| Quality      | 100%     | [complexity-management](../complexity-management/SKILL.md) |
| Complexity   | 94%      | [complexity-management](../complexity-management/SKILL.md) |
| Architecture | 100%     | [deptrac-fixer](../deptrac-fixer/SKILL.md)                 |
| Style        | 100%     | Run `make phpcsfixer`                                      |

### PHPInsights (Tests)

| Metric       | Required | Fix With                                                   |
| ------------ | -------- | ---------------------------------------------------------- |
| Quality      | 95%      | [complexity-management](../complexity-management/SKILL.md) |
| Complexity   | 95%      | [complexity-management](../complexity-management/SKILL.md) |
| Architecture | 90%      | [deptrac-fixer](../deptrac-fixer/SKILL.md)                 |
| Style        | 95%      | Run `make phpcsfixer`                                      |

### Other Tools

| Tool      | Metric          | Required | Fix With                                                                                    |
| --------- | --------------- | -------- | ------------------------------------------------------------------------------------------- |
| Deptrac   | Violations      | 0        | [deptrac-fixer](../deptrac-fixer/SKILL.md)                                                  |
| Psalm     | Errors          | 0        | Fix reported issues                                                                         |
| Psalm     | ForbiddenCode   | 0        | Use Symfony Serializer and follow the architecture guards documented in `code-organization` |
| Psalm     | Security Issues | 0        | Fix tainted flows                                                                           |
| Infection | MSI             | 100%     | [testing-workflow](../testing-workflow/SKILL.md)                                            |
| PHPUnit   | Coverage        | 100%     | [testing-workflow](../testing-workflow/SKILL.md)                                            |

## Requirement Coverage Gate

For PR review and release readiness, require:

- Complete FR/NFR extraction from specs, stories, architecture, docs, PR description, and changed behavior.
- Positive, negative, edge, security, performance, operability, compatibility, and regression cases for each requirement.
- Evidence mapping for every case.
- Explicit report of missing coverage as blocking review findings.
- Flaky-test review for all changed tests.
- System quality attribute scorecard for all attributes listed in the strict gate reference.

Do not accept "line coverage is 100%" as proof of behavior coverage unless the
FR/NFR matrix shows the actual cases and assertions.

## Quick Reference Commands

### Comprehensive Checks

```bash
# Run all CI checks (recommended before commit)
make ci
```

**Success**: Must output "✅ CI checks successfully passed!"

### Individual Quality Checks

| Check               | Command                  | Purpose                      |
| ------------------- | ------------------------ | ---------------------------- |
| Code quality        | `make phpinsights`       | All PHPInsights metrics      |
| Complexity analysis | `make phpmd`             | Find high-complexity methods |
| Static analysis     | `make psalm`             | Type checking and errors     |
| Security taint      | `make psalm-security`    | Security vulnerability scan  |
| Architecture        | `make deptrac`           | Layer boundary validation    |
| Code style          | `make phpcsfixer`        | Auto-fix PSR-12 style        |
| Composer validation | `make composer-validate` | Validate composer.json       |

### Testing Commands

| Check             | Command                    | Purpose                     |
| ----------------- | -------------------------- | --------------------------- |
| Unit tests        | `make unit-tests`          | Domain/Application logic    |
| Integration tests | `make integration-tests`   | Component interactions      |
| E2E tests         | `make behat`               | Full user scenarios (Behat) |
| All tests         | `make all-tests`           | Unit + Integration + E2E    |
| Test coverage     | `make tests-with-coverage` | Generate coverage report    |
| Mutation tests    | `make infection`           | Test quality validation     |

## Routing to Specialized Skills

When quality checks fail, use the appropriate specialized skill:

### Architecture Issues

- **Deptrac violations** → [deptrac-fixer](../deptrac-fixer/SKILL.md)

  - Domain depends on Infrastructure
  - Layer boundary violations
  - "must not depend on" errors

- **DDD architecture patterns** → [implementing-ddd-architecture](../implementing-ddd-architecture/SKILL.md)
  - Creating new entities/value objects
  - Implementing CQRS patterns
  - Understanding layer responsibilities

### Code Quality Issues

- **High cyclomatic complexity** → [complexity-management](../complexity-management/SKILL.md)

  - PHPInsights complexity < 94%
  - PHPMD reports high CCN
  - Methods too complex

- **Structural/naming issues** → [code-organization](../code-organization/SKILL.md)

  - Class in wrong directory for its type
  - Vague variable or class names
  - Hardcoded config values that should be in `.env`
  - Namespace doesn't match directory structure

- **Code style issues** → Run `make phpcsfixer`
  - PSR-12 violations
  - Line length > 100 chars
  - Formatting issues

### Testing Issues

- **Test failures** → [testing-workflow](../testing-workflow/SKILL.md)
  - Unit/Integration/E2E failures
  - Mutation testing (Infection)
  - Test coverage < 100%
  - FR/NFR matrix gaps
  - Flaky-test risk

- **System quality attribute WARN/FAIL** → [code-review](../code-review/SKILL.md) plus the relevant specialized skill
  - Missing evidence for quality attributes
  - Unscored applicable attributes
  - Improvement suggestions required by strict review

### Workflow Integration

- **Before committing** → [ci-workflow](../ci-workflow/SKILL.md)

  - Run all checks systematically
  - Fix failures in priority order
  - Ensure all checks pass

- **PR review feedback** → [code-review](../code-review/SKILL.md)
  - Fetch and address PR comments
  - Systematic comment resolution

## Quality Improvement Workflow

### Step 1: Run Comprehensive Checks

```bash
make ci
```

### Step 2: Identify Failing Check

Check output for specific failure:

```
❌ CI checks failed:
  - phpinsights: Complexity score too low (93.5% < 94%)
```

### Step 3: Use Specialized Skill

Based on failure type, use appropriate skill:

| Failure Pattern            | Skill to Use             |
| -------------------------- | ------------------------ |
| "Complexity score too low" | complexity-management    |
| "Deptrac violations"       | deptrac-fixer            |
| "must not depend on"       | deptrac-fixer            |
| "Class not found"          | code-organization        |
| "Namespace mismatch"       | code-organization        |
| "tests failed"             | testing-workflow         |
| "requirement matrix gap"   | testing-workflow         |
| "flaky test"               | testing-workflow         |
| "quality attribute"        | code-review              |
| "Psalm found errors"       | Fix type errors directly |
| "escaped mutants"          | testing-workflow         |

### Step 4: Re-run CI

```bash
make ci
```

Repeat until: "✅ CI checks successfully passed!"

## Constraints (Parameters)

### NEVER

- Lower quality thresholds in config files (`phpinsights.php`, `infection.json5`, etc.)
- Skip failing checks to "save time"
- Skip FR/NFR coverage mapping for PR review
- Skip system quality attribute scoring for PR review
- Treat manual evidence as enough to close an applicable FR/NFR automation gap
- Commit code without all CI checks passing
- Modify `deptrac.yaml` to allow violations (fix code, not config)
- Disable security checks
- Add suppression/ignore annotations to hide quality issues (`@SuppressWarnings`, `@infection-ignore*`, `@codeCoverageIgnore*`, `@psalm-suppress`, `@phpstan-ignore*`, `phpcs:ignore`, `@phpinsights-ignore*`)

### ALWAYS

- Fix code to meet standards (not config to meet code)
- Run `make ci` before creating commits
- Use specialized skills for specific quality issues
- Maintain 100% test coverage
- Maintain requirement-level test evidence, not only line coverage
- Require deterministic tests and fix flaky-test risks
- Keep cyclomatic complexity low (target: < 5 per method)
- Respect hexagonal architecture boundaries

## Format (Output)

### Expected CI Output

```
✅ CI checks successfully passed!
```

### Expected PHPInsights Output

```
[CODE] 100.0 pts       ✅ Target: 100%
[COMPLEXITY] 94.0 pts  ✅ Target: 94%
[ARCHITECTURE] 100 pts ✅ Target: 100%
[STYLE] 100.0 pts      ✅ Target: 100%
```

### Expected Deptrac Output

```
✅ No violations found
```

### Expected Infection Output

```
Mutation Score Indicator (MSI): 100%
```

## Verification Checklist

After using this skill:

- [ ] Identified which quality check is failing
- [ ] Selected appropriate specialized skill for the issue
- [ ] Ready to execute specialized skill workflow
- [ ] Understand which threshold applies to the failure
- [ ] Know the command to re-run the check after fixes
- [ ] FR/NFR coverage matrix has no gaps for reviewed changes
- [ ] System quality attributes are scored or explicitly marked N/A with reasons

## Related Skills

- [ci-workflow](../ci-workflow/SKILL.md) - Run comprehensive CI validation
- [complexity-management](../complexity-management/SKILL.md) - Reduce complexity, improve quality
- [code-organization](../code-organization/SKILL.md) - Fix structural/naming issues, extract hardcoded configs
- [deptrac-fixer](../deptrac-fixer/SKILL.md) - Fix architectural violations
- [implementing-ddd-architecture](../implementing-ddd-architecture/SKILL.md) - Understand DDD patterns
- [testing-workflow](../testing-workflow/SKILL.md) - Fix test failures, improve coverage

## Reference Documentation

For detailed examples and patterns, see:

- **Refactoring patterns** → complexity-management skill
- **Architecture rules** → implementing-ddd-architecture skill
- **Layer boundaries** → deptrac-fixer skill
- **Testing strategies** → testing-workflow skill
