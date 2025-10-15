# Repository Guidelines

User Service is a PHP 8.3+ microservice built with Symfony 7.2, API Platform 4.1, and GraphQL. It provides user account management and authentication within the VilnaCRM ecosystem using OAuth Server, REST API, and GraphQL. The project follows hexagonal architecture with DDD & CQRS patterns and includes comprehensive testing with 193 test files across unit, integration, and E2E test suites.

**CRITICAL: Always use make commands or docker exec into the PHP container. Never use direct PHP commands outside the container.**

## What Is User Service?

The VilnaCRM User Service is designed to manage user accounts and authentication within the VilnaCRM ecosystem. It provides essential functionalities such as user registration and authentication, implemented with OAuth Server, REST API, and GraphQL, ensuring seamless integration with other components of the CRM system.

### Key Features

- **User Registration**: Facilitates adding new users with validation and confirmation workflows
- **Authentication**: Robust OAuth mechanisms with multiple grant types (Authorization Code, Client Credentials, Password)
- **Flexibility**: REST API and GraphQL interfaces for versatile integration
- **Localization**: Supports English and Ukrainian languages
- **Modern Architecture**: Built on Hexagonal Architecture, DDD, CQRS, and Event-Driven principles

### Design Principles

- **Hexagonal Architecture**: Separates core business logic from external dependencies
- **Domain-Driven Design**: Focuses on core domain logic with bounded contexts
- **CQRS**: Separates read and write operations for better performance and scalability
- **Event-Driven Architecture**: Uses domain events for loose coupling and extensibility
- **Modern PHP Stack**: Leverages latest PHP features and best practices

## Command Reference

### MANDATORY: Use Make Commands or Container Access Only

**All development work MUST use either:**

1. **Make commands** (preferred): `make command-name`
2. **Direct container access**: `docker compose exec php command`
3. **Container shell**: `make sh` then run commands inside

**NEVER run PHP commands directly on host system.**

### Quick Start

1. `make build` (15-30 min, NEVER CANCEL)
2. `make start` (5-10 min, includes database, Redis, LocalStack)
3. `make install` (3-5 min, PHP dependencies)
4. `make doctrine-migrations-migrate` (1-2 min)
5. Verify: https://localhost/api/docs, https://localhost/api/graphql

### Essential Development Commands

- `make start` -- Start all services (Docker containers, database, Redis)
- `make stop` -- Stop all services
- `make sh` -- Access PHP container shell for manual commands
- `make install` -- Install PHP dependencies via Composer
- `make cache-clear` -- Clear Symfony cache
- `make logs` -- Show all service logs
- `make new-logs` -- Show live logs

### Testing Commands

- `make unit-tests` -- Run unit tests (193 test files, 2-3 min)
- `make integration-tests` -- Test database/external services (3-5 min)
- `make behat` -- E2E tests via BDD scenarios (5-10 min)
- `make all-tests` -- Run complete test suite (8-15 min, NEVER CANCEL)
- `make setup-test-db` -- Create test database
- `make tests-with-coverage` -- Generate code coverage (10-15 min)
- `make coverage-html` -- Generate HTML coverage report
- `make infection` -- Mutation testing with Infection (advanced quality check)

### Code Quality Commands (Run Before Every Commit)

- `make phpcsfixer` -- Auto-fix PHP code style (PSR-12)
- `make psalm` -- Static analysis for type safety
- `make phpinsights` -- Code quality analysis
- `make deptrac` -- Architecture dependency validation

### Comprehensive CI Quality Checks

**IMPORTANT: Run comprehensive CI checks before finishing any task and committing changes:**

**Primary CI Command:**

- `make ci` -- Run all comprehensive CI checks (composer validation, security analysis, code style, static analysis, architecture validation, complete test suite, mutation testing). **MUST output "✅ CI checks successfully passed!" at the end when all checks pass successfully. If any check fails, outputs "❌ CI checks failed:" with specific error details.**

**Individual CI commands available:**

- `make composer-validate` -- Validate composer.json and composer.lock
- `make check-requirements` -- Check Symfony requirements
- `make check-security` -- Security vulnerability analysis
- `make phpcsfixer` -- Auto-fix PHP code style (PSR-12)
- `make psalm` -- Static analysis for type safety
- `make psalm-security` -- Security taint analysis
- `make phpinsights` -- Code quality analysis
- `make deptrac` -- Architecture dependency validation
- `make unit-tests` -- Unit test suite
- `make integration-tests` -- Integration test suite
- `make behat` -- End-to-end BDD tests
- `make infection` -- Mutation testing

**Mandatory workflow before finishing tasks:**

1. Make your code changes
2. Run `make ci` to execute all quality checks
3. **CRITICAL**: The `make ci` command MUST output "✅ CI checks successfully passed!" at the end
4. If you see "❌ CI checks failed:" message, you MUST fix the reported issues and rerun `make ci`
5. **DO NOT finish the task** until you see "✅ CI checks successfully passed!" in the output
6. Fix any issues reported by the checks iteratively
7. Ensure all tests pass and code coverage is maintained
8. Commit your changes only after CI passes completely with the success message

### Load Testing Commands

- `make load-tests` -- Run complete load test suite with K6
- `make smoke-load-tests` -- Minimal load testing
- `make average-load-tests` -- Average load scenarios
- `make stress-load-tests` -- High load testing
- `make spike-load-tests` -- Extreme spike testing
- `make execute-load-tests-script scenario=<name>` -- Run specific scenario

### Database & OAuth Commands

- `make doctrine-migrations-migrate` -- Apply database migrations
- `make doctrine-migrations-generate` -- Create new migration
- `make create-oauth-client CLIENT_NAME=<name>` -- Create OAuth client
- `make load-fixtures` -- Load database fixtures

### Specification Generation

- `make generate-openapi-spec` -- Export OpenAPI YAML specification
- `make generate-graphql-spec` -- Export GraphQL specification

## Schemathesis Validation Guidance

- Always diagnose the failing request reported by `make schemathesis-validate`; reproduce it with `curl` and adjust `app:seed-schemathesis-data` plus the Symfony validators/DTOs so the API enforces the expected rules.
- Seed deterministic data through `php bin/console app:seed-schemathesis-data` (the make target already calls it) and keep the OpenAPI description (request factories, serializer groups, schema builders, examples) consistent with those fixtures.
- Do **not** introduce request listeners or per-user-agent logic to coerce Schemathesis payloads; fixes belong in validation or documentation so every client benefits.
- Iterate on validation/schema changes until `make schemathesis-validate` completes without errors.

### Detailed Remediation Steps

1. Run `make schemathesis-validate`, capture every failing curl snippet, and replay it from within the PHP container to observe the actual response (status code, headers, body).
2. Triage failures by category and address the root issue:
   - **Invalid authentication accepted**: ensure the OAuth password grant refuses tampered secrets or unexpected parameters and that fixtures expose only legitimate credential pairs.
   - **500 errors / missing header handling**: add guards in listeners/transformers so unauthenticated flows return `application/problem+json` 401/400 responses rather than HTML error pages.
   - **Schema-compliant payload rejected**: hydrate fixtures (users, tokens, OAuth codes) so documented examples reference real data, then align Symfony validators and command handlers with the schema contracts.
   - **Repeated 404 warnings**: extend `app:seed-schemathesis-data` instead of skipping endpoints; create deterministic UUIDs/emails for `/api/users/{id}` flows and reuse them in OpenAPI examples.
3. Update OpenAPI examples and serializer groups so the documented payloads exactly match the seeded data (no placeholder values that Schemathesis cannot reach).
4. Re-run `make generate-openapi-spec` if invoked in isolation, or just rerun `make schemathesis-validate`. Repeat until both **Examples** and **Coverage** phases report zero failures and zero warnings.

## Architecture Deep Dive

### Bounded Contexts (DDD)

The User Service is divided into 3 bounded contexts with predictable structure:

#### 1. Shared Context

Provides foundational support across the service:

- **Application Layer**: Cross-cutting concerns (Validators, Exception Normalizers, OpenAPI docs)
- **Domain Layer**: Interfaces for Infrastructure, abstract classes, common entities
- **Infrastructure Layer**: Message Buses, custom Doctrine types, retry strategies

#### 2. User Context (Core Domain)

Comprehensive user management functionality:

- **Application Layer**:
  - Commands: RegisterUserCommand, ConfirmUserCommand, UpdateUserCommand, SendConfirmationEmailCommand
  - Command Handlers: Process business operations
  - HTTP Request Processors & GraphQL Resolvers
  - Event Listeners & Subscribers
- **Domain Layer**:
  - Entities: User, ConfirmationToken
  - Aggregates: ConfirmationEmail
  - Events: UserRegisteredEvent, UserConfirmedEvent, EmailChangedEvent, PasswordChangedEvent
  - Value Objects: UserUpdate
  - Domain Exceptions: UserNotFoundException, InvalidPasswordException, TokenNotFoundException
- **Infrastructure Layer**: Repository implementations

#### 3. OAuth Context

Thin context for OAuth server integration:

- Uses [OAuth2 Server Bundle](https://oauth2.thephpleague.com/) for implementation
- Contains minimal entity mapping for OpenAPI documentation

### CQRS Implementation

- **Commands**: Encapsulate write operations (RegisterUser, UpdateUser, etc.)
- **Queries**: Handle read operations (separate from commands)
- **Handlers**: Process commands/queries with business logic
- **Message Bus**: Routes commands/queries to appropriate handlers

### Event-Driven Architecture

- **Domain Events**: Published from Domain layer or handlers
- **Event Subscribers**: Handle events for system extensibility
- **Available Events**: UserRegistered, UserConfirmed, EmailChanged, PasswordChanged, ConfirmationEmailSent

## Comprehensive Testing Strategy

### Testing Philosophy

- **100% Unit & Integration Test Coverage** - All code paths covered
- **0 Escaped Mutants** - Mutation testing with Infection ensures test quality
- **End-to-End Coverage** - BDD scenarios cover all user journeys
- **Load Testing** - Performance validated under various load conditions

### Test Types & Commands

1. **Unit Tests** (`make unit-tests`):

   - Focus on individual classes/methods with mocked dependencies
   - 193 test files, 2-3 minutes runtime
   - Test business logic in isolation

2. **Integration Tests** (`make integration-tests`):

   - Test interactions between components (database, external services)
   - Real database connections and services
   - 3-5 minutes runtime

3. **End-to-End Tests** (`make behat`):

   - BDD scenarios in Gherkin language in `/features` folder
   - 6 feature files covering: user operations, GraphQL, OAuth, localization
   - Test complete user journeys from UI to database

4. **Mutation Testing** (`make infection`):

   - Validates test quality by making code mutations
   - Must maintain 0 escaped/uncovered mutants
   - Uses Infection framework for rigorous testing

5. **Load Testing** (K6-based):
   - **Smoke**: `make smoke-load-tests` (10 VUs, minimal load)
   - **Average**: `make average-load-tests` (50 VUs, normal patterns)
   - **Stress**: `make stress-load-tests` (300 VUs, high load)
   - **Spike**: `make spike-load-tests` (400 VUs, extreme spikes)

### Code Quality Standards

- **PHPInsights**: Instant quality checks, architecture analysis
- **Psalm**: Static analysis with security taint analysis (`make psalm-security`)
- **Deptrac**: Architecture dependency validation, prevents unwanted coupling
- **PHP CS Fixer**: PSR-12 compliance, auto-formatting

## Security & Performance

### Security Practices

- **Password Security**: Bcrypt hashing with configurable cost (PASSWORD_HASHING_COST=15)
- **Confirmation Tokens**: Random hex tokens with 1-hour expiration (CONFIRMATION_TOKEN_LENGTH=10)
- **OAuth Implementation**: Secure OAuth2 server with multiple grant types
- **Dependency Scanning**: Snyk and Dependabot for vulnerability detection

### Performance Optimization

- **Load Testing Results**: Service handles 400 RPS with P(99) < 100ms for most endpoints
- **Database Optimization**: Doctrine ORM with proper indexing and migrations
- **Caching Strategy**: Redis integration for session/cache management
- **Container Efficiency**: FrankenPHP for high-performance PHP execution

### OAuth Grant Types

1. **Authorization Code**: Full OAuth flow with redirect (`/api/oauth/authorize`)
2. **Client Credentials**: Service-to-service authentication
3. **Password**: Direct username/password authentication (for trusted clients)

### Localization Support

- **Languages**: English (default) and Ukrainian
- **Header**: `Accept-Language: en` or `Accept-Language: uk`
- **Coverage**: API messages, error responses, validation messages

## Validation

### Manual Testing Scenarios

**ALWAYS run through at least one complete end-to-end scenario after making changes:**

1. **User Registration and Confirmation Flow:**

   - Create a new user via REST API: `POST /api/users` with email/password
   - Check that confirmation email is sent (check MailCatcher at http://localhost:1080)
   - Extract confirmation token from email
   - Confirm user registration: `POST /api/users/confirm` with token
   - Verify user can authenticate via OAuth

2. **GraphQL User Operations:**

   - Register user via GraphQL mutation `registerUser` at https://localhost/api/graphql
   - Check email in MailCatcher for confirmation token
   - Confirm user via GraphQL mutation `confirmUser` with token
   - Query user information via GraphQL query `user`
   - Test user updates via `updateUser` mutation

3. **OAuth Authentication Flow:**

   - Create OAuth client: `make create-oauth-client clientName=test`
   - Test OAuth authorization flow with test client credentials
   - Verify JWT token generation and validation
   - Test token refresh capabilities

4. **Localization Testing:**
   - Test API responses in different languages (features include user_localization.feature)
   - Verify error messages are properly localized
   - Test GraphQL localization support

**Service Health Checks:**

- Verify https://localhost/api/docs loads (API documentation)
- Verify https://localhost/api/graphql loads (GraphQL playground)
- Verify http://localhost:8080/workspace/diagrams loads (architecture diagrams)
- Check database connectivity and migrations status

### Load Testing Scenarios

**All load tests require OAuth client setup and have 30-minute setup/teardown timeouts.**

- **Smoke tests:** `make smoke-load-tests` -- minimal load (10 VUs, 10 RPS, 10s duration). Takes 5-10 minutes.
- **Average load:** `make average-load-tests` -- normal load patterns (50 VUs, 50 RPS, 30s total). Takes 15-25 minutes.
- **Stress tests:** `make stress-load-tests` -- high load testing (150-300 VUs, 150-300 RPS). Takes 20-30 minutes.
- **Spike tests:** `make spike-load-tests` -- extreme load spikes (400 VUs, 400 RPS). Takes 25-35 minutes.

**Available load test endpoints:**

- REST API: getUser, createUser, updateUser, deleteUser, confirmUser, oauth
- GraphQL API: graphQLGetUser, graphQLCreateUser, graphQLUpdateUser, graphQLConfirmUser
- Batch operations: createUserBatch (10 users per batch)
- Health checks and email operations

## Common Tasks

### Development Workflow

- Check make targets: `make` or `make help`
- View logs: `make logs` or `make new-logs`
- Access container shell: `make sh`
- Stop services: `make stop`
- Restart services: `make down && make start`

### Database Operations

- Run migrations: `make doctrine-migrations-migrate`
- Generate migration: `make doctrine-migrations-generate`
- Load fixtures: `make load-fixtures`
- Setup test database: `make setup-test-db`

### Build and Deployment

- Clear cache: `make cache-clear`
- Build for production: Use docker-compose.prod.yml
- Generate API specs: `make generate-openapi-spec` and `make generate-graphql-spec`

### CI/CD Integration

The repository includes 15 GitHub Actions workflows for:

- Automated testing (PHPUnit, Behat, load tests)
- Code quality checks (Psalm, PHPInsights, PHP CS Fixer)
- Security scanning and dependency analysis
- API specification validation and diff checking
- Automated releases and template synchronization

## Key Projects and Structure

### Source Code Organization

```
src/
├── Internal/           # Internal domain logic
├── OAuth/             # OAuth authentication implementation
├── Shared/            # Shared components and utilities
└── User/              # User domain with DDD/CQRS structure
    ├── Application/   # Application services, commands, queries
    ├── Domain/        # Domain entities, value objects, repositories
    └── Infrastructure/ # Database, external services integration
```

### Important Files

- `Makefile` -- All development commands and shortcuts
- `docker-compose.yml` -- Production container configuration
- `docker-compose.override.yml` -- Development environment overrides
- `composer.json` -- PHP dependencies and project metadata
- `config/bundles.php` -- Symfony bundle configuration
- `phpunit.xml.dist` -- Test configuration
- `behat.yml.dist` -- E2E test configuration

### Configuration Files to Check When Making Changes

- Always check `config/api_platform/` after modifying API resources
- Always check `config/doctrine/` after modifying entities
- Always check `config/routes/` after adding new endpoints
- Review `src/User/Application/` when modifying user business logic

## API Platform, Swagger, and OpenAPI Integration

### API Platform Configuration

This service uses **API Platform 4.1** for REST API and GraphQL functionality. API Platform automatically generates OpenAPI documentation and provides Swagger UI interface.

**Key Configuration Files:**

- `config/api_platform/resources.yaml` - Main API resource definitions
- Individual entity annotations (User, etc.)
- DTO classes for input/output

### Swagger/OpenAPI Documentation Best Practices

**IMPORTANT: Use API Platform Built-in Functionality, NOT OpenAPI Library Directly**

- **DO NOT** use `OpenApi\Annotations` (OA\*) annotations in DTOs
- **DO** rely on API Platform's automatic schema generation
- **DO** use Symfony Serializer Groups for input/output control
- **DO** define proper denormalizationContext in API Platform configuration

**Correct DTO Structure:**

```php
<?php

namespace App\User\Application\DTO;

use Symfony\Component\Serializer\Annotation\Groups;

final readonly class ExampleDto
{
    public function __construct(
        #[Groups(['example:write'])]
        public ?string $field = null
    ) {
    }
}
```

**Correct API Platform Configuration:**

```yaml
App\User\Domain\Entity\User:
  operations:
    example_operation:
      class: 'ApiPlatform\Metadata\Post'
      uriTemplate: '/users/{id}/example'
      input: 'App\User\Application\DTO\ExampleDto'
      processor: 'App\User\Application\Processor\ExampleProcessor'
      denormalizationContext:
        groups: ['example:write']
```

### How API Platform Generates Swagger Documentation

1. **Automatic Schema Generation**: API Platform scans DTOs and generates OpenAPI schemas
2. **Serializer Groups**: Groups defined in denormalizationContext control which fields appear in request body
3. **Type Inference**: PHP types and nullable properties automatically generate correct schemas
4. **No Manual Annotations Needed**: Avoid OA\* annotations as they conflict with automatic generation

### Swagger UI Access

- **REST API Docs**: https://localhost/api/docs
- **GraphQL Playground**: https://localhost/api/graphql
- **OpenAPI Spec**: Generated via `make generate-openapi-spec`
- **GraphQL Spec**: Generated via `make generate-graphql-spec`

### Troubleshooting Swagger Issues

**Problem**: Request body schema is empty in Swagger
**Solution**:

1. Remove all `OpenApi\Annotations` (OA\*) from DTOs
2. Ensure Serializer Groups are properly defined
3. Verify denormalizationContext groups match DTO Groups annotations
4. Check that input DTO is correctly specified in API Platform configuration

**Problem**: Parameters not showing in Swagger
**Solution**:

1. Verify uriTemplate parameters match processor expectations
2. Check that path parameters are properly handled in processors
3. Ensure proper API Platform operation configuration

## Timing Expectations and Timeouts

**CRITICAL TIMEOUT VALUES:**

- Docker build: 45+ minutes (NEVER CANCEL)
- Complete test suite: 30+ minutes (NEVER CANCEL)
- Load tests: 45+ minutes (NEVER CANCEL)
- Dependency installation: 10+ minutes (NEVER CANCEL)
- Application startup: 15+ minutes (NEVER CANCEL)

**Common Command Timings:**

- `make build`: 15-30 minutes first time, 5-10 minutes subsequent
- `make start`: 5-10 minutes
- `make install`: 3-5 minutes
- `make all-tests`: 8-15 minutes
- `make phpcsfixer`: 1-2 minutes
- `make psalm`: 2-3 minutes

## Troubleshooting

### Common Issues

- **Docker build fails with "Permission denied"**: Network restrictions blocking Alpine repositories. Document as limitation and use local PHP development with `composer install --no-dev` and `APP_ENV=prod php bin/console`.
- **MakerBundle not found**: Missing dev dependencies. Either run `composer install` with dev dependencies or set `APP_ENV=prod` to run in production mode.
- **Database connection errors**: Ensure `make start` completed successfully and database container is healthy. Check `docker compose logs database`.
- **Memory issues during tests**: Use `php -d memory_limit=-1` for memory-intensive operations like infection testing.
- **GitHub rate limits during composer install**: Use `COMPOSER_NO_INTERACTION=1` or configure GitHub token.
- **Load test OAuth errors**: Ensure OAuth client is created with `make create-oauth-client` before running load tests.
- **Load test network errors**: K6 extension downloading requires external network access. In restricted environments, load tests may fail due to DNS/network limitations when trying to access `ingest.k6.io` for extension provisioning.

### Network and Authentication

- GitHub token may be required for Composer in CI environments
- OAuth client configuration needed for load testing
- MailCatcher (port 1080) required for email testing workflows
- LocalStack (port 4566) provides AWS SQS simulation for message queues

### Performance Optimization

- Use `--no-dev` flag for production composer installs
- Redis cache improves performance (separate databases for dev/test)
- Database connection pooling configured for MariaDB 11.4
- Symfony cache warmup recommended: `make cache-warmup`

## Environment Variables

Key environment variables in `.env`:

- `DATABASE_URL="mysql://root:root@database:3306/db?serverVersion=11.4"`
- `REDIS_URL=redis://redis:6379/0`
- `API_BASE_URL=https://localhost`
- `STRUCTURIZR_PORT=8080` -- Architecture diagram service
- AWS SQS configuration for message queues (LocalStack for development)

Key environment variables in `.env.test`:

- `REDIS_URL=redis://redis:6379/1` -- Separate Redis database for tests
- `LOAD_TEST_CONFIG=tests/Load/config.json.dist` -- Load test configuration
- Database and mailer settings configured in Docker Compose

**Load Test Configuration:**

- Test runs on `localhost:8081` with MailCatcher on port `1080`
- Batch size: 5000 users for bulk operations
- OAuth test client: `ClientName` with ID `ID123` and secret `Secret1`
- Email verification: Max 300 retries with 30s delay between scenarios

## Architecture and Design Patterns

This application implements:

- **Hexagonal Architecture**: Clear separation of domain, application, and infrastructure
- **DDD (Domain-Driven Design)**: User domain with entities, value objects, and repositories
- **CQRS (Command Query Responsibility Segregation)**: Separate commands and queries
- **Event Sourcing**: Domain events for user lifecycle events
- **API-First Design**: REST and GraphQL APIs using API Platform

When making changes, respect these architectural boundaries and patterns.

## Software Engineering Best Practices

### Code Quality and Complexity Management

**MANDATORY: Always follow software engineering best practices to maintain code quality:**

#### Cyclomatic Complexity Management

- **Keep cyclomatic complexity below 5 per class/method** (enforced by PHPInsights)
- **When complexity exceeds threshold, refactor by:**
  - Creating new methods to extract complex logic
  - Extracting strategy classes for complex validation or business rules
  - Using composition instead of inheritance where appropriate
  - Breaking down large methods into smaller, focused methods
  - Using the Strategy Pattern for complex conditional logic

#### Example: Refactoring High Complexity Validators

```php
// ❌ BAD: High cyclomatic complexity (8)
public function validate($value, Constraint $constraint): void
{
    if ($value === null || ($constraint->isOptional() && $value === '')) {
        return;
    }
    if (!(strlen($value) >= 8 && strlen($value) <= 64)) {
        $this->addViolation('password.invalid.length');
    }
    // ... more complex conditions
}

// ✅ GOOD: Low complexity using strategy classes
public function validate($value, Constraint $constraint): void
{
    if ($this->skipChecker->shouldSkip($value, $constraint)) {
        return;
    }
    $this->performValidations($value);
}
```

#### Other Best Practices

- **Single Responsibility**: Each class/method should have one clear purpose
- **DRY Principle**: Don't repeat yourself - extract common logic into reusable components
- **SOLID Principles**: Follow dependency inversion, open/closed, and other SOLID principles
- **Meaningful Names**: Use descriptive class, method, and variable names
- **Small Methods**: Keep methods under 20 lines when possible
- **Consistent Code Style**: Follow PSR-12 standards enforced by PHP CS Fixer

### Quality Gates

All code must pass these quality gates before commit:

- **PHPInsights**: 100% code quality, 95%+ complexity score, 100% architecture score, 100% style score
- **Psalm**: Static analysis with no errors
- **PHP CS Fixer**: PSR-12 compliance
- **Unit/Integration Tests**: 100% test coverage
- **Mutation Testing**: 0 escaped mutants

### Achieving 100% Mutation Testing Coverage

**Understanding Mutation Testing:**
Mutation testing validates test quality by making small changes (mutations) to source code and checking if tests catch these changes. Escaped mutants indicate gaps in test coverage.

**When Unit Tests Can't Catch Escaped Mutants:**

If extending unit tests doesn't achieve 100% mutation score, consider refactoring the original classes to make them more testable:

#### 1. **Constructor Default Parameters**

```php
// ❌ HARD TO TEST: Default values in constructor
public function __construct(
    private int $maxRequests = 3,
    private int $timeWindow = 3600
) {}

// ✅ TESTABLE: Expose defaults or use factory methods
public function __construct(
    private int $maxRequests,
    private int $timeWindow
) {}

public static function withDefaults(): self
{
    return new self(3, 3600);
}
```

#### 2. **DateTime Boundary Conditions**

```php
// ❌ HARD TO TEST: Fixed current time
public function isExpired(): bool
{
    return $this->expiresAt < new DateTime();
}

// ✅ TESTABLE: Injectable time parameter
public function isExpired(?DateTime $currentTime = null): bool
{
    $currentTime ??= new DateTime();
    return $this->expiresAt < $currentTime;
}
```

#### 3. **Complex Boolean Logic**

```php
// ❌ HARD TO TEST: Complex nested conditions
public function validate($value): bool
{
    return $value !== null &&
           strlen($value) >= 8 &&
           strlen($value) <= 64 &&
           preg_match('/[A-Z]/', $value) &&
           preg_match('/[0-9]/', $value);
}

// ✅ TESTABLE: Extract validation steps
public function validate($value): bool
{
    if (!$this->hasValidLength($value)) return false;
    if (!$this->hasUppercase($value)) return false;
    if (!$this->hasDigit($value)) return false;
    return true;
}
```

#### 4. **Static Method Calls**

```php
// ❌ HARD TO TEST: Direct static calls
public function generateToken(): string
{
    return bin2hex(random_bytes(16));
}

// ✅ TESTABLE: Dependency injection or factory
public function generateToken(): string
{
    return $this->tokenGenerator->generate();
}
```

**Mutation Testing Strategy:**

1. **Run `make infection`** to identify escaped mutants
2. **Analyze mutant types**: Constructor parameters, boundary conditions, logical operators
3. **First try**: Add targeted unit tests for specific edge cases
4. **If tests can't catch mutants**: Refactor original classes for better testability
5. **Maintain backward compatibility** when refactoring existing public APIs
6. **Update interface signatures** to match implementation changes

**Target: 100% MSI (Mutation Score Indicator)**

- 784/784 mutants killed (or equivalent for your codebase)
- Zero escaped mutants
- All boundary conditions, default values, and logical operators tested

## Code Review Workflow and PR Refactoring

### Automated Code Review Comment Retrieval

**CRITICAL: Always use `make pr-comments` to retrieve and address all code review comments systematically.**

The repository provides a comprehensive code review workflow that enables automatic retrieval and systematic addressing of all unresolved comments on a Pull Request.

#### Using the PR Comments Command

**Basic usage:**

```bash
make pr-comments                    # Auto-detect PR from current branch
make pr-comments PR=215             # Specify PR number explicitly
make pr-comments FORMAT=json        # Get comments in JSON format
make pr-comments FORMAT=markdown    # Get comments in Markdown format
```

**Command features:**

- **Auto-detection**: Automatically detects PR number from current git branch
- **Multiple formats**: Text (default), JSON, and Markdown output options
- **GitHub Enterprise support**: Configurable via `GITHUB_HOST` environment variable
- **Comprehensive output**: Shows file paths, line numbers, authors, timestamps, and GitHub URLs
- **Unresolved focus**: Only retrieves unresolved comments that require action

#### Code Review Refactoring Workflow

**MANDATORY: Follow this systematic approach for addressing code review feedback:**

##### 1. **Retrieve All Code Review Comments**

```bash
make pr-comments
```

This command will output all unresolved comments in a readable format, showing:

- File path and line number where comment was made
- Author and timestamp of the comment
- Full comment content including suggestions and prompts
- Direct GitHub URL for context

##### 2. **Analyze Comment Types and Prioritize**

**Categorize each comment by type:**

**A. Committable Suggestions (Highest Priority)**

- Comments containing code suggestions that can be directly applied
- Usually prefixed with "suggestion" or contain code blocks
- **Action**: Apply the suggested changes exactly as provided
- **Priority**: Address these first as they provide explicit solutions

**B. LLM Prompts and Instructions (High Priority)**

- Comments providing specific instructions on how to refactor
- May include architectural guidance or implementation approaches
- **Action**: Use these as detailed prompts for code generation/refactoring
- **Priority**: Address after committable suggestions

**C. Questions and Clarifications (Medium Priority)**

- Comments asking for explanation or clarification of implementation
- **Action**: Reply with explanations and make code more self-documenting if needed
- **Priority**: Can be addressed alongside code changes

**D. General Feedback and Observations (Low Priority)**

- Comments providing general observations or praise
- **Action**: Consider for future improvements, no immediate action needed
- **Priority**: Address if time permits

##### 3. **Systematic Implementation Strategy**

**For Committable Suggestions:**

```bash
# Apply suggestion directly to the code
# Example: If comment suggests changing variable name
# Before: $userInfo = ...
# After: $userData = ...  (as suggested)

# Commit the change immediately
git add .
git commit -m "Apply code review suggestion: improve variable naming"
```

**For LLM Prompts:**

```bash
# Use the comment as a detailed prompt for refactoring
# Example: "Refactor this method to use dependency injection"
# 1. Analyze current implementation
# 2. Design dependency injection approach
# 3. Implement changes following SOLID principles
# 4. Update tests accordingly
# 5. Verify with make ci
```

**For Complex Refactoring Requests:**

```bash
# Break down large refactoring into smaller commits
# 1. Create interfaces/abstractions first
# 2. Implement new classes/methods
# 3. Update existing code to use new structure
# 4. Remove deprecated code
# 5. Update tests and documentation
```

##### 4. **Quality Assurance After Each Change**

**MANDATORY: Run quality checks after addressing each comment or group of related comments:**

```bash
# For code changes
make phpcsfixer              # Fix code style
make psalm                   # Static analysis
make unit-tests             # Run unit tests
make ci                     # Full CI suite (for significant changes)

# For test changes
make unit-tests             # Verify tests pass
make infection              # Check mutation testing coverage
```

##### 5. **Documentation and Verification**

**Update documentation when comments suggest:**

- API documentation changes
- README updates
- Inline code comments for clarity
- Architecture decision records

**Verify changes meet requirements:**

- All tests pass with expected coverage
- No regressions introduced
- Code quality metrics maintained
- Architectural boundaries respected

##### 6. **Comment Response Strategy**

**Reply to comments systematically:**

- **Questions**: Provide clear, concise answers
- **Implemented suggestions**: Reply with commit hash that addresses the comment
- **Complex refactoring**: Explain approach taken and reference relevant commits
- **Cannot implement**: Explain technical constraints and propose alternatives

#### Advanced Code Review Patterns

**Handling Conflicting Comments:**

1. Prioritize architectural concerns over stylistic preferences
2. Discuss conflicting suggestions with reviewers before implementing
3. Document decisions in commit messages or PR comments

**Large-Scale Refactoring:**

1. Create separate commits for each logical change
2. Maintain backward compatibility when possible
3. Update tests incrementally with code changes
4. Use feature flags for risky changes

**Performance and Security Comments:**

1. Address security concerns immediately with highest priority
2. Benchmark performance changes when suggested
3. Document performance trade-offs in code comments

#### Integration with Development Workflow

**Before Starting Code Review Refactoring:**

```bash
git status                  # Ensure clean working directory
git pull origin main        # Get latest changes
make pr-comments           # Get current comment status
```

**During Refactoring:**

```bash
# Work on one comment or related group at a time
# Commit frequently with descriptive messages
# Reference comment URLs in commit messages for traceability
```

**After Completing All Comments:**

```bash
make ci                    # Full quality check
make pr-comments           # Verify no new unresolved comments
git push                   # Push all changes
```

This systematic approach ensures that all code review feedback is addressed thoroughly, maintaining high code quality while efficiently incorporating reviewer suggestions and maintaining project standards.

## Documentation Synchronization Requirements

**MANDATORY: When making ANY code changes, adding features, or modifying functionality, you MUST update the corresponding documentation in the `docs/` directory to maintain synchronization between codebase and documentation.**

### Core Documentation Synchronization Rules

**1. Feature Implementation Documentation Updates**
When implementing new features or modifying existing ones:

- **API Changes**: Update `docs/api-endpoints.md` with new endpoints, modified request/response schemas, authentication requirements, and examples
- **Architecture Changes**: Update `docs/design-and-architecture.md` when adding new components, modifying existing patterns, or changing system interactions
- **Configuration Changes**: Update `docs/advanced-configuration.md` when adding new environment variables, configuration options, or deployment parameters
- **Performance Impact**: Update `docs/performance.md` and `docs/performance-frankenphp.md` when changes affect system performance or resource usage

**2. Testing Documentation Updates**
When adding or modifying tests:

- **Test Coverage**: Update `docs/testing.md` with new test categories, testing strategies, or coverage requirements
- **Test Commands**: Update testing section if new make commands or testing procedures are introduced
- **BDD Scenarios**: Document new Behat scenarios or testing workflows

**3. Developer Experience Documentation Updates**
When modifying development workflows:

- **Getting Started**: Update `docs/getting-started.md` if setup procedures change
- **Developer Guide**: Update `docs/developer-guide.md` with new development patterns, tools, or workflows
- **Onboarding**: Update `docs/onboarding.md` if new team member procedures change

**4. Security and Operational Documentation Updates**
When implementing security or operational changes:

- **Security**: Update `docs/security.md` with new authentication methods, authorization rules, or security considerations
- **Operational**: Update `docs/operational.md` with new monitoring, logging, or maintenance procedures

**5. User-Facing Documentation Updates**
When adding user-facing features:

- **User Guide**: Update `docs/user-guide.md` with new user workflows, features, or API usage examples
- **API Documentation**: Ensure OpenAPI/GraphQL schemas in `.github/openapi-spec/` and `.github/graphql-spec/` are updated

### Specific Documentation Update Scenarios

**When adding new REST API endpoints:**

```markdown
1. Update `docs/api-endpoints.md` with:

   - Endpoint URL and HTTP method
   - Request/response schemas with examples
   - Authentication/authorization requirements
   - Error codes and responses
   - Rate limiting information

2. Update `docs/user-guide.md` with usage examples
3. Update OpenAPI specification in `.github/openapi-spec/`
```

**When adding new GraphQL operations:**

```markdown
1. Update `docs/api-endpoints.md` with:

   - Query/mutation schemas
   - Input/output types
   - Example requests and responses
   - Authentication requirements

2. Update GraphQL schema in `.github/graphql-spec/`
3. Update `docs/user-guide.md` with client integration examples
```

**When modifying database schema:**

```markdown
1. Update `docs/design-and-architecture.md` with:

   - Updated entity relationships
   - New database tables or fields
   - Migration considerations

2. Update `docs/developer-guide.md` with:
   - New entity usage patterns
   - Repository method examples
```

**When adding new configuration options:**

```markdown
1. Update `docs/advanced-configuration.md` with:

   - New environment variables
   - Configuration examples
   - Default values and validation rules
   - Docker compose updates if needed

2. Update `docs/getting-started.md` if required for basic setup
```

**When implementing new domain features:**

```markdown
1. Update `docs/design-and-architecture.md` with:

   - New domain models and aggregates
   - Command/query handlers
   - Domain events and their handlers
   - Bounded context interactions

2. Update `docs/glossary.md` with new domain terms
3. Update `docs/developer-guide.md` with usage examples
```

**When modifying authentication/authorization:**

```markdown
1. Update `docs/security.md` with:

   - New OAuth flows or grant types
   - Permission changes
   - Security considerations

2. Update `docs/api-endpoints.md` with updated auth requirements
3. Update `docs/user-guide.md` with client authentication examples
```

**When adding new testing strategies or tools:**

```markdown
1. Update `docs/testing.md` with:

   - New test categories or patterns
   - Updated coverage requirements
   - New testing commands or procedures

2. Update `docs/developer-guide.md` if development workflow changes
```

**When implementing performance optimizations:**

```markdown
1. Update `docs/performance.md` with:

   - Performance benchmarks and improvements
   - New caching strategies
   - Resource usage optimizations

2. Update `docs/php-fpm-vs-frankenphp.md` if runtime comparisons change
```

### Documentation Quality Standards

**Consistency Requirements:**

- Follow existing documentation structure and formatting
- Use consistent terminology from `docs/glossary.md`
- Include practical code examples with proper syntax highlighting
- Add cross-references to related documentation sections

**Completeness Requirements:**

- Document all public APIs, endpoints, and user-facing features
- Include error handling and edge cases
- Provide both basic and advanced usage examples
- Update version information in `docs/versioning.md` when applicable

**Maintenance Requirements:**

- Remove outdated information when features are deprecated
- Update `docs/release-notes.md` with significant changes
- Ensure all links and references remain valid
- Update screenshots or diagrams if UI/architecture changes

### Documentation Validation Process

**Before committing code changes:**

1. **Review Documentation Impact**: Identify which documentation files need updates based on your code changes
2. **Update Relevant Files**: Make comprehensive updates to all affected documentation
3. **Cross-Reference Check**: Ensure all internal links and references remain valid
4. **Example Validation**: Test all code examples and ensure they work with current implementation
5. **Consistency Check**: Verify terminology alignment with `docs/glossary.md`

**Documentation Update Checklist:**

- [ ] API documentation updated for endpoint/schema changes
- [ ] Architecture documentation reflects structural changes
- [ ] Configuration documentation includes new options
- [ ] Testing documentation covers new test scenarios
- [ ] User guide includes new feature usage examples
- [ ] Security documentation addresses new auth/security aspects
- [ ] Performance documentation reflects optimization changes
- [ ] Glossary updated with new domain terms
- [ ] Release notes updated for significant changes
- [ ] All code examples tested and validated

### Automated Documentation Maintenance

**Integration with CI/CD:**

- Documentation updates should be part of the same pull request as code changes
- Consider the `make ci` command should validate documentation consistency
- Use the existing quality checks to ensure documentation standards

**Version Synchronization:**

- Keep documentation version aligned with application version in `docs/versioning.md`
- Update `docs/release-notes.md` for each release with documentation changes
- Maintain backward compatibility notes in relevant documentation sections

This comprehensive approach ensures that the `docs/` directory remains an accurate, up-to-date reflection of the codebase, providing developers and users with reliable documentation that evolves alongside the system.

## Quality Standards Protection

**MANDATORY: Maintain or improve quality standards - NEVER decrease current quality levels:**

### Protected Quality Metrics

**PHPInsights Quality Requirements (phpinsights.php):**

- **min-quality**: 100% (NEVER decrease below 100%)
- **min-complexity**: 95% (NEVER decrease below 95%)
- **min-architecture**: 100% (NEVER decrease below 100%)
- **min-style**: 100% (NEVER decrease below 100%)

**PHPInsights Test Quality Requirements (phpinsights-tests.php):**

- **min-quality**: 95% (NEVER decrease below 95%)
- **min-complexity**: 95% (NEVER decrease below 95%)
- **min-architecture**: 90% (NEVER decrease below 90%)
- **min-style**: 95% (NEVER decrease below 95%)

### Resolving PHPInsights Complexity Failures

- When `make phpinsights` reports only `[ERROR] The complexity score is too low` without pointing to specific files, **run PHP Mess Detector first** to gather actionable hotspots:
  - `make phpmd`
  - If you are troubleshooting manually, you can invoke the same command directly to inspect results quickly.
- Address every PHP MD finding (especially high cyclomatic complexity warnings) before re-running PHPInsights.
- After fixes, execute `make phpinsights` again; the complexity score must now meet or exceed the protected thresholds.

**Test Coverage Requirements:**

- **Unit test coverage**: 100% (NEVER decrease below 100%)
- **Integration test coverage**: Must maintain comprehensive coverage
- **Mutation testing (Infection)**: 100% MSI (NEVER decrease below 100% - 0 escaped mutants)

**Prohibited Quality Downgrades:**

```php
// ❌ FORBIDDEN: Decreasing PHPInsights requirements
'requirements' => [
    'min-quality' => 95,    // FORBIDDEN: Was 100%
    'min-complexity' => 90, // FORBIDDEN: Was 95%
    'min-architecture' => 95, // FORBIDDEN: Was 100%
    'min-style' => 95,      // FORBIDDEN: Was 100%
],

// ❌ FORBIDDEN: Adjusting infection threshold below 100%
{
  "mutators": {
    "@default": true
  },
  "minMsi": 99  // FORBIDDEN: Must be 100%
}
```

**Quality Enforcement Rules:**

1. **Never modify quality thresholds downward** in any configuration file:

   - `phpinsights.php` requirements section
   - `phpinsights-tests.php` requirements section
   - `infection.json5` mutation score indicator
   - `phpunit.xml.dist` coverage settings

2. **Always maintain or improve coverage** when adding new code:

   - Write tests for all new functionality
   - Ensure 100% line and branch coverage
   - Add mutation tests for complex logic
   - Update integration and E2E tests as needed

3. **Code quality must meet or exceed standards**:

   - Cyclomatic complexity below 5 per method
   - No architectural violations in Deptrac
   - PSR-12 code style compliance
   - Zero static analysis errors in Psalm

4. **Quality gate enforcement**:
   - `make ci` must output "✅ CI checks successfully passed!"
   - All quality checks must pass before code can be committed
   - Any quality regression must be fixed immediately
   - No exceptions or temporary quality downgrades allowed

### Quality Improvement Guidelines

**When refactoring or adding features:**

- **Improve quality scores** when possible, but never decrease them
- **Add more comprehensive tests** to catch edge cases
- **Reduce cyclomatic complexity** through better code organization
- **Enhance architectural separation** following DDD/CQRS patterns
- **Maintain backward compatibility** while improving code quality

**If quality checks fail:**

1. **Fix the underlying issue** rather than lowering standards
2. **Refactor complex code** to reduce complexity scores
3. **Add missing tests** to maintain coverage
4. **Update architecture** to resolve dependency violations
5. **Follow SOLID principles** to improve design quality

This ensures the codebase maintains its high quality standards while continuously improving over time.

## Additional Development Guidelines

### Code Comments and Self-Explanatory Code

**MANDATORY: Remove all inline comments and write self-explanatory code:**

- **No inline comments** in PHP, JavaScript, Docker, or any other code files
- **Self-explanatory code** should tell its own story through clear naming and structure
- **Function extraction** is preferred over explanatory comments
- If complex logic needs explanation, extract it into a method with a descriptive name

**Examples:**

```php
// ❌ BAD: Inline comment explaining code
if ($value === '') {
    return false; // empty is not "only spaces"
}

// ✅ GOOD: Self-explanatory method name
private function isEmptyButNotOnlySpaces(string $value): bool
{
    return $value === '';
}
```

### Symfony Built-in Features Preference

**MANDATORY: Use Symfony and API Platform built-in features instead of custom implementations:**

- **Validators**: Use Symfony's built-in validators instead of custom validation classes
- **Rate Limiting**: Use Symfony Rate Limiter component instead of custom rate limiting
- **Caching**: Use Symfony Cache component for rate limiting and other caching needs
- **API Platform**: Rely on automatic OpenAPI generation instead of manual `openapi.requestBody` decorations

**Rate Limiting Migration:**

- Replace custom database-based rate limiting with Symfony Rate Limiter
- Store rate limiting data in Symfony Cache (Redis) instead of database tables
- Remove violation of Single Responsibility Principle from tokens table

### Testing Standards

**MANDATORY: Use Faker library for all test data generation:**

- **No hardcoded values** in tests (emails, passwords, tokens, IDs, etc.)
- **Faker integration** already available in `tests/Unit/UnitTestCase.php` and `tests/Integration/IntegrationTestCase.php`
- **Dynamic test data** ensures tests are more robust and realistic

**Examples:**

```php
// ❌ BAD: Hardcoded test values
$email = 'test@example.com';
$token = 'test_token_123';
$password = 'password123';

// ✅ GOOD: Faker-generated values
$email = $this->faker->unique()->email();
$token = $this->faker->lexify('??????????');
$password = $this->faker->password(12);
```

### Database Migrations

**MANDATORY: Clean up empty migration files:**

- **Delete empty migrations** immediately if they contain no schema changes
- **Empty migrations** with only boilerplate code and no actual schema modifications should be removed
- **Check migration content** before committing to ensure they serve a purpose

### API Platform Best Practices

**MANDATORY: Follow API Platform patterns for clean API design:**

- **Input DTOs**: Use `input:` parameter in API Platform configuration instead of `openapi.requestBody`
- **Automatic schema generation**: Let API Platform generate OpenAPI schemas from DTOs
- **Empty response classes**: Use separate Empty DTO class for endpoints that don't return response bodies
- **HTTP status codes**: Use appropriate HTTP status codes (204 No Content) instead of success messages
- **No response messages**: For security operations like password reset, return only HTTP status codes

### Pluralization and Internationalization

**MANDATORY: Proper pluralization for time units and user-facing text:**

- **Time units**: Use correct singular/plural forms (1 hour vs 2 hours)
- **Dynamic pluralization**: Implement logic to handle both singular and plural forms
- **Consistent messaging**: Ensure all user-facing text follows proper grammar rules
