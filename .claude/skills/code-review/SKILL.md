---
name: code-review
description: Strict PR QA and code review workflow. Use when reviewing a PR, addressing PR comments, auditing FR/NFR coverage, finding bugs/security issues/flaky tests, scoring system quality attributes, or validating that automated tests and CI cover changed behavior.
---

# Code Review Workflow Skill

## Mission

Act as an independent senior code reviewer and QA reviewer, not only as a
comment resolver. A review is complete only when:

- Every functional requirement (FR) and non-functional requirement (NFR)
  implied by the PR is derived from evidence.
- Positive, negative, edge, security, performance, operability, compatibility,
  and regression test cases are generated for every FR and relevant NFR.
- Existing automated tests and CI checks are mapped to those cases.
- Missing coverage, bugs, security risks, flaky tests, and quality regressions
  are reported as findings.
- Every listed system quality attribute is scored as `0-5` or `N/A` with
  evidence, concrete improvement suggestions for weak scores, and reasons for
  `N/A`.
- After fixes, a repeated review finds no new issues.

Missing automated coverage for an FR/NFR is a review finding. Passing CI is
necessary but never sufficient by itself.

## Context (Input)

- PR has unresolved code review comments
- Need systematic approach to address feedback
- Ready to implement reviewer suggestions
- Need to maintain quality standards during review implementation
- Need strict BMAD-style FR/NFR, QA coverage, flaky-test, and system quality attribute review

## Task (Function)

Systematically retrieve, categorize, and address all PR code review comments
while independently reviewing the PR for bugs, vulnerabilities, missing
coverage, flaky tests, CI gaps, and strict FR/NFR evidence.

**Success Criteria**: `make pr-comments` shows 0 unresolved, strict FR/NFR and numeric quality-attribute scorecards have no unresolved blockers, every FR/NFR has automated test or CI evidence, every improvable quality attribute has a concrete suggestion or implemented fix, and `make ci` shows "✅ CI checks successfully passed!"

## Workflow Overview

```mermaid
Strict FR/NFR Gate → AI Review Loop → PR Comments → Categorize → Fix → Verify Coverage/CI → Report
```

## Quick Start

```bash
# 0. Run autonomous AI review + fix loop (Codex default, Claude optional)
cat .claude/skills/code-review/reference/fr-nfr-quality-gate.md
make ai-review-loop

# 1. Get comments
make pr-comments

# 2. Apply each suggestion/fix (one commit per comment)
git commit -m "Apply review suggestion: [description]

Ref: [comment URL]"

# 3. Verify all addressed
make pr-comments  # Should show 0 unresolved

# 4. Run CI
make ci  # Must show "✅ CI checks successfully passed!"
```

## Execution Steps

### Step -1: Run Strict BMAD FR/NFR Review Gate

Before addressing PR comments manually, run the strict review protocol in
[fr-nfr-quality-gate.md](reference/fr-nfr-quality-gate.md). This is mandatory
for every PR review, even when the user only asks to resolve comments.

The gate must:

- Post/update GitHub status context `BMAD FR/NFR Review Gate` at start and completion when a PR is available.
- Load PR diff, changed files, relevant specs/docs, existing tests, CI workflows, and current-head codebase graph evidence. Search for graph/impact artifacts and graph tooling; if graph evidence is missing or stale, run a current graph when tooling exists or mark `GRAPH_IMPACT_CONTEXT: MISSING|FAIL` and compensate with explicit current-head code-search impact evidence in the report.
- Extract every FR and NFR from PRD, stories, architecture, run summaries, issue text, PR description, and reviewer comments.
- Generate positive, negative, edge, security, performance, operability, compatibility, and regression test cases for every FR/NFR.
- Verify every generated case is covered by automated tests, CI checks, mutation tests, contract tests, or load tests. Manual evidence is supporting context only and cannot replace automation.
- Review flaky-test risk and require deterministic test data, isolated state, bounded timing, and no ordering dependency.
- Score every system quality attribute from the reference list as `0-5` or `N/A`, with evidence for each score and a reason for each `N/A`. Suggest mandatory improvements for every score below `5` when a practical PR-scope improvement exists.
- Fail the review when any applicable FR/NFR lacks automated or CI-backed coverage, any changed behavior is only manually checked, any critical quality attribute scores below the strict threshold, or any reviewer/comment/CI blocker remains.

### Step 0: Run Autonomous AI Review Loop

Before addressing PR comments manually, run the autonomous review loop:

```bash
make ai-review-loop
```

This executes `scripts/ai-review-loop.sh`, which:

1. Runs an AI review agent against the current diff (base: `main` by default)
2. If issues are found (`STATUS: FAIL`), runs a fix agent to auto-remediate
3. Verifies fixes with `make ci`
4. Repeats up to `AI_REVIEW_MAX_ITER` times (default: 3)
5. Fails closed when the limit is reached; final reporting remains blocked until
   a later repeated review finds no new actionable issues

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
AI_REVIEW_AGENTS=claude make ai-review-loop

# Limit to 1 iteration, custom base branch
AI_REVIEW_BASE=develop AI_REVIEW_MAX_ITER=1 make ai-review-loop

# Run both agents
AI_REVIEW_AGENTS=codex,claude make ai-review-loop
```

**Prompt templates**: `scripts/ai-review-prompts/review.md` (reviewer) and `scripts/ai-review-prompts/fix.md` (fixer).

### Step 1: Get PR Comments

```bash
make pr-comments              # Auto-detect from current branch
make pr-comments PR=62       # Specify PR number
make pr-comments FORMAT=json  # JSON output
```

**Output**: All unresolved comments with file/line, author, timestamp, URL

When reviewing a GitHub PR directly, also run:

```bash
gh pr view <PR> --json url,headRefOid,baseRefName,body,files,reviews,reviewThreads,statusCheckRollup
gh pr checks <PR>
```

Set `BMAD FR/NFR Review Gate` to `pending` on the current head SHA before
reviewing, then set it to `success` only after the strict gate and verification
pass. Set it to `failure` if actionable strict-gate findings remain.

### Step 2: Categorize Comments

| Type                   | Identifier                  | Priority | Action                               |
| ---------------------- | --------------------------- | -------- | ------------------------------------ |
| Committable Suggestion | Code block, "```suggestion" | Highest  | Apply immediately, commit separately |
| LLM Prompt             | "🤖 Prompt for AI Agents"   | High     | Execute prompt, implement changes    |
| Architecture Concern   | Class naming, file location | High     | Invoke appropriate skill             |
| Question               | Ends with "?"               | Medium   | Answer inline or via code change     |
| General Feedback       | Discussion, recommendation  | Low      | Consider and improve                 |

Merge explicit review comments with strict FR/NFR gate findings. Treat each
gate finding as actionable unless code, test, spec, or CI evidence proves it is
false.

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
make pr-comments  # Should show zero unresolved comments
```

Re-run the strict BMAD FR/NFR gate after fixes. It must find no new actionable
issues before final reporting.

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

### Step 7: Publish Review Result

When a GitHub PR exists:

1. Confirm the PR head SHA still matches the reviewed SHA.
2. Post the strict review report as a PR comment.
3. Update `BMAD FR/NFR Review Gate` on that same SHA to `pending` while expected remote checks are running, `failure` while any expected check or gate finding fails, and `success` only after the strict gate and all expected automated checks pass.
4. Clearly separate BMAD gate status from repository merge-policy facts. Do not wait for human/codeowner approvals before posting the BMAD result.

## Constraints (Parameters)

**NEVER**:

- Skip the autonomous AI review loop (`make ai-review-loop`) without justification
- Skip the strict BMAD FR/NFR review gate
- Mark a PR clean while any applicable FR/NFR lacks automated or CI-backed evidence
- Treat 100% line coverage as enough when positive/negative/edge cases are missing
- Ignore flaky-test risk, nondeterministic data, time/order dependency, race risk, or weak assertions
- Omit the system quality attribute scorecard
- Post a passing GitHub status before checking the current PR head SHA
- Skip committable suggestions
- Batch unrelated changes in one commit
- Ignore LLM prompts from reviewers
- Commit without running verification
- Leave questions unanswered
- Accept organizational violations (invoke `code-organization` skill)
- Accept architecture violations (invoke `implementing-ddd-architecture` skill)
- Add suppression/ignore annotations to "fix" review comments or CI failures
- Finish task before `make ci` shows success message

**ALWAYS**:

- Run the strict BMAD FR/NFR gate before and after fixes
- Derive a test-case matrix from all FRs and NFRs, then map each case to evidence
- Use current-head codebase graph/context evidence and code search to find downstream impact; do not treat graph discovery as optional
- Score system quality attributes numerically and require improvement suggestions for every improvable score below `5`
- Post GitHub status updates for PR review start and final result when a PR is available
- Run `make ai-review-loop` before manually addressing PR comments
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

- [ ] Strict BMAD FR/NFR gate run against the current PR head
- [ ] GitHub `BMAD FR/NFR Review Gate` status posted/updated for current head when PR exists
- [ ] FR/NFR list extracted from specs, docs, issues, PR description, and changed behavior
- [ ] Positive/negative/edge/security/performance/operability test matrix generated
- [ ] Each FR/NFR test case mapped to automated test, CI check, mutation/contract/load evidence; manual evidence recorded only as supporting context
- [ ] Missing coverage implemented or recorded as blocking suggestion
- [ ] Flaky-test risks checked and fixed or recorded as blocking
- [ ] System quality attribute scorecard completed for every listed attribute with `0-5` or `N/A`, evidence, `N/A` reasons, and improvement suggestions where applicable
- [ ] Current-head codebase graph impact reviewed, or `GRAPH_IMPACT_CONTEXT: MISSING|FAIL` recorded with compensating code-search impact evidence
- [ ] Autonomous AI review loop run via `make ai-review-loop` (or skipped with justification)
- [ ] All PR comments retrieved via `make pr-comments`
- [ ] Comments categorized by type (suggestion/prompt/architecture/question/feedback)
- [ ] Architecture verified using appropriate skills
- [ ] `make deptrac` passes (0 violations)
- [ ] Committable suggestions applied and committed separately
- [ ] LLM prompts executed and implemented
- [ ] Questions answered (code or reply)
- [ ] General feedback evaluated and addressed
- [ ] `make ci` shows "✅ CI checks successfully passed!"
- [ ] `make pr-comments` shows zero unresolved
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
- Reference: `reference/fr-nfr-quality-gate.md` - Strict QA review gate
