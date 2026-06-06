---
name: code-review
description: Systematically retrieve and address PR code review comments using make pr-comments. Use when handling code review feedback or addressing PR comments.
---

# Code Review Workflow Skill

## Context (Input)

- PR has unresolved code review comments
- Need systematic approach to address feedback
- Ready to implement reviewer suggestions
- Need to maintain quality standards during review implementation

## Task (Function)

Systematically retrieve, categorize, and address all PR code review comments while maintaining quality standards and PR readiness.

**Success Criteria**:

- `make pr-comments PR=<number>` shows 0 unresolved review comments
- `make ci` shows "✅ CI checks successfully passed!"
- `gh pr checks <number>` shows all required checks passing on the latest pushed head
- `gh pr view <number> --json mergeStateStatus,mergeable,reviewDecision,isDraft` shows the PR is not draft, not conflicting, and has no outstanding requested changes

## Workflow Overview

```mermaid
AI Review Loop → PR Comments → Categorize → Apply by Priority → Verify → Run CI → Done
```

## Quick Start

```bash
# 0. Run autonomous AI review + fix loop against the PR base
PR=<number>
BASE_REF="$(gh pr view "$PR" --json baseRefName --jq .baseRefName)"
git fetch origin "$BASE_REF"
AI_REVIEW_BASE="origin/$BASE_REF" make ai-review-loop

# 1. Get comments
make pr-comments PR="$PR"

# 2. Apply each suggestion/fix (one commit per comment)
git commit -m "Apply review suggestion: [description]

Ref: [comment URL]"

# 3. Verify all addressed
make pr-comments PR="$PR"  # Should show 0 unresolved

# 4. Run CI
make ci  # Must show "✅ CI checks successfully passed!"

# 5. Verify GitHub PR readiness after pushing
gh pr checks "$PR"
gh pr view "$PR" --json mergeStateStatus,mergeable,reviewDecision,isDraft
```

## Execution Steps

### Step 0: Run Autonomous AI Review Loop

Before addressing PR comments manually, fetch the PR base and run the autonomous review loop against that base:

```bash
PR=<number>
BASE_REF="$(gh pr view "$PR" --json baseRefName --jq .baseRefName)"
git fetch origin "$BASE_REF"
AI_REVIEW_BASE="origin/$BASE_REF" make ai-review-loop
```

This executes `scripts/ai-review-loop.sh`, which:

1. Runs an AI review agent against the current diff (base: `AI_REVIEW_BASE`, or `main` by default)
2. If issues are found (`STATUS: FAIL`), runs a fix agent to auto-remediate
3. Verifies fixes with `make ci`
4. Repeats up to `AI_REVIEW_MAX_ITER` times (default: 3)

**Configuration** (all overridable via environment):

| Variable               | Default         | Description                         |
| ---------------------- | --------------- | ----------------------------------- |
| `AI_REVIEW_AGENTS`     | `codex`         | Agent(s) to use (`codex`, `claude`) |
| `AI_REVIEW_BASE`       | `main`          | Base branch for diff comparison     |
| `AI_REVIEW_MAX_ITER`   | `3`             | Max review/fix iterations (0=∞)     |
| `AI_REVIEW_VERIFY_CMD` | `make ci`       | Verification command after each fix |
| `AI_REVIEW_LOG_DIR`    | `var/ai-review` | Directory for review/fix logs       |

**Examples**:

```bash
# Use Claude instead of Codex
AI_REVIEW_BASE="origin/$BASE_REF" AI_REVIEW_AGENTS=claude make ai-review-loop

# Limit to 1 iteration, custom base branch
AI_REVIEW_BASE=develop AI_REVIEW_MAX_ITER=1 make ai-review-loop

# Run both agents
AI_REVIEW_BASE="origin/$BASE_REF" AI_REVIEW_AGENTS=codex,claude make ai-review-loop
```

**Prompt templates**: `scripts/ai-review-prompts/review.md` (reviewer) and `scripts/ai-review-prompts/fix.md` (fixer).

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
| Architecture Concern   | Class naming, file location | High     | Invoke appropriate skill             |
| Question               | Ends with "?"               | Medium   | Answer inline or via code change     |
| General Feedback       | Discussion, recommendation  | Low      | Consider and improve                 |
| Resolved/Stale         | Outdated or already fixed   | None     | Do not change code; record reason    |

### Step 3: Verify Architecture & Organization

For code changes (suggestions, prompts, new files), invoke verification skills:

| Concern Type           | Skill to Invoke                    |
| ---------------------- | ---------------------------------- |
| Class placement/naming | `code-organization`                |
| DDD patterns           | `implementing-ddd-architecture`    |
| Layer violations       | `deptrac-fixer` (if deptrac fails) |

**Quick verification**: Run `make phpcsfixer && make psalm && make deptrac && make unit-tests`

### Step 4: Apply Changes Systematically

#### For Committable Suggestions

1. Verify the suggestion still applies to current code
2. Apply the suggestion exactly when it is still valid and compatible with repository rules
3. If the suggestion is stale, implement the current equivalent fix or record why no change is needed
4. Commit with reference:

   ```bash
   git commit -m "Apply review suggestion: [brief description]

   Ref: [comment URL]"
   ```

#### For LLM Prompts

1. Copy prompt from comment
2. Verify every finding against current code before changing files
3. Execute still-valid instructions
4. Skip stale, duplicate, or contradicted findings with a brief reason
5. Verify output meets requirements
6. Commit with reference

#### For Architecture/Organization Concerns

1. Invoke appropriate skill (`code-organization` or `implementing-ddd-architecture`)
2. Implement recommended changes
3. Verify: `make phpcsfixer && make psalm && make deptrac && make unit-tests`
4. Commit with reference

#### For Questions

1. Determine if code change or reply needed
2. If code: implement + commit
3. If reply: respond on GitHub

#### For General Feedback

1. Evaluate suggestion merit
2. Implement if beneficial
3. Document reasoning if declined

### Step 5: Verify All Addressed

```bash
make pr-comments PR=<number>  # Should show zero unresolved comments
```

If unresolved comments remain, repeat categorization and implementation. If a remaining thread is stale, duplicate, or answer-only, respond or resolve it according to the review workflow before continuing.

### Step 6: Run Quality Checks

**MANDATORY**: Run comprehensive CI checks after implementing all changes:

```bash
make ci  # Must output "✅ CI checks successfully passed!"
```

**If CI fails**, invoke appropriate skill:

| Failure Type            | Skill to Use            |
| ----------------------- | ----------------------- |
| Architecture violations | `deptrac-fixer`         |
| Complexity issues       | `complexity-management` |
| Test failures           | `testing-workflow`      |
| Mutation testing issues | `testing-workflow`      |
| Code style              | Run `make phpcsfixer`   |
| Static analysis         | Run `make psalm`        |

**DO NOT** finish the task until `make ci` shows: `✅ CI checks successfully passed!`

### Step 7: Verify GitHub PR Readiness

After pushing changes, verify the actual PR state:

```bash
gh pr checks <number>
gh pr view <number> --json mergeStateStatus,mergeable,reviewDecision,isDraft
```

Required state:

- All required checks pass on the latest pushed head
- `isDraft` is `false`
- `mergeStateStatus` is not `DIRTY` or `UNKNOWN`
- `mergeable` is not `CONFLICTING`
- `reviewDecision` is not `CHANGES_REQUESTED`

## Constraints (Parameters)

**NEVER**:

- Skip the autonomous AI review loop (`make ai-review-loop`) without justification
- Skip committable suggestions
- Batch unrelated changes in one commit
- Ignore LLM prompts from reviewers
- Apply stale or invalid review suggestions blindly
- Commit without running verification
- Leave questions unanswered
- Accept organizational violations (invoke `code-organization` skill)
- Accept architecture violations (invoke `implementing-ddd-architecture` skill)
- Add suppression/ignore annotations to "fix" review comments or CI failures
- Finish task before `make ci` shows success message
- Finish while GitHub reports failing checks, conflicts, draft status, or requested changes

**ALWAYS**:

- Run `make ai-review-loop` before manually addressing PR comments
- Verify review findings against current code before applying them
- Commit each suggestion separately with URL reference
- Invoke `code-organization` skill for structural issues
- Invoke `implementing-ddd-architecture` skill for DDD violations
- Run `make ci` after implementing all changes
- Check `gh pr checks` and `gh pr view` after pushing
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
✅ gh pr checks shows all required checks passing
✅ gh pr view shows no conflicts, no draft status, and no requested changes
```

## Verification Checklist

- [ ] Autonomous AI review loop run via `make ai-review-loop` (or skipped with justification)
- [ ] All PR comments retrieved via `make pr-comments`
- [ ] Comments categorized by type (suggestion/prompt/architecture/question/feedback)
- [ ] Stale or duplicate comments recorded with concrete reasons
- [ ] Architecture verified using appropriate skills
- [ ] `make deptrac` passes (0 violations)
- [ ] Committable suggestions applied and committed separately
- [ ] LLM prompts executed and implemented
- [ ] Questions answered (code or reply)
- [ ] General feedback evaluated and addressed
- [ ] `make ci` shows "✅ CI checks successfully passed!"
- [ ] `make pr-comments` shows zero unresolved
- [ ] `gh pr checks` shows all required checks passing on the latest pushed head
- [ ] `gh pr view` shows no conflicts, no draft status, and no requested changes
- [ ] All conversations marked resolved on GitHub

## Quick Reference: When to Use Related Skills

During code review, you may need to invoke other skills:

| Issue                    | Skill to Use                    |
| ------------------------ | ------------------------------- |
| Class in wrong directory | `code-organization`             |
| Vague naming             | `code-organization`             |
| DDD pattern violations   | `implementing-ddd-architecture` |
| Deptrac failures         | `deptrac-fixer`                 |
| Complexity too high      | `complexity-management`         |
| Test failures            | `testing-workflow`              |
| Quality standards        | `quality-standards`             |

## Related Skills

- **code-organization**: Enforces "Directory X contains ONLY class type X" and naming conventions
- **implementing-ddd-architecture**: DDD patterns, layer structure, and boundaries
- **deptrac-fixer**: Fixes architectural boundary violations
- **complexity-management**: Reduces cyclomatic complexity
- **testing-workflow**: Test coverage and mutation testing
- **quality-standards**: Overall quality metrics and thresholds
- **ci-workflow**: Comprehensive CI checks

## Related Documentation

- Reference: `reference/quality-standards.md` - Quality standards integration details
- Organizational fix patterns: `../code-organization/SKILL.md`
