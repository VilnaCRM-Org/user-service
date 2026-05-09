---
title: 'Issue #239: Post-Implementation Skill Sweep'
slug: 'issue-239-skill-sweep'
issue: 'https://github.com/VilnaCRM-Org/user-service/issues/239'
created: '2026-05-09'
status: 'ready-for-development'
---

# Issue #239: Post-Implementation Skill Sweep

## Problem

`AGENTS.md` already requires AI agents to use the `.claude/skills` system and execute every
skill for new features. Issue #239 asks for the post-implementation behavior to be explicit:
after generating or modifying feature code, the agent must scan the skills directory, apply every
skill to the changed feature, find violations, and refactor those violations before marking the
task complete.

## Goals

- Add a clear post-implementation protocol to `AGENTS.md`.
- Require scanning `.claude/skills` after implementation.
- Require iterating every `SKILL.md`, including skills that appear unrelated.
- Require explicit violation detection and immediate self-correction/refactoring.
- Preserve the existing mandatory skill gate and avoid duplicating broad skill lists.

## Non-Goals

- No runtime application code changes.
- No changes to CI workflow behavior.
- No new skill files.
- No changes to API, database, or deployment configuration.

## Proposed Change

Update the existing "Mandatory New Feature Verification Gate (ALL Skills)" section in `AGENTS.md`
with a dedicated "Post-Implementation Protocol: Skill Sweep" subsection. This keeps the guidance
near the existing mandatory gate while satisfying the issue's requested wording.

## Acceptance Mapping

| Issue requirement                                         | Implementation target                         |
| --------------------------------------------------------- | --------------------------------------------- |
| `AGENTS.md` has a clear mandatory skill review step       | Add Skill Sweep subsection under the gate     |
| All skills in `.claude/skills` must be checked            | Require scanning and iterating every skill    |
| Agent must find and refactor violations before completion | Require violation log and immediate refactors |

## Validation

- `git diff --check`
- Markdown/formatting inspection of `AGENTS.md`
- Confirm references to `.claude/skills`, all skills, violations, and refactoring are present

## Performance Impact

None. This is a documentation-only workflow clarification.
