---
name: code-review
description: Systematically retrieve and address PR code review comments using make pr-comments. Enforces DDD architecture, code organization principles, and quality standards. Use when handling code review feedback, refactoring based on reviewer suggestions, or addressing PR comments.
---

# Code Review Workflow Skill

## Context (Input)

- PR has unresolved code review comments
- Need systematic approach to address feedback
- Ready to implement reviewer suggestions
- Need to verify DDD architecture compliance
- Need to ensure code organization best practices
- Need to maintain quality standards

## Task (Function)

Retrieve PR comments, categorize by type, verify architecture compliance, enforce code organization principles, and implement all changes systematically while maintaining 100% quality standards.

## Execution Steps

### Step 1: Get PR Comments

```bash
make pr-comments              # Auto-detect from current branch
make pr-comments PR=62       # Specify PR number
make pr-comments FORMAT=json  # JSON output
```

**Output**: All unresolved comments with file/line, author, timestamp, URL

### Step 2: Categorize Comments

| Type                   | Identifier                  | Priority | Action                               |
| ---------------------- | --------------------------- | -------- | ------------------------------------ |
| Committable Suggestion | Code block, "```suggestion" | Highest  | Apply immediately, commit separately |
| LLM Prompt             | "🤖 Prompt for AI Agents"   | High     | Execute prompt, implement changes    |
| Architecture Concern   | Class naming, file location | High     | Verify compliance (see Step 2.1)     |
| Question               | Ends with "?"               | Medium   | Answer inline or via code change     |
| General Feedback       | Discussion, recommendation  | Low      | Consider and improve                 |

#### Step 2.1: Architecture & Code Organization Verification

For any code changes (suggestions, prompts, or new files), **MANDATORY** verification using the `code-organization` skill:

**Core Principle** (from `code-organization` skill):

> **Directory X contains ONLY class type X**

**Verification Questions**:

1. ✅ Is the class following **"Directory X contains ONLY class type X"** principle?
   - Example: `UlidValidator` must be in `Validator/`, NOT in `Transformer/` or `Converter/`
2. ✅ Is the class name following the DDD naming pattern for its type?
3. ✅ Is the class in the correct directory according to its responsibility?
4. ✅ Does the class name reflect what it actually does?
5. ✅ Is the class in the correct layer (Domain/Application/Infrastructure)?
6. ✅ Does Domain layer have NO framework imports (Symfony/Doctrine/API Platform)?
7. ✅ Are variable names specific (not vague)?
   - ✅ `$typeConverter`, `$scalarResolver` (specific)
   - ❌ `$converter`, `$resolver` (too vague)
8. ✅ Are parameter names accurate (match actual types)?
   - ✅ `mixed $value` when accepts any type
   - ❌ `string $binary` when accepts mixed
9. ✅ No "Helper" or "Util" classes?
10. ✅ Factories used for complex object creation in production code?
11. ✅ Typed classes used instead of arrays for structured data?
12. ✅ Cross-cutting concerns (metrics, logging) in event subscribers, not handlers?

**For detailed organization rules, see the `code-organization` skill.**

**Quick Directory Reference**:

- `Converter/` → ONLY converters (type conversion)
- `Transformer/` → ONLY transformers (data transformation for DB/serialization)
- `Validator/` → ONLY validators
- `Builder/` → ONLY builders
- `Factory/` → ONLY factories
- `Resolver/` → ONLY resolvers
- `Formatter/` → ONLY formatters
- `Mapper/` → ONLY mappers
- `Processor/` → ONLY API Platform processors
- `Serializer/` → ONLY serializers/normalizers

**DDD Naming Patterns** (examples):

| Layer              | Type            | Pattern                         | Example                          |
| ------------------ | --------------- | ------------------------------- | -------------------------------- |
| **Domain**         | Entity          | `{EntityName}.php`              | `Customer.php`                   |
|                    | Value Object    | `{ConceptName}.php`             | `Email.php`                      |
|                    | Domain Event    | `{Entity}{PastTenseAction}.php` | `CustomerCreated.php`            |
| **Application**    | Command         | `{Action}{Entity}Command.php`   | `CreateCustomerCommand.php`      |
|                    | Command Handler | `{Action}{Entity}Handler.php`   | `CreateCustomerHandler.php`      |
|                    | Processor       | `{Action}{Entity}Processor.php` | `CreateCustomerProcessor.php`    |
|                    | Transformer     | `{From}To{To}Transformer.php`   | `CustomerToArrayTransformer.php` |
| **Infrastructure** | Repository      | `{Tech}{Entity}Repository.php`  | `MySQLCustomerRepository.php`    |

**For complete DDD patterns, see `implementing-ddd-architecture` skill.**

**Action on Violations**:

1. **Class in Wrong Directory**:

   ```bash
   # Move file to correct directory
   mv src/Path/WrongDir/ClassName.php src/Path/CorrectDir/ClassName.php

   # Update namespace in file
   # Update all imports across codebase
   grep -r "use.*WrongDir\\ClassName" src/ tests/
   ```

2. **Wrong Class Name**:

   - Rename class to follow naming conventions
   - Update all references to renamed class
   - Ensure name reflects actual functionality

3. **Vague Variable/Parameter Names**:

   ```php
   ❌ BEFORE: private UlidTypeConverter $converter;
   ✅ AFTER:  private UlidTypeConverter $typeConverter;

   ❌ BEFORE: private CustomerUpdateScalarResolver $resolver;
   ✅ AFTER:  private CustomerUpdateScalarResolver $scalarResolver;
   ```

4. **Quality Verification**:
   ```bash
   make phpcsfixer    # Fix code style
   make psalm         # Static analysis
   make deptrac       # Verify no layer violations
   make unit-tests    # Run tests
   ```

### Step 3: Apply Changes Systematically

#### For Committable Suggestions

1. Apply code change exactly as suggested
2. Commit with reference:

   ```bash
   git commit -m "Apply review suggestion: [brief description]

   Ref: [comment URL]"
   ```

#### For LLM Prompts

1. Copy prompt from comment
2. Execute as instructed
3. Verify output meets requirements
4. Commit with reference

#### For Architecture/Organization Concerns

1. Use `code-organization` skill to verify proper structure
2. Move/rename files if needed
3. Update namespaces and imports
4. Run `make deptrac` to verify compliance
5. Commit with reference

#### For Questions

1. Determine if code change or reply needed
2. If code: implement + commit
3. If reply: respond on GitHub

#### For Feedback

1. Evaluate suggestion merit
2. Implement if beneficial
3. Document reasoning if declined

### Step 4: Verify All Addressed

```bash
make pr-comments  # Should show zero unresolved comments
```

### Step 5: Run Quality Checks

**MANDATORY**: Run comprehensive CI checks after implementing all changes:

```bash
make ci  # Must output "✅ CI checks successfully passed!"
```

**If CI fails**, address issues systematically:

1. **Code Style Issues**: `make phpcsfixer`
2. **Static Analysis Errors**: `make psalm`
3. **Architecture Violations**: `make deptrac` (use `deptrac-fixer` skill if needed)
4. **Test Failures**: `make unit-tests` / `make integration-tests` (use `testing-workflow` skill)
5. **Mutation Testing**: `make infection` (use `testing-workflow` skill)
6. **Complexity Issues**: Use `complexity-management` skill

**Quality Standards Protection** (see `quality-standards` skill):

- **PHPInsights**: 100% quality, 93% src / 95% tests complexity, 100% architecture, 100% style
- **Test Coverage**: 100% (no decrease allowed)
- **Mutation Testing**: 100% MSI, 0 escaped mutants
- **Cyclomatic Complexity**: < 5 per class/method

**DO NOT** finish the task until `make ci` shows: `✅ CI checks successfully passed!`

## Comment Resolution Workflow

```mermaid
PR Comments → Categorize → Apply by Priority → Verify → Run CI → Done
```

## Constraints (Parameters)

**NEVER**:

- Skip committable suggestions
- Batch unrelated changes in one commit
- Ignore LLM prompts from reviewers
- Commit without running `make ci`
- Leave questions unanswered
- Accept organizational violations (see `code-organization` skill for rules)
- Allow Domain layer to import framework code
- Use vague variable names
- Create "Helper" or "Util" classes
- Decrease quality thresholds
- Allow cyclomatic complexity > 5 per method
- Finish task before `make ci` shows success message

**ALWAYS**:

- Apply suggestions exactly as provided
- Commit each suggestion separately with URL reference
- Verify code organization compliance (use `code-organization` skill)
- Verify architecture compliance (use `implementing-ddd-architecture` skill)
- Run `make deptrac` to ensure no layer violations
- Run `make ci` after implementing changes
- Address ALL quality standard violations before finishing
- Maintain 100% test coverage and 100% MSI (0 escaped mutants)
- Keep cyclomatic complexity < 5 per method
- Mark conversations resolved after addressing

## Format (Output)

**Commit Message Template**:

```
Apply review suggestion: [concise description]

[Optional: explanation if non-obvious]

Ref: https://github.com/owner/repo/pull/XX#discussion_rYYYYYYY
```

**Final Verification**:

```bash
✅ make pr-comments shows 0 unresolved
✅ make ci shows "CI checks successfully passed!"
```

## Verification Checklist

- [ ] All PR comments retrieved via `make pr-comments`
- [ ] Comments categorized by type (suggestion/prompt/architecture/question/feedback)
- [ ] **Code Organization verified** (use `code-organization` skill):
  - [ ] "Directory X contains ONLY class type X" principle enforced
  - [ ] Class names follow DDD naming patterns
  - [ ] Namespace matches directory structure
  - [ ] Variable names are specific
  - [ ] No Helper/Util classes
- [ ] **Architecture compliance verified** (use `implementing-ddd-architecture` skill):
  - [ ] Files in correct layer directories
  - [ ] Domain layer has NO framework imports
  - [ ] `make deptrac` passes (0 violations)
- [ ] Committable suggestions applied and committed separately
- [ ] LLM prompts executed and implemented
- [ ] Architecture/organization issues fixed
- [ ] Questions answered (code or reply)
- [ ] General feedback evaluated and addressed
- [ ] **Quality standards maintained** (see `quality-standards` skill):
  - [ ] Test coverage remains 100%
  - [ ] Mutation testing: 100% MSI (0 escaped mutants)
  - [ ] PHPInsights: 100% quality, 93% src / 95% tests complexity, 100% architecture, 100% style
  - [ ] Cyclomatic complexity < 5 per method
  - [ ] `make ci` shows "✅ CI checks successfully passed!"
- [ ] `make pr-comments` shows zero unresolved
- [ ] All conversations marked resolved on GitHub

## Quick Reference: When to Use Related Skills

During code review, you may need to invoke other skills:

| Issue                       | Skill to Use                    |
| --------------------------- | ------------------------------- |
| Class in wrong directory    | `code-organization`             |
| Vague naming                | `code-organization`             |
| DDD pattern violations      | `implementing-ddd-architecture` |
| Deptrac failures            | `deptrac-fixer`                 |
| Complexity too high         | `complexity-management`         |
| Test failures               | `testing-workflow`              |
| Quality standards questions | `quality-standards`             |

## Related Skills

- **code-organization**: Enforces "Directory X contains ONLY class type X" and naming conventions
- **implementing-ddd-architecture**: DDD patterns, layer structure, and boundaries
- **deptrac-fixer**: Fixes architectural boundary violations
- **complexity-management**: Reduces cyclomatic complexity
- **testing-workflow**: Test coverage and mutation testing
- **quality-standards**: Overall quality metrics and thresholds
- **ci-workflow**: Comprehensive CI checks

## Related Documentation

- Examples: `examples/organization-fixes.md` - Real-world organization fix examples
- Reference: `reference/quality-standards.md` - Quality standards integration details
