# Instructions for GitHub Copilot Coding Agent

## IMPORTANT: Firewall Bypass Configuration

This repository is specifically configured to work with GitHub Copilot coding agents that face firewall restrictions. **DO NOT run `composer install` directly** as it will fail due to network restrictions.

## Setup Commands (Use These Instead)

### Primary Setup (Recommended)
```bash
make copilot-setup
```
This command:
1. Starts Docker containers with pre-installed dependencies
2. Sets up the database
3. Configures the test environment
4. Bypasses firewall restrictions

### Alternative Setup
```bash
make start                 # Start environment
make setup-test-db        # Setup test database
```

### If Running in Container Already
```bash
./.devcontainer/setup.sh
```

## Why This Configuration Exists

The Copilot coding agent was failing with this error pattern:
```
composer install 
# -> Fails due to firewall restrictions preventing package downloads
```

**Solution**: Use Docker containers where dependencies are installed during the build phase (before firewall activation).

## Development Workflow

1. **Initial Setup**: `make copilot-setup`
2. **Run Tests**: `make tests-with-coverage`
3. **Code Analysis**: `make psalm`
4. **Code Formatting**: `make phpcsfixer`
5. **Access Container**: `make sh`

## Available Services After Setup

- Application: http://localhost:8081
- Database: localhost:3306 (MariaDB)
- Redis: localhost:6379
- MailCatcher: http://localhost:1080
- LocalStack: http://localhost:4566

## Common Tasks

### Testing
```bash
make unit-tests           # Unit tests only
make integration-tests    # Integration tests only
make tests-with-coverage  # All tests with coverage
make behat               # Behavioral tests
```

### Database Operations
```bash
make doctrine-migrations-migrate  # Run migrations
make load-fixtures               # Load test data
make setup-test-db              # Setup test database
```

### Code Quality
```bash
make psalm              # Static analysis
make phpinsights       # Code quality insights
make phpcsfixer        # Fix code style
make deptrac           # Architecture validation
```

### Container Management
```bash
make sh                # Access PHP container shell
make logs              # View container logs
make down              # Stop all containers
make build             # Rebuild containers
```

## Troubleshooting

### Problem: "Could not open input file: composer.phar"
**Solution**: Use `make install` instead of direct composer commands

### Problem: Network timeouts during dependency installation
**Solution**: Dependencies are pre-installed in Docker images, use `make start`

### Problem: Permission denied errors
**Solution**: Run commands through make targets which handle Docker permissions

### Problem: Database connection errors
**Solution**: Run `make setup-test-db` to ensure database is properly configured

## File Structure Reference

```
├── .copilot/           # Copilot agent configuration
├── .devcontainer/      # DevContainer configuration
├── src/               # Application source code
├── tests/             # Test files
├── config/            # Symfony configuration
├── docker-compose.yml # Docker services definition
└── Makefile          # Build and development commands
```

## Environment Variables

Key environment files:
- `.env` - Main configuration
- `.env.test` - Test environment settings

## Architecture Notes

This is a Symfony-based microservice following Clean Architecture:
- **Domain**: Core business logic (src/*/Domain/)
- **Application**: Use cases (src/*/Application/) 
- **Infrastructure**: External concerns (src/*/Infrastructure/)

## Security Notes

- OAuth2 server integration
- JWT authentication
- Request/response validation
- SQL injection protection via Doctrine ORM