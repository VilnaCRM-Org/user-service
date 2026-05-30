---
workflowType: research
project_name: BMAD FR/NFR Review Gate
author: Codex
date: 2026-05-30
revision: 2
---

# Research: BMAD FR/NFR Review Gate

## Current State

The repository already has a local AI review loop through
`scripts/ai-review-loop.sh` and `make ai-review-loop`. The loop supports Codex
and Claude reviewers, parses `STATUS: PASS` or `STATUS: FAIL`, runs a fixer
when review fails, and verifies fixes with a configurable command.

The existing prompt is a generic code review prompt. It does not read BMAD
specs, inventory FR/NFR requirements, score implementation evidence, require
manual test evidence, or check the NonFunctionals.com catalog categories.

BMALPH is installed through the repo's `make bmalph-setup` path. The generated
`_bmad/` assets are local and ignored. Durable automation must therefore live in
tracked repo files such as `.claude/skills/`, `.agents/skills/`, `scripts/`,
`scripts/ai-review-prompts/`, `Makefile`, tests, docs, and `specs/`.

## NFR Source

The review gate pins the NonFunctionals.com catalog categories visible on
2026-05-17:

- Performance
- Usability
- Maintainability
- Availability
- Interoperability
- Security
- Manageability
- Automatability
- Dependability

Source: https://nonfunctionals.com/catalog.html

The expanded quality review cross-checks the base catalog against ISO/IEC
25010:2023 product quality, ISO/IEC 25012 data quality, privacy, accessibility,
secure supply-chain, operational-excellence, observability, sustainability, and
automation-governance sources. The goal is to catch PR risks that fit poorly
into the original nine labels, such as unsafe account states, PII retention,
dependency provenance, deploy/rollback readiness, accessibility, and
whole-codebase data consistency.

Primary references:

- https://www.iso.org/standard/78176.html
- https://iso25000.com/index.php/en/iso-25000-standards/iso-25010
- https://www.iso.org/standard/35736.html
- https://www.nist.gov/privacy-framework
- https://csrc.nist.gov/pubs/sp/800/218/final
- https://owasp.org/www-project-application-security-verification-standard/
- https://owasp.org/www-project-software-component-verification-standard/
- https://www.w3.org/WAI/standards-guidelines/wcag/
- https://opentelemetry.io/docs/concepts/signals/
- https://sre.google/sre-book/monitoring-distributed-systems/

## Knowledge Graph and Impact Context

The gate should not depend on one graph product to pass, but it should be able
to consume graph context when present.

- Graphify is a practical optional choice for multimodal codebase graphs. Its
  current public docs describe a graph builder for code, docs, PDFs, screenshots,
  diagrams, and transcripts, with exportable `graph.html`, `graph.json`, and
  `GRAPH_REPORT.md` artifacts. The same docs describe AST edges, imports, calls,
  classes, docstrings, rationale comments, community clustering, god-node
  detection, and surprise paths. Source: https://graphify.homes/en
- Graphify should remain optional for this gate because the hosted quick start
  uses ZIP upload, packaging/integration details are moving quickly, and BMAD
  review must work in local and CI environments that cannot send private code to
  a third-party service. When a locally generated Graphify report is available,
  `BMAD_REVIEW_IMPACT_CONTEXT` can pass it to the reviewer as supporting
  relationship evidence.
- codebase-memory MCP is a strong local alternative for impact queries. Its
  current README describes a local tree-sitter and hybrid-LSP knowledge graph
  with PHP support, call chains, HTTP routes, cross-service links, 14 MCP tools,
  and `detect_changes` impact mapping. The accompanying 2026 arXiv preprint
  describes a persistent tree-sitter graph via MCP, call-graph traversal, impact
  analysis, and community discovery. Sources:
  https://github.com/DeusData/codebase-memory-mcp and
  https://arxiv.org/abs/2603.27277
- Deptrac is the deterministic baseline already aligned with this repository's
  architecture rules. Its graph output can explain DDD layer impact without
  adding a new dependency to the BMAD gate.

The implemented wrapper therefore accepts `BMAD_REVIEW_IMPACT_CONTEXT` and
otherwise creates a lightweight `codebase-impact-context.md` containing the
base/head, changed files, and available graph artifact pointers. The reviewer
must still inspect related runtime paths, architecture boundaries, contracts,
data paths, config, dependencies, tests, docs, operations, security/privacy, and
backward-compatibility surfaces before scoring impact.

Recommended adoption order:

1. Keep the current built-in impact handoff as the mandatory baseline.
2. Prefer deterministic repo-native evidence first: `git diff`, `rg`, Deptrac,
   tests, specs, docs, CI workflows, dependency metadata, and architecture
   rules.
3. Add optional Graphify or codebase-memory artifacts when they are available
   and locally safe to generate.
4. Fail the gate only when related codebase surfaces are not inspected or
   explicitly ruled out, not because one named graph tool is unavailable.

## Integration Points

- `Makefile`: add a discoverable post-implementation gate target.
- `scripts/ai-review-loop.sh`: reuse the existing agent loop, status parser,
  fixer phase, and verification phase.
- `scripts/ai-review-prompts/`: add BMAD-specific review and fix prompts.
- `.claude/skills/code-review/SKILL.md`: the new gate complements PR comment
  handling.
- `.claude/skills/ci-workflow/SKILL.md`: `make ci` remains the local CI gate.
- `.agents/skills/`: Codex skill wrapper for discoverability.

## Constraints

- Do not depend on generated `_bmad/` files at runtime.
- Keep `make ai-review-loop` backward compatible.
- Keep first-line review output as `STATUS: PASS` or `STATUS: FAIL`.
- Fail closed when evidence is missing, GitHub state cannot be verified, CI
  cannot be verified, or any requirement score is below 5/5.
- Do not fabricate manual test evidence.
- Do not lower repository quality thresholds.

## Risks

- Prompt-only scoring can overstate confidence. Mitigation: require source
  requirement evidence, implementation evidence, automated test evidence, and
  manual evidence where automation cannot prove behavior.
- GitHub or CI can be temporarily unavailable. Mitigation: report blocked or
  failed gate instead of passing.
- Overusing not-applicable decisions weakens the gate. Mitigation: require a
  concrete reason and source evidence for every not-applicable row.
- Agent availability may vary locally and in CI. Mitigation: keep the gate as a
  standalone target and configurable through environment variables.
