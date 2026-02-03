# CLAUDE.md

Guidance for Claude Code (claude.ai/code) and other AI agents when working in this repository.

## Project Overview

VilnaCRM User Service — PHP 8.3/Symfony 7.2 microservice for user registration, authentication, and account management. Uses API Platform 4.1, MySQL (Doctrine ORM), OAuth 2.0, REST, and GraphQL. Architecture: Hexagonal (Ports & Adapters), DDD, CQRS.

## 🎯 Skills & Workflows

Skills live in `.claude/skills/` and are auto-discovered. Use the decision tree in `.claude/skills/SKILL-DECISION-GUIDE.md` when unsure.

### Quick Skill Guide

| Task Type                   | Skill                           | When to Use                               |
| --------------------------- | ------------------------------- | ----------------------------------------- |
| **Fix Deptrac violations**  | `deptrac-fixer`                 | Architecture boundary violations detected |
| **Fix complexity issues**   | `complexity-management`         | PHPInsights complexity score drops        |
| **Run CI checks**           | `ci-workflow`                   | Before committing, validating changes     |
| **Debug test failures**     | `testing-workflow`              | PHPUnit, Behat, or Infection issues       |
| **Handle PR feedback**      | `code-review`                   | Processing code review comments           |
| **Organize code structure** | `code-organization`             | Directory placement, naming, type safety  |
| **Create DDD patterns**     | `implementing-ddd-architecture` | New entities, value objects, aggregates   |
| **Add CRUD endpoints**      | `api-platform-crud`             | New API resources with full CRUD          |
| **Add caching**             | `cache-management`              | Cache keys, TTLs, invalidation patterns   |
| **Add business metrics**    | `observability-instrumentation` | AWS EMF metrics for domain events         |
| **Create load tests**       | `load-testing`                  | K6 performance tests (REST/GraphQL)       |
| **Update entity schema**    | `database-migrations`           | Modifying entities, adding fields         |
| **Document APIs**           | `openapi-development`           | OpenAPI endpoint factories                |
| **Create docs (new project)** | `documentation-creation`      | Initial documentation suite               |
| **Sync documentation**      | `documentation-sync`            | After any code changes                    |
| **Quality overview**        | `quality-standards`             | Understanding protected thresholds        |
| **Query performance**       | `query-performance-analysis`    | N+1 detection, EXPLAIN, indexes           |
| **Architecture diagrams**   | `structurizr-architecture-sync` | Update workspace.dsl (C4 model)           |

> For detailed flows and cross-agent guidance see `.claude/skills/AI-AGENT-GUIDE.md`.

### ✅ Mandatory New Feature Verification Gate (ALL Skills)

For any **NEW feature** (new behavior, endpoint, domain model, schema change, or user-facing change), you MUST execute **every** skill in `.claude/skills/` **after implementation**.

**Execution rules:**

1. Open each `SKILL.md` file listed below.
2. Follow its steps exactly. If a skill is not applicable, explicitly record **"Not applicable"** with a concrete reason.
3. Run required commands using `make` or `docker compose exec php ...` only.
4. Provide evidence in your response: commands run and outcomes. If you cannot run a command, stop and explain why.
5. Do not claim the feature is complete until this gate is finished.

**Skills to execute for every new feature:**

- `api-platform-crud`
- `cache-management`
- `ci-workflow`
- `code-organization`
- `code-review`
- `complexity-management`
- `database-migrations`
- `deptrac-fixer`
- `documentation-creation`
- `documentation-sync`
- `implementing-ddd-architecture`
- `load-testing`
- `observability-instrumentation`
- `openapi-development`
- `quality-standards`
- `query-performance-analysis`
- `structurizr-architecture-sync`
- `testing-workflow`

## 🛡️ Quality Standards (Protected Thresholds)

Do **not** lower these thresholds:

| Tool              | Metric       | Required | Skill for Issues        |
| ----------------- | ------------ | -------- | ----------------------- |
| **PHPInsights**   | Complexity   | 94% min  | `complexity-management` |
| **PHPInsights**   | Quality      | 100%     | `complexity-management` |
| **PHPInsights**   | Architecture | 100%     | `deptrac-fixer`         |
| **PHPInsights**   | Style        | 100%     | Run `make phpcsfixer`   |
| **Deptrac**       | Violations   | 0        | `deptrac-fixer`         |
| **Psalm**         | Errors       | 0        | Fix reported issues     |
| **Test Coverage** | Lines        | High %   | `testing-workflow`      |
| **Infection MSI** | Score        | High %   | `testing-workflow`      |

Never reduce thresholds—fix code instead.

## Development Commands

### Quick Reference Table

| Category         | Command                      | Description             | Related Skill           |
| ---------------- | ---------------------------- | ----------------------- | ----------------------- |
| **Docker**       | `make start`                 | Start containers        | -                       |
|                  | `make sh`                    | Access PHP container    | -                       |
| **Quality**      | `make phpcsfixer`            | Fix code style          | -                       |
|                  | `make psalm`                 | Static analysis         | -                       |
|                  | `make phpinsights`           | Quality checks          | `complexity-management` |
|                  | `make deptrac`               | Architecture validation | `deptrac-fixer`         |
| **Testing**      | `make unit-tests`            | Unit tests              | `testing-workflow`      |
|                  | `make integration-tests`     | Integration tests       | `testing-workflow`      |
|                  | `make behat`                 | Behat E2E tests         | `testing-workflow`      |
|                  | `make all-tests`             | All functional tests    | `testing-workflow`      |
|                  | `make infection`             | Mutation testing        | `testing-workflow`      |
| **Load Testing** | `make smoke-load-tests`      | Minimal load test       | `load-testing`          |
|                  | `make load-tests`            | All load tests          | `load-testing`          |
| **CI**           | `make ci`                    | Run all CI checks       | `ci-workflow`           |
| **Database**     | `make setup-test-db`         | Reset test database     | `database-migrations`   |
| **API Docs**     | `make generate-openapi-spec` | Export OpenAPI          | `openapi-development`   |

### Detailed Commands

<details>
<summary>Docker Environment</summary>

```bash
make start          # Start docker containers
make stop           # Stop docker containers
make down           # Stop and remove containers
make build          # Build docker images from scratch
make sh             # Access PHP container shell
make logs           # Show all logs
make new-logs       # Show live logs
```

</details>

<details>
<summary>Dependency Management</summary>

```bash
make install        # Install dependencies from composer.lock
make update         # Update dependencies per composer.json
```

</details>

<details>
<summary>Code Quality & Static Analysis</summary>

```bash
make phpcsfixer     # Auto-fix PHP coding standards
make psalm          # Run Psalm static analysis
make psalm-security # Run Psalm security/taint analysis
make phpinsights    # Run PHP quality checks
make deptrac        # Validate architectural boundaries
make composer-validate  # Validate composer files
```

</details>

<details>
<summary>Testing</summary>

```bash
make unit-tests           # Run unit tests only
make integration-tests    # Run integration tests only
make behat                # Run Behat end-to-end tests
make all-tests            # Run unit, integration, and e2e tests
make tests-with-coverage  # Run tests with coverage report
make infection            # Run mutation testing

# Setup test database
make setup-test-db
```

</details>

<details>
<summary>Load Testing</summary>

```bash
make smoke-load-tests   # Minimal load test
make average-load-tests # Average load test
make stress-load-tests  # High load test
make spike-load-tests   # Spike/extreme load test
make load-tests         # Run all load tests
make execute-load-tests-script scenario=<name>
```

</details>

<details>
<summary>Symfony Commands</summary>

```bash
make cache-clear    # Clear Symfony cache
make cache-warmup   # Warmup cache
make commands       # List all Symfony console commands
```

</details>

<details>
<summary>API Documentation</summary>

```bash
make generate-openapi-spec   # Export OpenAPI spec to .github/openapi-spec/spec.yaml
make generate-graphql-spec   # Export GraphQL spec to .github/graphql-spec/spec
```

</details>

## Architecture

### Directory Structure

```text
src/
├── User/                 # User bounded context
├── OAuth/                # OAuth bounded context
├── Internal/HealthCheck/ # Internal services
└── Shared/               # Shared kernel
    ├── Application/
    ├── Domain/
    └── Infrastructure/
```

### Layered Architecture (Hexagonal/DDD)

The codebase enforces strict architectural boundaries via Deptrac:

| Layer              | Purpose                   | Contains                                                                                                   | Dependencies            |
| ------------------ | ------------------------- | ---------------------------------------------------------------------------------------------------------- | ----------------------- |
| **Domain**         | Pure business logic       | Entities, Value Objects, Aggregates, Events, Commands (interfaces), Repository interfaces                  | None (isolated)         |
| **Application**    | Use cases & orchestration | Command Handlers, Event Subscribers, DTOs, Transformers, Processors, Resolvers                             | Domain + Infrastructure |
| **Infrastructure** | External concerns         | Repository implementations (MySQL/Doctrine ORM), Message buses (Symfony), Doctrine types, Retry strategies | Domain + Application    |

**Rules**: Domain must stay framework-free. Application can use Symfony/API Platform. Infrastructure implements persistence/adapters.

### CQRS & Event-Driven Design

| Component             | Interface                        | Tag                    | Purpose                |
| --------------------- | -------------------------------- | ---------------------- | ---------------------- |
| **Commands**          | `CommandInterface`               | -                      | Write operations       |
| **Command Handlers**  | `CommandHandlerInterface`        | `app.command_handler`  | Execute commands       |
| **Domain Events**     | Extend `DomainEvent`             | -                      | Record state changes   |
| **Event Subscribers** | `DomainEventSubscriberInterface` | `app.event_subscriber` | React to events        |
| **Aggregates**        | Extend `AggregateRoot`           | -                      | Record and pull events |

**Flow**: Command → Handler → Aggregate → Domain Events → Subscribers

### Service Registration (Auto-Configured)

- All classes in `src/` autowired.
- Command handlers, event subscribers, and OpenAPI endpoint factories are tagged via `_instanceof` in `config/services.yaml`.

### API Platform & Database

| Component        | Technology   | Location                             | Notes                                |
| ---------------- | ------------ | ------------------------------------ | ------------------------------------ |
| **Database**     | MySQL        | -                                    | Doctrine ORM                         |
| **Custom Types** | ULID, UUID   | `Shared/Infrastructure/DoctrineType` | Custom field types                   |
| **Mappings**     | XML          | `config/doctrine/*.orm.xml`          | Keep Domain entities annotation-free |
| **Resources**    | API Platform | `src/{Context}/Domain/Entity`        | Resource discovery enabled           |
| **Filters**      | API Platform | `services.yaml`                      | Order, Search, Range, Date, Boolean  |

### Testing Structure

| Test Type   | Tool    | Directory                   | Env Var                         | Purpose        |
| ----------- | ------- | --------------------------- | ------------------------------- | -------------- |
| Unit        | PHPUnit | `tests/Unit/`               | `PHPUNIT_TESTSUITE=Unit`        | Isolated logic |
| Integration | PHPUnit | `tests/Integration/`        | `PHPUNIT_TESTSUITE=Integration` | Interactions   |
| E2E (BDD)   | Behat   | `features/`, `tests/Behat/` | -                               | User scenarios |
| Load        | k6      | `tests/Load/`               | -                               | Performance    |

## 🔄 Common Workflows

1. **Design Domain Model** → `implementing-ddd-architecture`
2. **Create API Endpoint** → `api-platform-crud`
3. **Configure Database** → `database-migrations`
4. **Add Business Metrics** → `observability-instrumentation`
5. **Write Tests** → `testing-workflow`
6. **Update Docs** → `documentation-sync`
7. **Run CI Validation** → `ci-workflow`

## 📐 Patterns

- Define entities in `{Context}/Domain/Entity/`, map via `config/doctrine/{Entity}.orm.xml`
- Add command + handler (implements `CommandInterface`/`CommandHandlerInterface`); handlers are auto-tagged
- Use `AggregateRoot` for domain events; dispatch via event bus
- Define custom API filters in `config/services.yaml` and tag with `api_platform.filter`

## 🚀 CI/CD Pipeline

Run `make ci` before finishing any task. Checks include PHP CS Fixer, Psalm (+ security), PHPInsights (100/94/100/100), Deptrac, PHPUnit (unit+integration+Behat), Infection, Composer validation, and security audit.

## 🔧 Environment Variables

Key variables in `.env`/`.env.test`:

| Variable    | Purpose                      | Example                              |
| ----------- | ---------------------------- | ------------------------------------ |
| `APP_ENV`   | Application environment      | `dev`, `test`, `prod`                |
| `DB_URL`    | Database connection string   | `mysql://root:root@database:3306/db` |
| `AWS_SQS_*` | AWS SQS message queue config | Various                              |

## 📂 Directory Organization Conventions

Place files in directories that match their class type. Each directory should contain ONLY the class type indicated by its name.

| Directory Name   | Must Contain             | Example Files                            |
| ---------------- | ------------------------ | ---------------------------------------- |
| `Command/`       | Symfony Console Commands | `SeedDataCommand.php`                    |
| `Factory/`       | Factory classes          | `UserFactory.php`, `OpenApiFactory.php`  |
| `Validator/`     | Validator classes        | `EmailValidator.php`                     |
| `Provider/`      | Provider classes         | `UserProvider.php`                       |
| `EventListener/` | Event Listeners          | `ExceptionListener.php`                  |
| `Enum/`          | PHP Enums                | `Requirement.php`, `AllowEmptyValue.php` |
| `ValueObject/`   | Value Objects            | `Header.php`, `Parameter.php`            |
| `Builder/`       | Builder classes          | `QueryParameterBuilder.php`              |
| `Seeder/`        | Seeder classes           | `UserSeeder.php`, `OAuthSeeder.php`      |

**Event Listener Registration**: Register event listeners in `config/services.yaml` using tags, NOT via PHP attributes.

**Rules**:

- Never mix class types in a directory
- Create new directories when introducing new class types
- Use subdirectories for logical grouping (e.g., `Validator/Http/`, `Provider/Http/`)
- Respect Deptrac rules—never modify architecture config to accommodate misplaced files

## 📚 Additional Resources

- Skills decision guide: `.claude/skills/SKILL-DECISION-GUIDE.md`
- DDD reference: `.claude/skills/implementing-ddd-architecture/REFERENCE.md`
- API examples: `.claude/skills/api-platform-crud/examples/`
- Load test patterns: `.claude/skills/load-testing/`
- Kernel: `src/Shared/Kernel.php`
- Preload: `config/preload.php`
- API docs: `https://localhost/api/docs` (after `make start`)
- Git hooks: `captainhook.json`

**Never lower quality thresholds. Respect architectural boundaries. Use the skills proactively.**
