# PRD: Self-Learning Skill Improvement

## Requirements

1. A developer can route captured Codex runs through an OpenAI-compatible Agent Lightning proxy.
2. Each captured run stores prompt, skill reference, skill version, command, proxy URL, tool call/result placeholders, stdout/stderr artifacts, final output, error, and exit code.
3. A developer can record reprompt, manual diff, test failure, and tool retry interventions against a trace.
4. Episode generation is deterministic for fixed trace and intervention inputs.
5. The optimizer command outputs a non-empty unified diff for a target skill file.
6. CI runs an eval gate when learning scripts, docs, workflows, or skill files change.
7. Documentation explains capture, intervention, episode, optimizer, eval, and historical import flows.

## Acceptance Mapping

- Proxy routing: `capture-codex-run.sh` and `verify-gh-codex.sh` propagate `AGENT_LIGHTNING_BASE_URL` to `OPENAI_BASE_URL`.
- Trace completeness: Bats validates required trace fields.
- Intervention capture: Bats validates linked signal records.
- Episode generation: Bats validates deterministic JSONL output.
- Optimization: Bats validates a concrete patch.
- Eval gating: `.github/workflows/agent-learning-evals.yml` runs `make agent-learning-evals`.
- Docs: `docs/self-learning.md` contains the reproducible workflow.
