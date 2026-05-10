# Research: Self-Learning Skill Improvement

## Problem

Skill prompts improve only when a developer manually remembers the failure pattern and edits a skill file. Reprompts, manual diffs, and test failures are not captured as structured data, so the repository has no durable loop from agent failure to prompt improvement.

## Current Surface

- Skills live under `.claude/skills/` and `.agents/skills/`.
- Codex workspace verification already exists in `scripts/local-coder/verify-gh-codex.sh`.
- Shell workflow coverage is handled through Bats in `tests/CLI/bats/`.
- Existing `.gitignore` already excludes local generated agent artifacts such as `_bmad/`.

## Decision

Implement an MVP as repo-local shell commands rather than a new service. The commands expose the same data flow required by Agent Lightning:

1. Optional OpenAI-compatible proxy routing with `OPENAI_BASE_URL` or `AGENT_LIGHTNING_BASE_URL`.
2. JSON trace capture for Codex or replayed runs.
3. JSON intervention capture linked to traces.
4. Deterministic JSONL episode generation.
5. Deterministic prompt patch proposal for skill markdown files.
6. Bats eval gate in CI.

This keeps the feature reproducible in CI without requiring a live external proxy.
