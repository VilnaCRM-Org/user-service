# Quality Standards Integration

How code review integrates with quality standards and ensures no regression.

## Protected Quality Metrics

Code reviews MUST maintain these standards - **NEVER decrease them**:

### PHPInsights Requirements

**Source Code** (`phpinsights.php`):

- min-quality: **100%** (NEVER decrease)
- min-complexity: **93%** (NEVER decrease)
- min-architecture: **100%** (NEVER decrease)
- min-style: **100%** (NEVER decrease)

**Test Code** (`phpinsights-tests.php`):

- min-quality: **95%** (NEVER decrease)
- min-complexity: **95%** (NEVER decrease)
- min-architecture: **90%** (NEVER decrease)
- min-style: **95%** (NEVER decrease)

### Test Coverage Requirements

- **Unit test coverage**: 100% (NEVER decrease)
- **Integration test coverage**: Comprehensive (NEVER decrease)
- **Mutation testing (Infection)**: 100% MSI, 0 escaped mutants (NEVER decrease)

### Cyclomatic Complexity

- **Maximum per method**: < 5
- **Action when exceeded**: Refactor to extract methods, use strategy pattern

## Quality Check Workflow in Code Review

### Step 1: Before Implementing Review Changes

```bash
# Get baseline metrics
make ci 2>&1 | tee baseline-ci.log

# Check current coverage
make tests-with-coverage

# Check current mutation score
make infection
```

### Step 2: After Each Change

```bash
# Quick quality check
make phpcsfixer  # Fix code style
make psalm       # Static analysis
make unit-tests  # Verify tests pass
```

### Step 3: After All Changes Complete

```bash
# Comprehensive CI check
make ci

# MUST output at end:
# ✅ CI checks successfully passed!
```

### Step 4: If CI Fails - Systematic Resolution

#### A. PHPInsights Complexity Failure

When `make phpinsights` reports **only** "complexity score too low" without specific files:

```bash
# 1. Run PHPMD first to identify hotspots
make phpmd

# 2. Address each PHPMD finding:
#    - High cyclomatic complexity methods
#    - Extract complex logic to methods
#    - Use strategy pattern for complex conditionals

# 3. Re-run PHPInsights
make phpinsights  # Must pass now
```

**Example Refactoring**:

```php
// ❌ BEFORE: Cyclomatic complexity = 8
public function validate($value, Constraint $constraint): void
{
    if ($value === null || ($constraint->isOptional() && $value === '')) {
        return;
    }
    if (!(strlen($value) >= 8 && strlen($value) <= 64)) {
        $this->addViolation('invalid.length');
    }
    if (!preg_match('/[A-Z]/', $value)) {
        $this->addViolation('missing.uppercase');
    }
    // More complex conditions...
}

// ✅ AFTER: Cyclomatic complexity = 2
public function validate($value, Constraint $constraint): void
{
    if ($this->skipChecker->shouldSkip($value, $constraint)) {
        return;
    }
    $this->performValidations($value);
}

private function performValidations(string $value): void
{
    $this->lengthValidator->validate($value);
    $this->uppercaseValidator->validate($value);
    // Delegated to specific validators
}
```

#### B. Test Coverage Failure

```bash
# Run coverage report
make tests-with-coverage

# Identify uncovered lines
make coverage-html
# Open var/coverage/index.html

# Add tests for uncovered lines
# Re-run to verify 100% coverage
make unit-tests
```

#### C. Mutation Testing Failure

```bash
# Run infection
make infection

# Analyze escaped mutants
# Example output:
# 1) src/Shared/Domain/ValueObject/Ulid.php:45
#    Escaped Mutant: Changed === to !==

# Fix: Add test case covering the mutation
# Re-run until 100% MSI with 0 escaped mutants
make infection
```

#### D. Static Analysis (Psalm) Errors

```bash
# Run psalm
make psalm

# Address each error type:
# - Type mismatches: Fix type hints
# - Undefined classes: Check namespaces/imports
# - Null safety: Add null checks or use type unions

# Re-run to verify
make psalm
```

#### E. Architecture Violations (Deptrac)

```bash
# Run deptrac
make deptrac

# Common violations:
# - Domain importing Symfony/Doctrine
# - Infrastructure importing Application
# - Wrong layer dependencies

# Fix by:
# - Move code to correct layer
# - Use interfaces to invert dependencies
# - Remove framework imports from Domain

make deptrac  # Must show 0 violations
```

## Quality Standards Enforcement Examples

### Example 1: Review Suggests Complexity Increase

**Scenario**: Review comment suggests adding complex validation logic

**Response**:

```
Thank you for the suggestion. However, adding this logic would increase
cyclomatic complexity above our 5 per method limit.

Instead, I'll:
1. Extract validation to separate validator class
2. Use strategy pattern for conditional logic
3. Maintain complexity < 5 while implementing the feature

This keeps our quality standards intact while addressing the concern.
```

### Example 2: Review Suggests Skipping Tests

**Scenario**: Review comment: "This is trivial, tests not needed"

**Response**:

```
We maintain 100% test coverage and 100% MSI (mutation testing).
All code must be tested, including trivial cases, to ensure:

1. Mutations are caught (mutation testing requirement)
2. Behavior is documented via tests
3. Future changes don't break existing behavior

I'll add comprehensive tests including edge cases.
```

### Example 3: Review Suggests Lowering Standards

**Scenario**: Review comment: "Lower PHPInsights threshold to merge faster"

**Response**:

```
Quality thresholds are protected and cannot be decreased:
- PHPInsights: 100% quality, 93% complexity, 100% architecture, 100% style
- Test coverage: 100%
- Mutation score: 100% MSI

These are enforced by make ci. Instead, I'll address the quality issues
by refactoring to meet standards. This ensures long-term maintainability.
```

## Integration with CI Workflow

### CI Command Breakdown

`make ci` runs these checks in order:

1. **composer-validate**: Validate composer.json/lock
2. **check-requirements**: Symfony requirements
3. **check-security**: Security vulnerabilities
4. **phpcsfixer**: Code style (PSR-12)
5. **psalm**: Static analysis
6. **psalm-security**: Security taint analysis
7. **phpmd**: Mess detector (complexity)
8. **phpinsights**: Code quality metrics
9. **deptrac**: Architecture compliance
10. **unit-tests**: 100% coverage required
11. **integration-tests**: Integration suite
12. **behat**: End-to-end tests
13. **infection**: Mutation testing (100% MSI)

**Success Criteria**: `✅ CI checks successfully passed!` message at end

### Workflow Integration

```mermaid
PR Comments → Categorize → Apply Changes → Run CI → Pass? → Done
                                              ↓
                                             Fail
                                              ↓
                                    Address Issues Systematically
                                              ↓
                                           Run CI Again
                                              ↓
                                             Pass? → Done
```

## Quality Regression Prevention

### Checklist Before Commit

```bash
# 1. Code style compliant
make phpcsfixer
# ✅ Should auto-fix all style issues

# 2. Static analysis clean
make psalm
# ✅ Should show 0 errors

# 3. Architecture compliant
make deptrac
# ✅ Should show 0 violations

# 4. Tests pass
make unit-tests
# ✅ Should show 100% coverage

# 5. Mutation testing
make infection
# ✅ Should show 100% MSI, 0 escaped mutants

# 6. Comprehensive CI
make ci
# ✅ Must output "CI checks successfully passed!"
```

### Common Regression Scenarios

#### Scenario 1: Adding New Method Without Tests

```bash
# Symptom: Coverage drops below 100%
make tests-with-coverage
# Error: Coverage is 99.8%, expected 100%

# Fix: Add comprehensive tests for new method
# Including edge cases and error conditions

# Verify:
make unit-tests  # Must show 100% coverage
```

#### Scenario 2: Refactoring Increases Complexity

```bash
# Symptom: PHPInsights complexity fails
make phpinsights
# Error: Complexity score is 94%, expected >= 95%

# Fix:
make phpmd  # Identify specific complex methods
# Refactor complex methods (extract, strategy pattern)

# Verify:
make phpinsights  # Must pass
```

#### Scenario 3: New Code Missing Mutation Coverage

```bash
# Symptom: Escaped mutants in infection
make infection
# Error: MSI 98%, 2 escaped mutants

# Fix: Add tests covering the specific mutations
# Example: Test both === and !== conditions

# Verify:
make infection  # Must show 100% MSI, 0 escaped
```

## Related Documentation

- **quality-standards skill**: Complete quality standards reference
- **ci-workflow skill**: Comprehensive CI process
- **testing-workflow skill**: Test coverage and mutation testing
- **code-organization skill**: Organization best practices
- **implementing-ddd-architecture skill**: DDD patterns and structure

## Quick Reference Card

```
BEFORE FINISHING CODE REVIEW:
✅ make ci shows "CI checks successfully passed!"
✅ PHPInsights: 100% quality, 93% complexity, 100% architecture/style
✅ Test coverage: 100%
✅ Mutation score: 100% MSI, 0 escaped mutants
✅ Cyclomatic complexity: < 5 per method
✅ All quality metrics maintained or improved
✅ No regressions introduced

IF CI FAILS:
1. Run specific check (make phpmd, make psalm, etc.)
2. Address reported issues
3. Re-run make ci
4. Repeat until success message appears

NEVER:
❌ Decrease quality thresholds
❌ Skip tests for "trivial" code
❌ Allow complexity > 5 per method
❌ Commit with failing CI
❌ Leave escaped mutants
```
