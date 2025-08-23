# User Service - GitHub Copilot Instructions

User Service is a PHP 8.3+ microservice built with Symfony 7.2, API Platform 4.1, and GraphQL. It provides user account management and authentication within the VilnaCRM ecosystem using OAuth Server, REST API, and GraphQL. The project follows hexagonal architecture with DDD & CQRS patterns and includes comprehensive testing with 193 test files across unit, integration, and E2E test suites.

**Always reference these instructions first and fallback to search or bash commands only when you encounter unexpected information that does not match the info here.**

## Quick Start Summary
1. `make build` (15-30 min, NEVER CANCEL)
2. `make start` (5-10 min, includes database, Redis, LocalStack)  
3. `make install` (3-5 min, PHP dependencies)
4. `make doctrine-migrations-migrate` (1-2 min)
5. Verify: https://localhost/api/docs, https://localhost/api/graphql
6. Always run `make phpcsfixer && make psalm` before committing

## Working Effectively

### Bootstrap, Build, and Test the Repository
- Install Docker and Docker Compose (required for database and services)
- `make build` -- builds Docker images. Takes 15-30 minutes on first run. NEVER CANCEL. Set timeout to 45+ minutes.
- `make start` -- starts Docker environment including database, Redis, and application containers. Takes 5-10 minutes. NEVER CANCEL. Set timeout to 15+ minutes.
- `make install` -- installs PHP dependencies via Composer. Takes 3-5 minutes. Set timeout to 10+ minutes.
- `make doctrine-migrations-migrate` -- runs database migrations. Takes 1-2 minutes.

### Environment Limitations
- **CRITICAL**: In restricted network environments (CI, sandboxes), Docker builds may fail due to Alpine package repository access being blocked. In such cases:
  - The error "Permission denied" when fetching Alpine packages indicates network restrictions
  - Use local PHP development instead: `composer install --no-dev` and `APP_ENV=prod php bin/console` 
  - Document the limitation as "Docker build fails due to firewall limitations"

### Run the Application
- **Always run the bootstrapping steps first**
- Start all services: `make start`
- Web API: Access via https://localhost/api/docs (REST API documentation)
- GraphQL: Access via https://localhost/api/graphql
- Architecture diagrams: http://localhost:8080/workspace/diagrams

### Testing
- Unit tests: `make unit-tests` -- tests 193 test files. Takes 2-3 minutes. Set timeout to 10+ minutes.
- Integration tests: `make integration-tests` -- tests database and external services. Takes 3-5 minutes. Set timeout to 15+ minutes.
- E2E tests: `make behat` -- tests 6 feature files (user operations, GraphQL, OAuth, localization). Takes 5-10 minutes. Set timeout to 20+ minutes.
- All tests: `make all-tests` -- runs all test suites. Takes 8-15 minutes total. NEVER CANCEL. Set timeout to 30+ minutes.
- Coverage: `make tests-with-coverage` -- generates code coverage report. Takes 10-15 minutes. Set timeout to 25+ minutes.

### Code Quality and Linting
- Always run these before committing or the CI will fail:
- `make phpcsfixer` -- PHP code style fixer. Takes 1-2 minutes.
- `make psalm` -- static analysis. Takes 2-3 minutes.
- `make phpinsights` -- code quality analysis. Takes 3-5 minutes.
- `make deptrac` -- architecture dependency checking. Takes 1-2 minutes.

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