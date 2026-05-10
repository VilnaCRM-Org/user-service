# Self-Learning Skills

The self-learning workflow captures Codex runs, links human interventions to those runs, converts the data into deterministic training/eval episodes, and proposes skill prompt patches. It is designed to work without a live proxy in CI and with an Agent Lightning OpenAI-compatible proxy in developer workspaces.

## Runtime Configuration

Set one of these environment variables before running Codex through the capture wrapper:

```bash
export OPENAI_BASE_URL="http://localhost:9999/v1"
# or
export AGENT_LIGHTNING_BASE_URL="http://localhost:9999/v1"
```

`AGENT_LIGHTNING_BASE_URL` is a convenience alias. When it is set and `OPENAI_BASE_URL` is empty, the wrapper exports `OPENAI_BASE_URL` for the child Codex process.

Captured artifacts are written to `.agent-learning/` by default. Override with:

```bash
export AGENT_LEARNING_DIR=/tmp/user-service-agent-learning
```

## Capture One Run

```bash
scripts/agent-learning/capture-codex-run.sh \
  --skill .claude/skills/code-review/SKILL.md \
  --prompt "Review the current diff and report actionable issues" \
  -- codex exec "Review the current diff and report actionable issues"
```

The trace record contains the user input, resolved prompt, skill reference and version, command, proxy base URL, tool call/result placeholders, stdout/stderr artifacts, final output, error, and exit code.

## Record an Intervention

When a developer reprompts, manually edits generated output, captures a test failure, or retries a tool, record the learning signal against the trace id:

```bash
scripts/agent-learning/record-intervention.sh \
  --trace-id trace-example \
  --type reprompt \
  --summary "The agent ignored the apply_patch editing rule" \
  --reprompt "Use apply_patch for manual file edits" \
  --labels review,editing
```

For manual edits, pass a diff file:

```bash
git diff > /tmp/manual-fix.diff
scripts/agent-learning/record-intervention.sh \
  --trace-id trace-example \
  --type manual-diff \
  --summary "Manual fix added missing verification evidence" \
  --diff-file /tmp/manual-fix.diff
```

## Build Episodes

```bash
scripts/agent-learning/build-episodes.sh
```

The command writes `.agent-learning/episodes.jsonl`. Each line has a stable episode id, source trace/signal ids, skill reference, input, bad output, desired output, labels, and intervention metadata.

## Propose a Skill Patch

```bash
scripts/agent-learning/propose-skill-update.sh \
  --skill-file .claude/skills/code-review/SKILL.md \
  --episodes .agent-learning/episodes.jsonl \
  --output .agent-learning/skill-update.patch
```

Review the patch, run the eval gate, then apply it intentionally:

```bash
scripts/agent-learning/propose-skill-update.sh \
  --skill-file .claude/skills/code-review/SKILL.md \
  --episodes .agent-learning/episodes.jsonl \
  --apply
```

## Eval Gate

Run the deterministic eval suite before proposing or merging skill changes:

```bash
make agent-learning-evals
```

CI runs the same target in the `Agent learning evals` workflow for changes to the learning scripts, related docs, workflows, and skill files. The Bats suite checks proxy env propagation, trace schema completeness, intervention linkage, deterministic JSONL generation, and non-empty optimizer patches.

## Import Old Conversation History

For historical conversations, create one trace per relevant task using `capture-codex-run.sh --trace-id` with the original prompt and a replay command such as `printf '%s\n' "$saved_output"`. Then add one or more `record-intervention.sh` calls for the reprompts, manual diffs, or test failures that corrected the output. This keeps imported data in the same trace/signal/episode format as new proxy-captured runs.
