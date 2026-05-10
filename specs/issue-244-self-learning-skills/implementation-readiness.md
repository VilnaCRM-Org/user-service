# Implementation Readiness

## Ready

- The MVP stays inside existing repository tooling.
- No application runtime schema changes are required.
- CI can validate the workflow without external proxy credentials.

## Risks

- A live Agent Lightning proxy is environment-specific, so CI validates env propagation rather than making network calls.
- Captured traces can contain sensitive prompts or diffs; `.agent-learning/` is ignored and docs call out the local store.

## Verification

- `bash -n scripts/agent-learning/*.sh`
- `make agent-learning-evals`
- `npx --yes prettier@3.5.3 --check docs/self-learning.md specs/issue-244-self-learning-skills/*.md .github/workflows/agent-learning-evals.yml`
- `git diff --check`
