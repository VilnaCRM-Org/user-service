---
title: Clean Architecture Skill for LLM Modules
slug: issue-226-clean-architecture-llm-skill
issue: 226
issue_url: https://github.com/VilnaCRM-Org/user-service/issues/226
created: 2026-05-09
status: ready-for-development
stepsCompleted: [1, 2, 3, 4]
tech_stack: Markdown skill documentation, Claude/OpenAI agent skill workflow, BMALPH/BMAD planning
files_to_modify:
  - .claude/skills/clean-architecture-llm/SKILL.md
  - .claude/skills/clean-architecture-llm/examples/llm-module-template.md
  - .claude/skills/clean-architecture-llm/reference/review-checklist.md
  - .claude/skills/README.md
  - .claude/skills/SKILL-DECISION-GUIDE.md
  - .claude/skills/AI-AGENT-GUIDE.md
  - docs/onboarding.md
  - README.md
code_patterns:
  - Repository skill format with frontmatter name and description
  - Cross-agent skill routing through README, decision guide, and AI agent guide
  - DDD/Clean Architecture layer vocabulary used by existing skills
test_patterns:
  - Documentation lint-style checks with git diff --check
  - Reference checks for new skill links and routing entries
---

# Clean Architecture Skill for LLM Modules

## Problem

Issue #226 asks for a new skill that helps LLM-related modules follow Clean
Architecture, SOLID, DRY, KISS, and established design patterns. The repository
already has architecture, code organization, and quality skills, but there is no
LLM-specific workflow that explains where prompts, provider adapters, model
selection, tool orchestration, and deterministic tests should live.

## Goals

- Add a `clean-architecture-llm` skill under `.claude/skills/`.
- Document layer boundaries for LLM-backed modules and agent workflows.
- Include guidance for SOLID, DRY, KISS, and patterns such as Adapter, Strategy,
  Factory, Decorator, and Pipeline.
- Provide concrete templates for LLM ports, adapters, prompt factories, and
  tests without adding production PHP code.
- Register the skill in onboarding, README, the decision guide, and the
  cross-agent guide so future agents can find it.

## Non-Goals

- Do not implement a production LLM feature in this PR.
- Do not add dependencies, SDK clients, or runtime configuration.
- Do not change CI thresholds or architecture rules.
- Do not replace the existing DDD, code organization, documentation, or CI
  skills. The new skill routes LLM-specific design work to those existing
  workflows when applicable.

## Proposed Solution

Create a documentation-only skill with three files:

- `SKILL.md`: primary workflow and decision rules.
- `examples/llm-module-template.md`: provider-neutral code templates and
  directory layout examples.
- `reference/review-checklist.md`: review checklist for LLM module PRs.

Then update the existing skill catalogs and onboarding docs:

- Add `clean-architecture-llm` to mandatory new feature verification lists.
- Add decision-tree routing for LLM-powered modules.
- Update the AI agent total from 19 to 20.
- Add short onboarding and README pointers to the new skill.

## Architecture Guidance

The skill should reinforce these boundaries:

| Layer | Responsibility | LLM Rule |
| ----- | -------------- | -------- |
| Domain | Business invariants and domain events | No prompts, provider SDKs, HTTP clients, or model-specific concepts |
| Application | Use cases, command/query handlers, ports, DTOs | Own provider-neutral interfaces and orchestration |
| Infrastructure | HTTP/SDK provider adapters, retries, timeouts, persistence | Implements ports and hides provider details |
| Tooling/Docs | Agent skills, BMAD specs, prompt playbooks | Documents workflows without leaking into runtime code |

## Acceptance Criteria

- New skill frontmatter follows existing `.claude/skills/*/SKILL.md` format.
- Skill covers Clean Architecture boundaries, SOLID, DRY, KISS, patterns, testing,
  observability, privacy, and anti-patterns.
- Examples show a provider-neutral port, request/response DTOs, prompt factory,
  adapter, strategy, and deterministic test shape.
- Review checklist gives concrete evidence reviewers can request.
- README, onboarding, decision guide, and AI agent guide link to the skill.
- Local validation confirms references and whitespace are clean.

## Validation Plan

- `git diff --check`
- `rg -n "clean-architecture-llm" .claude/skills README.md docs/onboarding.md specs`
- Documentation-only PR: no runtime PHP tests are required unless CI policy asks
  for full checks.
