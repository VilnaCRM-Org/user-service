---
workflowType: implementation-readiness
project_name: BMAD FR/NFR Review Gate
author: Codex
date: 2026-05-17
revision: 1
---

# Implementation Readiness: BMAD FR/NFR Review Gate

## Decision

Ready for implementation as a repo-local Bash/Make/Markdown/Bats workflow.

## Readiness Checks

- Requirements are implementation-sized.
- Runtime does not depend on generated `_bmad/` assets.
- The design reuses the existing AI review loop instead of duplicating agent
  orchestration.
- Deterministic behavior can be tested with fake Codex CLIs.
- Documentation can route Codex, Claude, and human users to one Make target.

## QA Best Practices

- Test fail-closed paths before relying on happy-path PASS.
- Use fake agents in Bats for deterministic status output.
- Keep manual evidence explicit; never infer it from code changes.
- Require exact commands and outcomes in final reports.
- Publish BMAD PR comments and status checks from bounded local artifacts, not
  raw unbounded logs.
- Run `make ci` for production code changes.

## Verification Plan

```bash
bash -n scripts/ai-review-loop.sh
bash -n scripts/bmad-fr-nfr-review-gate.sh
bats tests/CLI/bats/make_ai_review_loop_tests.bats
bats tests/CLI/bats/make_bmalph_tests.bats
git diff --check
```

## Risks

| Risk                                                | Mitigation                                                                                  |
| --------------------------------------------------- | ------------------------------------------------------------------------------------------- |
| LLM reviewer gives shallow score                    | Require evidence per row and required PASS markers.                                         |
| Manual evidence absent                              | Fail closed and report required manual action.                                              |
| GitHub unavailable                                  | Gate cannot pass GitHub completion marker.                                                  |
| GitHub result publishing fails                      | BMAD wrapper exits nonzero when final PASS cannot publish the configured result.            |
| Make target is run without spec path                | Wrapper and Make target fail with a helpful message.                                        |
| Claude built-in `/review` bypasses prompt           | BMAD wrapper disables built-in Claude review for spec mode.                                 |
| Local tags shadow base branch names                 | Review loop resolves branch and remote refs before accepting explicit commit-ish refs.      |
| Generic AI review environment downgrades BMAD gates | BMAD wrapper overwrites threshold, NFR categories, and required markers with pinned values. |
