# Copilot Coding Agent Configuration

This repository is configured to work with GitHub Copilot coding agents. The setup avoids firewall restrictions by using Docker containers with pre-installed dependencies.

## Quick Setup

**For Copilot Agents**: Use the setup script instead of running `composer install` directly:

```bash
./.devcontainer/setup.sh
```

**Alternative**: Use the make command (recommended):

```bash
make start
```

## Why This Configuration?

The Copilot coding agent was failing when trying to run `composer install` due to firewall restrictions that prevent fetching dependencies at runtime. This configuration solves the problem by:

1. **Using Docker containers** with dependencies pre-installed during the build phase (before firewall restrictions)
2. **Leveraging the existing GitHub Actions pattern** that already works correctly
3. **Providing multiple setup methods** for different environments

## Setup Methods

### Method 1: Docker + Make (Recommended)
```bash
make start                    # Starts all services with pre-installed dependencies
make setup-test-db           # Sets up test database
make tests-with-coverage     # Runs tests
```

### Method 2: DevContainer
Use the `.devcontainer/devcontainer.json` configuration for:
- VS Code Dev Containers
- GitHub Codespaces
- Compatible IDEs

### Method 3: Manual Setup (if needed)
```bash
# Only if running inside a container where dependencies aren't pre-installed
./.devcontainer/setup.sh
```

## Available Services

After running `make start`, the following services are available:

- **Application**: http://localhost:8081
- **Database (MariaDB)**: localhost:3306
- **Redis**: localhost:6379
- **MailCatcher Web UI**: http://localhost:1080
- **MailCatcher SMTP**: localhost:1025
- **LocalStack (AWS simulation)**: http://localhost:4566
- **Structurizr (Architecture docs)**: http://localhost:8080

## Common Commands

```bash
make help                    # Show all available commands
make sh                      # Access the PHP container shell
make logs                    # Show application logs
make tests-with-coverage     # Run tests with coverage
make psalm                   # Run static analysis
make phpinsights            # Run code quality analysis
```

## Environment Variables

The application uses these environment files:
- `.env` - Main environment configuration
- `.env.test` - Test environment overrides

## Troubleshooting

### If you get firewall/network errors:
1. Use `make start` instead of direct `composer install`
2. Make sure Docker is running
3. Dependencies are installed during Docker build, not at runtime

### If containers won't start:
```bash
make down                    # Stop all containers
make build                   # Rebuild images
make start                   # Start fresh
```

### If you need to access the container:
```bash
make sh                      # Interactive shell in PHP container
```

## Architecture

This project follows Clean Architecture principles with:
- **Domain Layer**: Core business logic
- **Application Layer**: Use cases and command handlers  
- **Infrastructure Layer**: Database, external services
- **Presentation Layer**: API endpoints, GraphQL resolvers

See `workspace.dsl` and `workspace.json` for detailed architecture documentation.