# Architecture: Self-Learning Skills

## Components

- `scripts/agent-learning/lib.sh`: shared shell helpers for store paths, stable ids, proxy wiring, timestamps, and JSON labels.
- `scripts/agent-learning/capture-codex-run.sh`: trace recorder and proxy env bridge.
- `scripts/agent-learning/record-intervention.sh`: intervention signal recorder.
- `scripts/agent-learning/build-episodes.sh`: deterministic JSONL episode builder.
- `scripts/agent-learning/propose-skill-update.sh`: deterministic prompt patch proposer.
- `tests/CLI/bats/make_agent_learning_tests.bats`: eval suite.
- `.github/workflows/agent-learning-evals.yml`: CI gate.

## Data Store

Runtime artifacts default to `.agent-learning/`:

- `traces/*.json`
- `interventions/*.json`
- `artifacts/*.stdout`
- `artifacts/*.stderr`
- `episodes.jsonl`
- `skill-update.patch`

The directory is gitignored because traces may contain prompts, output, or manual diffs.

## Integration

The implementation avoids new PHP runtime dependencies. It uses Bash and `jq`, matching existing repository CLI tooling and Bats tests.
