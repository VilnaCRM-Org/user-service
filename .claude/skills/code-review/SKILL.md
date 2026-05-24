---
name: code-review
description: Fetch unresolved PR review comments, categorize by type (committable suggestions, LLM prompts, architecture concerns, questions, general feedback), apply changes, and verify with CI. Use when addressing pull request feedback, reviewer comments, requested changes, GitHub review suggestions, resolving review threads, or running `make pr-comments` / `make ai-review-loop`.
---

# Code Review Workflow Skill

## Context (Input)

- PR has unresolved code review comments
- Quality standards must hold while addressing feedback

## Task (Function)

Systematically retrieve, categorize, and address all PR code review comments while maintaining quality standards.

**Success Criteria**: `make pr-comments` shows 0 unresolved AND `make ci` shows "✅ CI checks successfully passed!"

## Quick Start

```bash
make ai-review-loop                                    # 0. Autonomous review + fix loop
make pr-comments                                       # 1. Get unresolved comments
# 2. Apply each fix and commit per comment (template below)
make pr-comments                                       # 3. Verify 0 unresolved
make ci                                                # 4. CI must pass
```

## Skill Routing

When a comment (or a CI failure during verification) maps to one of the concerns below, invoke the matching skill instead of fixing ad-hoc.

| Concern                             | Skill                           |
| ----------------------------------- | ------------------------------- |
| Class placement / naming            | `code-organization`             |
| DDD pattern violations              | `implementing-ddd-architecture` |
| Deptrac / layer violations          | `deptrac-fixer`                 |
| Cyclomatic complexity / PHPInsights | `complexity-management`         |
| Test failures, coverage, mutation   | `testing-workflow`              |
| Quality threshold questions         | `quality-standards`             |
| Comprehensive CI checks             | `ci-workflow`                   |

### Tool-Driven Fixes (no skill — run the command)

Some failure types have no dedicated skill because the tool itself is the remediation. Do NOT manually patch the underlying code; run the command.

| Concern                              | Command            |
| ------------------------------------ | ------------------ |
| Static analysis errors (Psalm types) | `make psalm`       |
| Code style violations                | `make phpcsfixer`  |

## Execution Steps

### Step 0: Run Autonomous AI Review Loop

```bash
make ai-review-loop
```

Configuration, alternative agents, and prompt template paths: [reference/ai-review-loop.md](reference/ai-review-loop.md).

### Step 1: Get PR Comments

```bash
make pr-comments              # Auto-detect from current branch
make pr-comments PR=62        # Specify PR number
make pr-comments FORMAT=json  # JSON output
```

### Step 2: Categorize Comments

| Type                   | Identifier                  | Priority | Action                                     |
| ---------------------- | --------------------------- | -------- | ------------------------------------------ |
| Committable Suggestion | Code block, "```suggestion" | Highest  | Apply verbatim, commit separately          |
| LLM Prompt             | "🤖 Prompt for AI Agents"   | High     | Execute prompt, implement, commit          |
| Architecture Concern   | Class naming, file location | High     | Route through Skill Routing                |
| Question               | Ends with "?"               | Medium   | Answer inline or via code change           |
| General Feedback       | Discussion, recommendation  | Low      | Apply if beneficial                        |

Per-type handling: [reference/comment-types.md](reference/comment-types.md).

### Step 3: Apply Changes Systematically

Process comments in priority order from Step 2. For each comment, route through **Skill Routing** when applicable; otherwise apply the change and commit using the template in [reference/comment-types.md](reference/comment-types.md).

Local verification before pushing: `make ci`.

### Step 4: Verify Complete

```bash
make pr-comments  # Must show zero unresolved
make ci           # Must show "✅ CI checks successfully passed!"
```

If `make ci` fails, route the failure type through **Skill Routing**. Do not finish until both commands succeed.

## Constraints (Parameters)

**NEVER**:

- Skip `make ai-review-loop` without justification
- Batch unrelated changes in one commit
- Add suppression / ignore annotations to "fix" comments or CI failures
- Finish before `make ci` succeeds

**ALWAYS**:

- Route concerns through **Skill Routing** rather than fixing ad-hoc
- Include the comment URL in every commit message
- Mark conversations resolved on GitHub after addressing

## Format (Output)

**Commit Message Template** (Conventional Commits; the `(#PR)` suffix is appended by GitHub on squash-merge):

```
<type>(#<issue>): <imperative description of what changed>

[Optional: why, if non-obvious]

Ref: https://github.com/owner/repo/pull/XX#discussion_rYYYYYYY
```

- `<type>` ∈ `feat | fix | refactor | docs | perf | test | chore`
- `(#<issue>)` scope is optional — include the GitHub issue number when there is one
- Subject is lowercase, imperative, describes the change (not "apply review suggestion")

**Example**:

```
fix(#230): null-check user lookup before command dispatch

Ref: https://github.com/VilnaCRM-Org/user-service/pull/285#discussion_r1234567890
```

## Verification Checklist

- [ ] `make ai-review-loop` run (or skipped with justification)
- [ ] Comments retrieved and categorized per Step 2
- [ ] Architecture concerns routed through matching skills
- [ ] Suggestions applied verbatim, one commit per comment with URL ref
- [ ] `make pr-comments` shows zero unresolved
- [ ] `make ci` shows "✅ CI checks successfully passed!"
- [ ] All conversations marked resolved on GitHub

## Related Documentation

- [reference/ai-review-loop.md](reference/ai-review-loop.md) — Configuration for `make ai-review-loop`
- [reference/comment-types.md](reference/comment-types.md) — Per-type execution details
- [reference/quality-standards.md](reference/quality-standards.md) — Quality standards integration
