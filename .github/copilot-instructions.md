# User Service - GitHub Copilot Instructions

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