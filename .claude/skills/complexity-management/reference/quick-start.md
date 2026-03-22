# Quick Start Guide for Complexity Refactoring

Fast-track guide for code agents to systematically reduce complexity. Start here for immediate action.

## 🎯 Your Mission

Reduce cyclomatic complexity to meet PHPInsights standards:

- **Target**: 93% complexity minimum
- **Method complexity**: < 5 average per method
- **Maintain**: 100% test coverage, 100% mutation score

## 🚀 START HERE

```bash
make analyze-complexity N=20
```

This shows the top 20 most complex classes in your codebase.

## 📋 Workflow (Repeat for Each Class)

### 1️⃣ ANALYZE

For the highest complexity class from analysis:

```bash
# Read implementation
cat src/path/to/ComplexClass.php

# Review tests
cat tests/Unit/path/to/ComplexClassTest.php

# Identify complex methods
# Look for: high if/else, loops, boolean operators (&&/||)
```

**Focus on**:

- Methods with complexity > 10
- Nested conditionals (3+ levels deep)
- Long methods (> 50 lines)
- Multiple responsibilities

### 2️⃣ REFACTOR

Pick ONE pattern and apply it:

**Pattern 1: Extract Method** - Break complex methods into focused ones
**Pattern 2: Guard Clauses** - Replace nested ifs with early returns
**Pattern 3: Strategy Pattern** - Replace conditionals with strategy classes
**Pattern 4: Match Expressions** - Use PHP 8.1+ match for cleaner logic
**Pattern 5: Functional Composition** - Use array_reduce/array_map/array_filter
**Pattern 6: Extract to Application Layer** - Eliminate duplication with Validators/Transformers/Factories (must comply with deptrac)

See [refactoring-strategies.md](../refactoring-strategies.md) for detailed examples.

**Keep changes**:

- ✅ Minimal and surgical
- ✅ One pattern at a time
- ✅ Self-documenting (NO comments needed)
- ✅ Test coverage maintained

### 3️⃣ TEST

**Run ALL quality checks** (must all pass):

```bash
make phpcsfixer    # Fix code style
make psalm         # Static analysis
make unit-tests    # Verify tests pass
make integration-tests  # Integration tests
make infection     # Mutation testing (100% MSI required)
```

If ANY fail → Fix immediately. Do not proceed.

### 4️⃣ COMMIT

```bash
git add .
git commit -m "refactor: reduce complexity in ClassName using [pattern]

- Complexity reduced from X to Y
- Applied [specific pattern]
- All tests passing, 100% coverage maintained"
```

### 5️⃣ VERIFY

**Every 5 classes**, run full CI:

```bash
make ci
```

**Must output**: `✅ CI checks successfully passed!`

If not → Fix issues before continuing.

## ⚡ Quick Patterns Reference

### Extract Method

```php
// BEFORE (complexity: 8)
public function validate($value): bool
{
    if (!$value || strlen($value) < 8 || strlen($value) > 64 ||
        !preg_match('/[A-Z]/', $value)) {
        return false;
    }
    return true;
}

// AFTER (complexity: 1 each)
public function validate($value): bool
{
    if (!$this->hasValidLength($value)) return false;
    if (!$this->hasUppercase($value)) return false;
    return true;
}

private function hasValidLength(?string $value): bool {
    return $value && strlen($value) >= 8 && strlen($value) <= 64;
}
```

### Guard Clauses

```php
// BEFORE (nested, complexity: 4)
public function calculate($value)
{
    if ($value !== null) {
        if ($value > 0) {
            if ($value < 100) return $value * 2;
        }
    }
    return 0;
}

// AFTER (flat, complexity: 3)
public function calculate($value)
{
    if ($value === null) return 0;
    if ($value <= 0) return 0;
    if ($value >= 100) return 0;
    return $value * 2;
}
```

### Strategy Pattern

```php
// BEFORE (complexity: 12)
public function process($type, $data)
{
    if ($type === 'email') { /* 15 lines */ }
    elseif ($type === 'phone') { /* 15 lines */ }
    elseif ($type === 'address') { /* 15 lines */ }
}

// AFTER (complexity: 2)
public function process($type, $data)
{
    return $this->strategyFactory->create($type)->process($data);
}
```

### Match Expressions (PHP 8.1+)

```php
// BEFORE (complexity: 5)
private function processValue($key, $value): mixed
{
    if ($this->shouldRemove($key, $value)) {
        return null;
    }
    if (!is_array($value)) {
        return $value;
    }
    return $this->processArray($value);
}

// AFTER (complexity: 3)
private function processValue($key, $value): mixed
{
    return match (true) {
        $this->shouldRemove($key, $value) => null,
        is_array($value) => $this->processArray($value),
        default => $value,
    };
}
```

## 📏 Success Criteria (Per Class)

After refactoring each class, verify:

- ✅ Average complexity < 5 per method
- ✅ All unit tests pass (100% coverage)
- ✅ Mutation testing: 100% MSI (0 escaped mutants)
- ✅ `make phpcsfixer && make psalm` both pass
- ✅ Code is self-explanatory without comments
- ✅ Clear commit message with metrics

## 🎯 Overall Targets

Your refactoring is complete when:

- **PHPInsights complexity**: ≥ 93%
- **Code quality**: 100%
- **Architecture**: 100%
- **Style**: 100%
- **Avg complexity per method**: < 5
- **Unit test coverage**: 100%
- **Mutation testing MSI**: 100%

## 🚫 STRICT RULES

### DO:

✅ Refactor ONE class at a time
✅ Keep changes minimal and surgical
✅ Write self-documenting code
✅ Maintain 100% test coverage
✅ Ensure 100% mutation score
✅ Run quality checks after EVERY change
✅ Commit frequently with clear messages

### DO NOT:

❌ Decrease quality thresholds in phpinsights.php
❌ Skip failing tests
❌ Add inline comments to explain complex code (refactor instead)
❌ Break architectural boundaries (Domain/Application/Infrastructure)
❌ Batch multiple refactorings before testing
❌ Proceed if any quality check fails

## 🔄 Iteration Strategy

1. **Start** with highest complexity class (CCN > 15)
2. **Refactor** ONE class completely
3. **Verify** all quality checks pass
4. **Commit** with descriptive message
5. **Re-analyze** to track progress:
   ```bash
   make analyze-complexity N=20
   ```
6. **Repeat** for next class

**After 5-10 classes**:

```bash
make ci  # Full CI to ensure no regressions
```

## 📊 Track Progress

```bash
# Before refactoring
make analyze-complexity-json N=20 > complexity-before.json

# After refactoring
make analyze-complexity-json N=20 > complexity-after.json

# Compare
diff complexity-before.json complexity-after.json
```

## 🆘 When Stuck

1. **Check patterns**: [refactoring-strategies.md](../refactoring-strategies.md)
2. **Understand metrics**: [complexity-metrics.md](complexity-metrics.md)
3. **Fix issues**: [troubleshooting.md](troubleshooting.md)
4. **Architecture**: See `CLAUDE.md` for hexagonal/DDD/CQRS

## ⏱️ Example Timeline

**Per class** (typical):

- 5 min: Analyze and identify pattern
- 10 min: Apply refactoring
- 5 min: Run tests and verify
- 2 min: Commit
- **Total**: ~20-25 minutes per class

**20 complex classes** = ~7-8 hours of focused work

## 🎉 Success Indicators

You'll know refactoring is working when:

- ✅ PHPInsights complexity trending up (93.5% → 94% → 94.5%)
- ✅ Classes dropping out of top 20 complexity list
- ✅ Average complexity decreasing
- ✅ All CI checks consistently green
- ✅ Code becomes easier to understand

---

**Full documentation**: See [SKILL.md](../SKILL.md) for complete guide.
