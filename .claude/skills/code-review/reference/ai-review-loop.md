# AI Review Loop Reference

Detailed configuration for `make ai-review-loop`, which executes `scripts/ai-review-loop.sh`.

## What it does

1. Runs an AI review agent against the current diff (base: `main` by default)
2. If issues are found (`STATUS: FAIL`), runs a fix agent to auto-remediate
3. Verifies fixes with `make ci`
4. Repeats up to `AI_REVIEW_MAX_ITER` times (default: 3)

## Configuration (environment overrides)

| Variable               | Default         | Description                         |
| ---------------------- | --------------- | ----------------------------------- |
| `AI_REVIEW_AGENTS`     | `codex`         | Agent(s) to use (`codex`, `claude`) |
| `AI_REVIEW_BASE`       | `main`          | Base branch for diff comparison     |
| `AI_REVIEW_MAX_ITER`   | `3`             | Max review/fix iterations (0=∞)     |
| `AI_REVIEW_VERIFY_CMD` | `make ci`       | Verification command after each fix |
| `AI_REVIEW_LOG_DIR`    | `var/ai-review` | Directory for review/fix logs       |

## Examples

```bash
# Use Claude instead of Codex
AI_REVIEW_AGENTS=claude make ai-review-loop

# Limit to 1 iteration, custom base branch
AI_REVIEW_BASE=develop AI_REVIEW_MAX_ITER=1 make ai-review-loop

# Run both agents
AI_REVIEW_AGENTS=codex,claude make ai-review-loop
```

## Prompt templates

- Reviewer: `scripts/ai-review-prompts/review.md`
- Fixer: `scripts/ai-review-prompts/fix.md`
