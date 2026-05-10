# Epics

## Epic 1: Proxy-Compatible Capture

- Add optional `AGENT_LIGHTNING_BASE_URL` to `OPENAI_BASE_URL` wiring.
- Capture complete trace metadata and artifacts.

## Epic 2: Learning Signals

- Record reprompts, manual diffs, test failures, and tool retries.
- Link every intervention to a source trace.

## Epic 3: Episode Builder and Optimizer

- Convert trace and signal JSON into stable JSONL.
- Generate a concrete skill prompt patch from episodes.

## Epic 4: Eval Gate and Docs

- Add Bats coverage for the full data flow.
- Add a dedicated CI workflow.
- Document local and proxy-backed operation.
