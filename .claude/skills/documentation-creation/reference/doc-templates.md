# Documentation Templates

Use these scaffolds when authoring each documentation file. Replace every placeholder with project-specific details gathered during discovery.

## main.md

```markdown
# VilnaCRM {Project Name}

## What Is the {Project Name}?

{One-paragraph description covering main functionality and purpose}

## Design Principles

1. **Hexagonal Architecture** – Domain/Application/Infrastructure boundaries
2. **DDD & CQRS** – Commands, handlers, aggregates, repositories
3. **Security First** – {Authentication method}, rate limiting
4. **Documentation Everywhere** – Every feature documented at launch

## Technology Stack

| Component  | Technology          | Version |
| ---------- | ------------------- | ------- |
| Language   | PHP                 | {X.Y}   |
| Runtime    | {Runtime}           | latest  |
| Framework  | Symfony             | {X.Y}   |
| API Layer  | API Platform        | {X.Y}   |
| Database   | {Database}          | {X.Y}   |
| Messaging  | Symfony Messenger   | {X.Y}   |
| Cache      | Redis               | {X.Y}   |

## Quick Links

- [Getting Started](getting-started.md)
- [Design & Architecture](design-and-architecture.md)
- [API Endpoints](api-endpoints.md)
- [Developer Guide](developer-guide.md)
```

## getting-started.md

```markdown
# Getting Started

## Prerequisites

- Docker ≥ 26
- Docker Compose ≥ 2.24
- GNU Make
- mkcert (for HTTPS if required)

## Installation

\`\`\`bash
git clone https://github.com/VilnaCRM-Org/{project-name}.git
cd {project-name}
cp .env.example .env
make build
make start
make install
\`\`\`

## Verification

\`\`\`bash
make setup-test-db
make doctrine-migrations-migrate
make unit-tests
curl https://localhost/api/health
\`\`\`
```

## design-and-architecture.md

```markdown
# Design & Architecture

## Architectural Style

- Hexagonal Architecture (Ports & Adapters)
- Domain-Driven Design ({bounded contexts list})
- CQRS with Command Bus

## Directory Layout

\`\`\`text
src/
├── {Context1}/
│   ├── Application/
│   ├── Domain/
│   └── Infrastructure/
├── {Context2}/
├── Shared/
└── Internal/HealthCheck/
\`\`\`

## Layer Dependencies

\`\`\`text
Domain ← Application ← Infrastructure
\`\`\`

## Bounded Contexts

| Context  | Purpose |
| -------- | ------- |
| {Context1} | {Purpose} |
| {Context2} | {Purpose} |
| Shared   | Cross-cutting concerns |
| Internal | Health checks |
```

## developer-guide.md

```markdown
# Developer Guide

## Directory Structure

(Include tree with Application/Domain/Infrastructure breakdown)

## Make Commands

| Command | Description |
| ------- | ----------- |
| `make build` | Build containers |
| `make start` | Start stack |
| `make stop` | Stop stack |
| `make unit-tests` | PHPUnit unit suite |
| `make integration-tests` | Integration suite |
| `make behat` | Behat scenarios |
| `make load-tests` | K6 scenarios |
| `make ci` | Full pipeline |

## Quality Gates

List PHPInsights scores, coverage expectations, Infection MSI, etc.
```

## api-endpoints.md

```markdown
# API Endpoints

## REST (API Platform)

Describe resource operations, filters, serialization groups.

## {Auth} Endpoints

Document authentication endpoints if applicable.

## GraphQL

Include sample queries and mutations.
```

## testing.md

```markdown
# Testing Strategy

| Suite              | Command               | Location            |
| ------------------ | --------------------- | ------------------- |
| Unit               | `make unit-tests`     | `tests/Unit/`       |
| Integration        | `make integration-tests` | `tests/Integration/` |
| Behat              | `make behat`          | `tests/Behat/`      |
| Load (K6)          | `make load-tests`     | `tests/Load/`       |
| Mutation (Infection)| `make infection`     | N/A                 |

Note Faker usage requirement, coverage thresholds, etc.
```

## Additional Templates

Replicate structure for: glossary, user-guide, advanced-configuration, performance, security, operational, onboarding, community-and-support, legal-and-licensing, release-notes, versioning. Keep consistent headings:

```markdown
# {Title}

## Summary

## Sections (H2/H3)

- Provide checklists/tables/examples
- Link to related docs
```

## Placeholder Reference

Replace these placeholders in all templates:

| Placeholder     | Description                          |
| --------------- | ------------------------------------ |
| `{Project Name}` | e.g., "User Service", "Core Service" |
| `{X.Y}`         | Version number from composer.json    |
| `{Runtime}`     | PHP-FPM, FrankenPHP, etc.           |
| `{Database}`    | MySQL, MongoDB, PostgreSQL, etc.    |
| `{Context1}`    | First bounded context name          |
| `{Context2}`    | Second bounded context name         |
| `{Purpose}`     | Brief description of context purpose|
| `{Auth}`        | OAuth, JWT, etc.                    |
