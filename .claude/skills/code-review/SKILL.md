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

For any code changes (suggestions, prompts, or new files), **MANDATORY** verification:

**Use the `code-organization` skill** to verify:
- Directory X contains ONLY class type X principle
- DDD naming patterns
- Correct layer placement
- No framework imports in Domain layer
- Specific variable/parameter names (not vague)
- No Helper/Util classes

**Use the `implementing-ddd-architecture` skill** for:
- DDD patterns and layer boundaries
- Entity, value object, aggregate structure
- Command/handler patterns

**Quick verification checklist** (see skills above for complete rules):
- ✅ Class in correct directory for its type?
- ✅ Class name follows DDD naming pattern?
- ✅ Namespace matches directory structure?
- ✅ Variable names specific (not vague)?
- ✅ No organizational anti-patterns?

**On violations**: Consult `code-organization` skill for fix patterns, then run:
```bash
make phpcsfixer && make psalm && make deptrac && make unit-tests
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

1. Invoke `code-organization` skill to identify and fix issues
2. Implement recommended changes (move/rename/refactor)
3. Verify: `make phpcsfixer && make psalm && make deptrac && make unit-tests`
4. Commit with reference to review comment

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

**If CI fails**, invoke appropriate skill:

| Failure Type              | Skill to Use                |
| ------------------------- | --------------------------- |
| Architecture violations   | `deptrac-fixer`             |
| Complexity issues         | `complexity-management`     |
| Test failures             | `testing-workflow`          |
| Mutation testing issues   | `testing-workflow`          |
| Code style                | Run `make phpcsfixer`       |
| Static analysis           | Run `make psalm`            |

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
- Commit without running verification
- Leave questions unanswered
- Accept organizational violations (invoke `code-organization` skill)
- Accept architecture violations (invoke `implementing-ddd-architecture` skill)
- Finish task before `make ci` shows success message

**ALWAYS**:

- Apply suggestions exactly as provided
- Commit each suggestion separately with URL reference
- Invoke `code-organization` skill for structural issues
- Invoke `implementing-ddd-architecture` skill for DDD violations
- Run `make ci` after implementing all changes
- Address ALL CI failures before finishing
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
- [ ] **Organization verified**: Invoke `code-organization` skill for any structural changes
- [ ] **Architecture verified**: Invoke `implementing-ddd-architecture` skill for DDD patterns
- [ ] **Layer boundaries verified**: `make deptrac` passes (0 violations)
- [ ] Committable suggestions applied and committed separately
- [ ] LLM prompts executed and implemented
- [ ] Questions answered (code or reply)
- [ ] General feedback evaluated and addressed
- [ ] **Quality verified**: `make ci` shows "✅ CI checks successfully passed!"
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
