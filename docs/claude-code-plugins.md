# Claude Code Plugins

Recommended Claude Code CLI plugin set for working on this service. All plugins
come from the official Anthropic-managed marketplace (`claude-plugins-official`),
which is preconfigured in Claude Code — no extra marketplace setup is needed.

## Prerequisites

```bash
npm install -g intelephense   # PHP language server used by php-lsp
uv tool install semgrep       # SAST engine used by the semgrep plugin
```

`uv`/`uvx` (for the serena semantic-code server) and Node.js (for the
context7 and MongoDB MCP servers) must also be available on the host.

## Installation

```bash
for plugin in superpowers feature-dev php-lsp serena context7 \
  pr-review-toolkit security-guidance semgrep commit-commands \
  mongodb claude-md-management skill-creator hookify; do
  claude plugin install "$plugin@claude-plugins-official"
done
```

## What Each Plugin Covers

| SDLC Phase | Plugin | Purpose |
| --- | --- | --- |
| Planning | `superpowers` | Brainstorming, plan writing/execution, red/green TDD, systematic debugging |
| Planning | `feature-dev` | Guided feature workflow with explorer, architect, and reviewer agents |
| Coding | `php-lsp` | Intelephense-based PHP code intelligence and diagnostics |
| Coding | `serena` | Semantic code navigation and refactoring (token-efficient retrieval) |
| Coding | `context7` | Version-accurate library docs lookup (Symfony, API Platform, etc.) |
| Review | `pr-review-toolkit` | `/review-pr` plus agents for tests, types, silent failures, comments |
| Security | `security-guidance` | Pattern warnings on edits and LLM diff review before stopping |
| Security | `semgrep` | Real-time SAST scanning on writes/edits via the Semgrep MCP server |
| Git/CI | `commit-commands` | `/commit`, `/commit-push-pr`, and branch cleanup commands |
| Database | `mongodb` | Official MongoDB skills: schema design, query optimization, MCP server |
| Docs | `claude-md-management` | Audits and improves `CLAUDE.md` project memory |
| Tooling | `skill-creator` | Authoring and benchmarking the skills under `.claude/skills/` |
| Tooling | `hookify` | Builds custom hooks from natural-language rules |

Built-in Claude Code commands already cover general code review
(`/code-review`, `/security-review`, `/simplify`), so the standalone
`code-review` and `code-simplifier` plugins are intentionally omitted.
