# Repository Guidelines

VilnaCRM User Service is a PHP 8.3+ microservice built with Symfony 7.2, API Platform 4.1, MySQL, and GraphQL. It manages user accounts and authentication (OAuth server, REST API, GraphQL) inside the VilnaCRM ecosystem. The project follows Hexagonal Architecture with DDD & CQRS patterns and includes comprehensive testing across unit, integration, E2E, and load suites.

**CRITICAL: Always use make commands or docker exec into the PHP container. Never run PHP commands directly on the host.**

## 🚨 CRITICAL FOR OPENAI/GPT/CODEX AGENTS - READ THIS FIRST! 🚨

**BEFORE attempting to fix ANY issue in this repository, you MUST follow this workflow:**

### Mandatory Workflow for AI Agents

1. **READ** → `.claude/skills/AI-AGENT-GUIDE.md` (cross-agent guide)
2. **IDENTIFY** → Use `.claude/skills/SKILL-DECISION-GUIDE.md` to pick the right skill
3. **EXECUTE** → Open the specific skill file (e.g., `.claude/skills/deptrac-fixer/SKILL.md`)
4. **FOLLOW** → Execute the step-by-step instructions exactly as written

### ❌ DO NOT

- Fix issues directly from AGENTS.md without reading the skills
- Skip the skill decision guide
- Guess fixes based on generic DDD knowledge
- Use partial information from this file

### ✅ DO

- Start with `.claude/skills/AI-AGENT-GUIDE.md`
- Use the decision tree in `SKILL-DECISION-GUIDE.md`
- Read the complete skill file for your task
- Check supporting files (`reference/`, `examples/`) when referenced

### Example: Fixing Deptrac Violations

**WRONG APPROACH:**

```
1. See Deptrac violation in output
2. Remove framework imports from Domain
3. Add validation logic to Domain entity ❌ INCORRECT!
```

**CORRECT APPROACH:**

```
1. Read .claude/skills/AI-AGENT-GUIDE.md
2. Read .claude/skills/SKILL-DECISION-GUIDE.md → Points to "deptrac-fixer"
3. Read .claude/skills/deptrac-fixer/SKILL.md
4. Follow Pattern 1: Domain → Symfony
   - Remove ALL validation from Domain
   - Use YAML config at config/validator/validation.yaml
   - Domain entities stay pure (no Symfony validation/filters)
```

### Validation Architecture (CRITICAL)

**Domain Layer:**

- ❌ NO validation logic (no `filter_var`, `strlen`, or assertions)
- ❌ NO Symfony validation (`#[Assert\...]`)
- ✅ Pure PHP entities/value objects with primitive types
- ✅ Accept parameters directly in constructor

**Application Layer:**

- ✅ YAML validation config at `config/validator/validation.yaml`
- ✅ DTOs with public properties
- ✅ Custom validators in `Application/Validator/`

**Example (User Context):**

```php
// ❌ WRONG - Domain with validation
namespace App\User\Domain\Entity;

class User
{
    private function assertEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException();
        }
    }
}

// ✅ CORRECT - Pure Domain entity
namespace App\User\Domain\Entity;

class User
{
    public function __construct(private string $email, private string $initials)
    {
    }
}
```

```yaml
# ✅ CORRECT - Validation in config/validator/validation.yaml
App\User\Application\DTO\UserRegisterDto:
  properties:
    email:
      - NotBlank: { message: 'not.blank' }
      - Email: { message: 'email.invalid' }
      - App\Shared\Application\Validator\Constraint\UniqueEmail: ~
    initials:
      - NotBlank: { message: 'not.blank' }
      - App\Shared\Application\Validator\Constraint\Initials: ~
```

**Why This Matters:** Skills encode the real architecture patterns. AGENTS.md is a reference, not a fix guide.

---

## What Is User Service?

The User Service handles user registration, authentication, password reset, and account updates. It exposes REST and GraphQL APIs, implements OAuth 2.0 flows, and localizes responses (EN/UK). It integrates tightly with other VilnaCRM services via consistent DDD/CQRS patterns.

## Command Reference

### MANDATORY: Use Make Commands or Container Access Only

- Make commands (preferred): `make <command>`
- Container access: `docker compose exec php <command>` or `make sh` then run commands inside

### Quick Start

1. `make build` (15-30 min, NEVER CANCEL)
2. `make start` (5-10 min, includes database, Redis, LocalStack)
3. `make install` (3-5 min, PHP dependencies)
4. `make doctrine-migrations-migrate` (1-2 min)
5. Verify: https://localhost/api/docs, https://localhost/api/graphql

### Essential Development Commands

- `make start` — Start all services
- `make stop` — Stop all services
- `make sh` — Access PHP container shell
- `make install` — Install dependencies
- `make cache-clear` — Clear Symfony cache
- `make logs` — Show all service logs
- `make new-logs` — Show live logs

### Testing Commands

- `make unit-tests` — Run unit tests
- `make integration-tests` — Integration tests
- `make behat` — E2E tests via BDD scenarios
- `make all-tests` — Run complete test suite
- `make setup-test-db` — Create test database
- `make tests-with-coverage` — Generate coverage
- `make infection` — Mutation testing

### Code Quality Commands (Run Before Every Commit)

- `make phpcsfixer` — Auto-fix code style (PSR-12)
- `make psalm` — Static analysis
- `make phpinsights` — Code quality analysis
- `make deptrac` — Architecture validation

### Comprehensive CI Quality Checks

**Primary CI Command:**

- `make ci` — Runs all checks (composer validation, security, code style, static analysis, architecture, full test suite, mutation testing). **Must end with "✅ CI checks successfully passed!"**

**Individual CI commands:** `make composer-validate`, `make check-requirements`, `make check-security`, `make psalm-security`, `make all-tests`, `make phpinsights`, `make deptrac`, `make infection`

### Load Testing Commands

- `make smoke-load-tests`
- `make load-tests`
- `make average-load-tests`
- `make stress-load-tests`
- `make spike-load-tests`
- `make execute-load-tests-script scenario=<name>`

### Database & OAuth Commands

- `make doctrine-migrations-migrate`
- `make doctrine-migrations-generate`
- `make create-oauth-client CLIENT_NAME=<name>`
- `make load-fixtures`

### Specification Generation

- `make generate-openapi-spec`
- `make generate-graphql-spec`

## Schemathesis Validation Guidance

- Diagnose failing requests reported by `make schemathesis-validate`; replay with `curl` from inside the PHP container and adjust `app:seed-schemathesis-data` plus Symfony validators/DTOs so the API enforces the expected rules.
- Seed deterministic data through `php bin/console app:seed-schemathesis-data` (the make target already calls it) and keep OpenAPI examples consistent with those fixtures.
- Do **not** introduce request listeners or per-user-agent logic to coerce Schemathesis payloads; fixes belong in validation or documentation so every client benefits.
- Iterate on validation/schema changes until `make schemathesis-validate` completes with zero failures and warnings.

**Remediation steps:**

1. Run `make schemathesis-validate`, capture every failing curl snippet, and replay it from within the PHP container to observe the actual response (status code, headers, body).
2. Triage failures by category and fix at the source:
   - Invalid authentication accepted → tighten OAuth/password grant validation and fixtures.
   - 500 errors / missing headers → add guards so unauthenticated flows return RFC 7807 401/400 JSON instead of HTML errors.
   - Schema-compliant payload rejected → align fixtures, DTO validators, and OpenAPI examples so documented payloads are reachable.
   - Repeated 404 warnings → extend `app:seed-schemathesis-data` with deterministic user IDs/emails used in OpenAPI examples.
3. Update OpenAPI examples and serializer groups so documented payloads exactly match seeded data (no placeholder values).
4. Re-run `make schemathesis-validate` (or `make generate-openapi-spec` if run independently) until both Examples and Coverage phases report zero failures/warnings.

## AI Agent Skills (Claude Code, OpenAI, GitHub Copilot, Cursor)

This repository includes **AI-agnostic Skills** in `.claude/skills/`. Always use them.

### Available Skills

- **ci-workflow**: Run comprehensive CI checks
- **code-review**: Retrieve and address PR comments
- **testing-workflow**: Manage tests (unit, integration, E2E, mutation)
- **implementing-ddd-architecture**: Design DDD patterns (entities, value objects, aggregates, CQRS)
- **deptrac-fixer**: Diagnose and fix Deptrac violations
- **quality-standards**: Protected thresholds overview
- **complexity-management**: Reduce cyclomatic complexity
- **openapi-development**: OpenAPI endpoint factories / transformers
- **database-migrations**: Doctrine ORM/MySQL migrations
- **documentation-creation**: Create initial documentation suite from scratch
- **documentation-sync**: Keep docs synchronized with code changes
- **api-platform-crud**: Add REST resources with CRUD
- **load-testing**: Create/manage K6 load tests

**Skill Decision Guide:** `.claude/skills/SKILL-DECISION-GUIDE.md` provides the decision tree and scenarios.

**For non-Claude agents:** Start with `.claude/skills/AI-AGENT-GUIDE.md` for usage instructions.

## Architecture Deep Dive

### Layer Dependency Rules (CRITICAL)

Architecture is enforced by Deptrac. **Domain must stay framework-free.**

```
Infrastructure → Application → Domain
      ↓              ↓           ↓
  External       Use Cases    Pure Business
```

| From Layer         | Can Depend On                                       | CANNOT Depend On |
| ------------------ | --------------------------------------------------- | ---------------- |
| **Domain**         | NOTHING (pure PHP only)                             | Everything       |
| **Application**    | Domain, Infrastructure, Symfony, API Platform, etc. | N/A              |
| **Infrastructure** | Domain, Application, Symfony, Doctrine, etc.        | N/A              |

### Bounded Contexts (DDD)

- **Shared Context**: Cross-cutting support (validators, exception normalizers, OpenAPI helpers, buses, custom Doctrine types).
- **User Context**: User registration/update flows.
  - Application (can use Symfony/API Platform): Commands, Command Handlers (`CommandHandlerInterface`), DTOs with YAML validation, Processors/Resolvers, Event Subscribers (`DomainEventSubscriberInterface`).
  - Domain (NO framework imports): Entities (User), Value Objects (Email, Password), Domain Events, Repository interfaces, Domain exceptions.
  - Infrastructure: MySQL repositories, XML mappings under `config/doctrine/`.
- **OAuth Context**: OAuth client and token operations.
  - Application: Commands/Handlers, DTOs, validators, processors/resolvers.
  - Domain: Pure entities/value objects/events/exceptions.
  - Infrastructure: Repository implementations, XML mappings.
- **Internal Context**: Health checks and internal utilities.

### Deptrac Violation Patterns and Quick Fixes

**NEVER modify `deptrac.yaml` to bypass violations—fix the code.**

1. **Domain → Symfony Validation**

```
Domain must not depend on Symfony
  src/User/Domain/Entity/User.php
    uses Symfony\Component\Validator\Constraints as Assert
```

- Remove Symfony validation from Domain. Keep entities pure.
- Use DTO + YAML validation in `config/validator/validation.yaml`.

2. **Domain → Doctrine Annotations**

```
Domain must not depend on Doctrine
  src/User/Domain/Entity/User.php
    uses Doctrine ORM annotations/attributes in Domain (move to XML mapping)
```

- Move mappings to XML under `config/doctrine/*.orm.xml`.
- Keep Domain entities annotation-free.

3. **Domain → API Platform**

```
Domain must not depend on ApiPlatform
  src/User/Domain/Entity/User.php
    uses ApiPlatform\Metadata\ApiResource
```

- Move configuration to YAML or Application DTOs.

4. **Infrastructure → Application Handler**

```
Infrastructure must not depend on Application handlers
  src/User/Infrastructure/EventListener/UserListener.php
    uses App\User\Application\CommandHandler\...
```

- Inject `CommandBusInterface` and dispatch commands instead of calling handlers directly.

Quick reference:

| Violation Type             | Fix                             |
| -------------------------- | ------------------------------- |
| Domain → Symfony Validator | DTO + YAML validation           |
| Domain → Doctrine          | XML mappings in `config/`       |
| Domain → API Platform      | YAML config or Application DTOs |
| Infrastructure → Handler   | Command bus dispatch            |

### CQRS & Event-Driven Design

- Commands implement `CommandInterface`
- Command Handlers implement `CommandHandlerInterface` and are tagged `app.command_handler`
- Domain Events extend `DomainEvent`
- Event Subscribers implement `DomainEventSubscriberInterface` and are tagged `app.event_subscriber`
- Aggregates extend `AggregateRoot` to record/pull events

### Service Registration (Auto-Configured)

- All classes in `src/` are autowired/auto-configured.
- `_instanceof` in `config/services.yaml` tags command handlers, event subscribers, and OpenAPI endpoint factories.

### API Platform & Database

- Database: MySQL via Doctrine ORM
- Custom Types: ULID, Domain UUID (`Shared/Infrastructure/DoctrineType`)
- Mappings: XML in `config/doctrine/*.orm.xml`
- Resource discovery: Entities in `src/{Context}/Domain/Entity`
- Filters: Order, Search, Range, Date, Boolean (see `services.yaml`)
- Formats: JSON-LD, JSON Problem (RFC 7807), GraphQL

### Testing Structure

| Test Type   | Tool    | Directory                    | Env var                         | Purpose               |
| ----------- | ------- | ---------------------------- | ------------------------------- | --------------------- |
| Unit        | PHPUnit | `tests/Unit/`                | `PHPUNIT_TESTSUITE=Unit`        | Isolated components   |
| Integration | PHPUnit | `tests/Integration/`         | `PHPUNIT_TESTSUITE=Integration` | Component interaction |
| E2E (BDD)   | Behat   | `features/` & `tests/Behat/` | -                               | User scenarios        |
| Load        | k6      | `tests/Load/`                | -                               | Performance/stress    |

## Source Code Organization (Codely Pattern)

```text
src/
├── User/
│   ├── Application/            # Commands, Handlers, DTOs, Validators, Processors/Resolvers
│   ├── Domain/                 # Pure entities, VOs, events, exceptions, repositories
│   └── Infrastructure/         # Repository implementations, services
├── OAuth/
│   ├── Application/
│   ├── Domain/
│   └── Infrastructure/
├── Internal/HealthCheck/
└── Shared/
    ├── Application/            # Cross-cutting app concerns
    ├── Domain/                 # Kernel (AggregateRoot, buses, VO)
    └── Infrastructure/         # Buses, Doctrine types, retry strategies

config/
└── doctrine/                   # Doctrine XML mappings (NOT in Domain)
```

**Placement rules:**

- Domain: Entities/Aggregates, ValueObject, Event, Repository interfaces, Exception (no framework imports)
- Application: Command, CommandHandler, DTO, EventSubscriber, Processor/Resolver (can use Symfony/API Platform)
- Infrastructure: Repository implementations, XML mappings, framework integrations

## Quality Gates

- PHPInsights: Quality 100%, Complexity 94%, Architecture 100%, Style 100%
- Deptrac: 0 violations
- Psalm & Psalm Security: 0 errors
- Tests: All suites green; keep coverage high
- Infection: Maintain strong MSI
- `make ci` must finish with **"✅ CI checks successfully passed!"**

### Quality Improvement Guidelines

- Fix the code, never lower thresholds
- Reduce cyclomatic complexity (target < 5 per method)
- Strengthen tests for escaped mutants or coverage drops
- Respect DDD/CQRS boundaries; keep Domain pure
- Use Faker for all test data (`tests/Unit/UnitTestCase.php`, `tests/Integration/IntegrationTestCase.php`)

## Additional Development Guidelines

### Code Comments and Self-Explanatory Code

- **MANDATORY**: Remove inline comments; write self-explanatory code with clear naming.
- Extract helper methods instead of using comments for explanation.

### Symfony & API Platform Built-ins First

- **MANDATORY**: Prefer built-in validators, rate limiter, cache, and API Platform features over custom implementations.
- Avoid manual `openapi.requestBody`; use DTOs with `input:` and let API Platform generate schemas.

### Testing Standards

- **MANDATORY**: Use Faker for all test data (no hardcoded emails/passwords/tokens/IDs).
- Dynamic test data improves robustness.

### Database Migrations

- **MANDATORY**: Delete empty migration files immediately; ensure migrations contain real schema changes.

### API Platform Best Practices

- **MANDATORY**: Use input DTOs, correct HTTP status codes (204 for empty responses), and separate empty response DTOs.
- For security flows (e.g., password reset), return status codes only—no success messages.

### Pluralization and Internationalization

- **MANDATORY**: Use correct singular/plural forms for time units and user-facing text; ensure localization is grammatically correct.
