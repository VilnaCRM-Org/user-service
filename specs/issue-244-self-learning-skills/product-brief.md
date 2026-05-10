# Product Brief: Self-Learning Skills

## Outcome

Developers can turn agent failures into structured learning episodes and proposed skill updates without leaving the repository workflow.

## Users

- Developers maintaining Codex and Claude skill prompts.
- Platform maintainers reviewing autonomous-agent changes.
- ML/agent engineers who need reproducible failure episodes.

## MVP Scope

- Capture traces for Codex-compatible runs.
- Record reprompts, manual diffs, test failures, and tool retries.
- Build stable JSONL episodes.
- Produce a concrete skill prompt patch.
- Gate the workflow with CI evals.

## Non-Goals

- Hosting Agent Lightning.
- Training or fine-tuning a model inside this repository.
- Automatically merging skill updates.
