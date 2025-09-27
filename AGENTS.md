# Repository Guidelines

## Service Overview

User Service is a PHP 8.3+ microservice built with Symfony 7.2, API Platform 4.1, and GraphQL. It manages user registration, authentication, and profile flows for the VilnaCRM ecosystem, exposing REST and GraphQL APIs plus OAuth 2.0 grants (Authorization Code, Client Credentials, Password) with localization in English and Ukrainian. The codebase embraces Hexagonal Architecture, DDD, CQRS, and event-driven principles, backed by extensive automated testing (193+ unit, integration, Behat, CLI, and load suites).

## Architecture & Project Layout

- Source is split into bounded contexts `src/Shared`, `src/User`, `src/OAuth`, each with `Application/`, `Domain/`, and `Infrastructure/` layers; adapters/UI live in `public/` and `templates/`.
- API Platform resources reside in `config/api_platform/resources`; Symfony configuration in `config/`; Doctrine migrations in `migrations/`; generated docs and diagrams in `docs/`.
- Tests are organized under `tests/Unit`, `tests/Integration`, `tests/Behat`, `tests/CLI`, and `tests/Load`; Faker is available through the base test cases and must replace hard-coded values.
- Domain events (`UserRegisteredEvent`, `PasswordResetRequestedEvent`, etc.) decouple workflows; message buses route commands/queries to handlers.
- Review `config/api_platform`, `config/doctrine`, and `config/routes` whenever you change resources, entities, or endpoints.

### Bounded Context Snapshots

- **Shared** – cross-cutting validators, exception normalizers, message buses, retry strategies, Doctrine types, OpenAPI builders, and abstractions.
- **User (core domain)** – commands/handlers, HTTP processors, GraphQL resolvers, aggregates (`User`, `ConfirmationToken`, `PasswordResetToken`), events, value objects, repositories.
- **OAuth** – thin integration around the League OAuth2 Server bundle, providing entity metadata and documentation hooks.

## Container-First Development Rules

- **CRITICAL**: Only run commands through Docker. Prefer `make <target>`; otherwise use `docker compose exec php …` or `make sh`. Host-level PHP/Composer commands are forbidden.
- Initial bootstrap (never cancel mid-run): `make build` (~15–30 min) → `make start` (~5–10 min) → `make install` (~3–5 min) → `make doctrine-migrations-migrate` (~1–2 min). Verify https://localhost/api/docs and https://localhost/api/graphql immediately afterward.
- Manage services with `make start`, `make stop`, `make down`; inspect logs via `make logs` or `make new-logs`; clear cache using `make cache-clear`; open an interactive shell with `make sh`.
- Respect timing expectations: Docker build can exceed 45 min, dependency installation 10 min, full CI 30 min, load tests 45 min. Never interrupt these targets; restarts are more expensive.
- Discover automation with `make` or `make help` before scripting anything custom.

## Command Reference & Timings

- **Testing** – `make unit-tests` (100% line coverage enforced), `make integration-tests`, `make behat`, `make all-tests` (8–15 min, never cancel), `make setup-test-db`, `make tests-with-coverage`, `make coverage-html`, `make infection` (mutation safety net).
- **Quality** – `make phpcsfixer`, `make psalm`, `make psalm-security`, `make phpinsights`, `make deptrac`; run these before committing.
- **CI Pipeline** – `make ci` performs validation, security scans, style fixes, static analysis, architecture checks, unit/integration/Behat tests, and mutation testing. Work is unfinished until it prints `✅ CI checks successfully passed!`. Investigate any `❌` output, fix root causes, rerun.
- **Load testing** – `make smoke-load-tests` (10 VUs), `make average-load-tests` (50 VUs), `make stress-load-tests` (150–300 VUs), `make spike-load-tests` (400 VUs), `make load-tests`, or `make execute-load-tests-script scenario=<name>`. Set aside 15–35 min per run and create an OAuth client first; endpoints exercised include REST (`getUser`, `createUser`, `updateUser`, `confirmUser`, `oauth`), GraphQL (`graphQLCreateUser`, `graphQLUpdateUser`, etc.), batch operations, health checks, and email flows.
- **Database & OAuth** – `make doctrine-migrations-migrate`, `make doctrine-migrations-generate`, `make load-fixtures`, `make create-oauth-client CLIENT_NAME=<value>`.
- **Specs & Utilities** – `make generate-openapi-spec`, `make generate-graphql-spec`, `make cache-warmup`, `make logs`, `make build`, `make purge`. Fetch unresolved PR review feedback with `make pr-comments [PR=###] [FORMAT=text|json|markdown]`.

## Mandatory Delivery Workflow

1. Implement changes using Dockerized tooling only.
2. Run relevant suites during development (unit, integration, Behat, mutation, static analysis, architecture).
3. Execute `make ci`; iterate until the closing line reads `✅ CI checks successfully passed!`.
4. Summarize executed commands (especially `make ci`) in the PR description, link issues, and include screenshots/cURL snippets when changing external contracts.
5. Use `make pr-comments` to list unresolved review comments; address suggestions first, then instructions/prompts, then questions.
6. If any gate fails, fix the root issue, refactor to reduce complexity, extend tests, rerun the affected tool, and repeat until green—quality thresholds are never lowered.

## Coding Standards & Naming

- PSR-12 enforced via PHP CS Fixer; four-space indentation, strict types, readonly DTOs where practical.
- Name commands/handlers/processors with `VerbNoun` patterns (e.g., `RegisterUserCommand`, `ConfirmUserHandler`); align test filenames with the subject and suffix with `Test.php`.
- Inline comments are disallowed; express intent through method/variable names or extracted helpers.
- Keep cyclomatic complexity ≤5 per method; address phpinsights warnings promptly.
- Maintain correct pluralization and localized messaging for all user-facing text (time units, validation errors, API responses).
- Delete empty Doctrine migrations immediately—they add noise and break history.

## API Platform & OpenAPI Guidance

- Use API Platform configuration (`input:`, processors, denormalization contexts) to define operations; avoid manual `openapi.requestBody` or OA annotations.
- Serializer groups drive schema generation; empty schemas usually indicate misconfigured groups or DTO wiring.
- Use dedicated empty DTOs for 204/empty responses; never return success messages for security-sensitive endpoints (password reset/confirm).
- Generate specs with `make generate-openapi-spec` and `make generate-graphql-spec`. If Swagger shows missing schemas, ensure custom factories augment (not replace) API Platform-generated components.
- Prefer Symfony Rate Limiter + Cache for throttling; retire bespoke DB-based rate limitations to keep responsibilities focused.

## Testing Strategy & Quality Gates

- 100% line coverage is mandatory; `make unit-tests` fails otherwise. Maintain MSI ≥99 by killing all mutants through `make infection`.
- Integration tests hit real infrastructure (MariaDB, Redis, LocalStack). Behat covers business scenarios across REST, GraphQL, OAuth, and localization.
- Use Faker for all test data—emails, passwords, tokens, identifiers—to keep suites resilient.
- Manual smoke after impactful changes: run user registration + confirmation (REST and GraphQL), inspect MailCatcher on http://localhost:1080, verify OAuth authentication, confirm localization via `Accept-Language` headers.
- Quality score targets: application profile requires quality 100, complexity ≥96, architecture 100, style 100; tests profile requires ≥95 in every category. If a metric dips, improve the implementation (more targeted tests, simpler code) rather than suppressing the check.

## Load & Performance Expectations

- K6 scripts in `tests/Load` cover REST, GraphQL, batch, OAuth, and health endpoints. Ensure an OAuth client exists before running. Previous campaigns achieved 400 RPS with p99 <100 ms; treat this as the performance bar.

## Security, Configuration & Secrets

- Never commit secrets; `.env.test` carries safe defaults. Use Docker Compose overrides or environment variables for real credentials.
- Run `make check-security` and `make psalm-security` after dependency or auth changes. Monitor Snyk/Dependabot alerts.
- Credential handling: bcrypt hashing with configurable cost (`PASSWORD_HASHING_COST=15`), confirmation tokens are random hex strings (length 10) with a one-hour TTL and single use. OAuth supports Authorization Code, Client Credentials, and Password grants.
- Local stack: Redis at 6379 (dev DB 0, test DB 1), LocalStack at 4566 for SQS, MailCatcher at 1080 for email previews.
- Follow `SECURITY.md` for responsible disclosure and scrub generated artefacts (`var/`, `coverage/`, load-test outputs) before committing.

## Environment Snapshot

- `.env`: `DATABASE_URL=mysql://root:root@database:3306/db?serverVersion=11.4`, `REDIS_URL=redis://redis:6379/0`, `API_BASE_URL=https://localhost`, `STRUCTURIZR_PORT=8080`, AWS SQS/LocalStack endpoints.
- `.env.test`: isolates Redis (`redis://redis:6379/1`), sets `LOAD_TEST_CONFIG=tests/Load/config.json.dist`, and overrides mailer/DB for testing.
- Load tests call `localhost:8081`; UI/diagnostics accessible at https://localhost/api/docs, https://localhost/api/graphql, http://localhost:8080/workspace/diagrams.

## Troubleshooting & Timeouts

- Allow long-running commands to finish; cancelling often leaves containers or caches inconsistent.
- Frequent issues & remedies: Docker permission errors (check daemon socket access), missing dev dependencies (`make install`), database connectivity (inspect container health/logs), mutation memory limits (`php -d memory_limit=-1` inside container), GitHub rate limiting (configure token), load-test failures in restricted environments (document inability to reach `ingest.k6.io`).
- If Swagger omits schemas, remove custom OA annotations, verify serializer groups, and ensure DTOs are registered.

## Additional Guidelines

- Symfony features take precedence over custom code (validators, rate limiting, caching, serialization). Replace bespoke solutions with framework components when encountered.
- Keep architecture aligned with Deptrac; rerun `make deptrac` after reorganizing namespaces or dependencies.
- Use `make pr-comments` outputs to triage review feedback (committable suggestions, detailed prompts, clarifications) and address them sequentially.
- When refactoring, apply SOLID principles, reduce complexity, and enhance test coverage—never downgrade quality gates to push code through.
- Continuously improve quality scores: expand edge-case coverage, split large methods, tighten domain boundaries.

## Manual Validation Playbook

1. **REST password reset** – `POST /api/users` to register → inspect MailCatcher for confirmation token → `POST /api/users/confirm` → authenticate via OAuth.
2. **GraphQL flow** – run `registerUser`, `confirmUser`, `updateUser` mutations and `user` query via https://localhost/api/graphql.
3. **OAuth authorization** – create a client (`make create-oauth-client clientName=test`), execute Authorization Code flow, verify refresh tokens.
4. **Localization** – exercise endpoints with `Accept-Language: en` and `Accept-Language: uk`; ensure responses, errors, and validation messages localize correctly.
5. **Load scenarios** – provision OAuth credentials, run smoke/average/stress/spike scripts, confirm REST/GraphQL/batch endpoints sustain expected throughput.

Adhering to these guidelines keeps the service’s architecture, documentation, and CI automation aligned while maintaining the project’s high quality bar. EOF
