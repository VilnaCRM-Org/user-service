# AI Agent Skills (Claude Code, OpenAI, GitHub Copilot, Cursor)

This directory contains modular **AI-agnostic Skills** that work with any AI coding assistant. While originally designed for Claude Code, these skills are pure markdown files that any AI agent can read and follow.

## For Different AI Agents

### Claude Code Users

Skills are automatically discovered and invoked when relevant. You don't need to do anything special.

### OpenAI, GitHub Copilot, Cursor, and Other AI Agents

**Start here**: Read [AI-AGENT-GUIDE.md](AI-AGENT-GUIDE.md) for complete cross-platform usage instructions.

**Quick start**:

1. Read [SKILL-DECISION-GUIDE.md](SKILL-DECISION-GUIDE.md) to choose the right skill
2. Open the skill's `SKILL.md` file
3. Follow the execution steps
4. Check supporting files (`reference/`, `examples/`) as needed

## Available Skills

### 1. CI Workflow (`ci-workflow/`)

**Purpose**: Run comprehensive CI checks before committing changes

**When activated**:

- User asks to "run CI" or "run quality checks"
- Before finishing any task involving code changes
- Before creating pull requests

**What it does**:

- Executes `make ci` with all quality checks
- Guides through fixing failures (code style, static analysis, tests, mutations)
- Ensures "✅ CI checks successfully passed!" message appears
- Protects quality standards (never allows decreasing thresholds)

**Key commands**: `make ci`, `make phpcsfixer`, `make psalm`, `make phpinsights`, `make infection`

---

### 2. Testing Workflow (`testing-workflow/`)

**Purpose**: Run and manage different types of tests

**When activated**:

- Running tests (unit, integration, E2E, mutation, load)
- Debugging test failures
- Checking test coverage

**What it does**:

- Provides comprehensive testing guidance for all test types
- Explains debugging strategies for different failure types
- Covers mutation testing (Infection) strategies
- Documents load testing procedures
- Enforces 100% coverage and 0 escaped mutants

**Key commands**: `make unit-tests`, `make integration-tests`, `make behat`, `make infection`, `make load-tests`

---

### 3. Code Review Workflow (`code-review/`)

**Purpose**: Systematically retrieve and address PR code review comments

**When activated**:

- Handling code review feedback
- Addressing PR comments
- Refactoring based on reviewer suggestions

**What it does**:

- Uses `make pr-comments` to retrieve all unresolved comments
- Categorizes comments (committable suggestions, LLM prompts, questions, feedback)
- Provides systematic approach to implementing suggestions
- Guides comment response strategy
- Ensures all feedback is addressed with quality checks

**Key commands**: `make pr-comments`, `make ci`

---

### 4. Quality Standards (`quality-standards/`)

**Purpose**: Overview of protected quality thresholds and quick reference for all quality tools

**When activated**:

- Need to understand what quality metrics are protected
- Running comprehensive quality checks (`make ci`)
- Learning which specialized skill to use for specific issues

**What it does**:

- Documents all protected quality thresholds
- Provides quick reference for all quality tool commands
- Directs users to specialized skills for specific issues:
  - **Deptrac violations** → deptrac-fixer
  - **High complexity** → complexity-management
  - **Test coverage** → testing-workflow
  - **Architecture patterns** → implementing-ddd-architecture

**Key commands**: `make ci`, `make phpinsights`, `make psalm`, `make deptrac`

**Protected thresholds**:

- PHPInsights quality: 100%
- PHPInsights complexity: 94%
- PHPInsights architecture: 100%
- PHPInsights style: 100%
- Mutation testing MSI: 100%

---

### 5. Documentation Creation (`documentation-creation/`)

**Purpose**: Create comprehensive documentation suite from scratch for new projects

**When activated**:

- Setting up initial documentation for a new project
- Building complete documentation suite
- Major documentation rewrite
- Bootstrapping documentation for new services

**What it does**:

- Analyzes project structure and technology stack
- Creates all required `docs/*.md` files
- Provides templates for each documentation type
- Verifies all references against actual codebase
- Ensures consistent style and cross-linking

**Key files created**: `docs/main.md`, `docs/getting-started.md`, `docs/design-and-architecture.md`, `docs/developer-guide.md`, `docs/api-endpoints.md`, `docs/testing.md`, and 11+ more

**Note**: Use documentation-sync for updating existing docs

---

### 6. Documentation Synchronization (`documentation-sync/`)

**Purpose**: Keep `docs/` directory synchronized with code changes

**When activated**:

- Implementing new features or modifying existing ones
- Adding/changing API endpoints (REST or GraphQL)
- Modifying database schema or entities
- Changing architecture or configuration

**What it does**:

- Identifies which documentation files need updates
- Provides templates for documenting different change types
- Ensures consistency across documentation
- Validates examples and cross-references
- Updates API specifications (OpenAPI, GraphQL)

**Key files updated**: `docs/api-endpoints.md`, `docs/design-and-architecture.md`, `docs/developer-guide.md`, `docs/user-guide.md`

**Key commands**: `make generate-openapi-spec`, `make generate-graphql-spec`

---

### 7. Database Migrations (`database-migrations/`)

**Purpose**: Create and manage database migrations using Doctrine ORM (MySQL in this service)

**When activated**:

- Adding new entities
- Modifying entity fields
- Managing database schema changes
- Setting up test database

**What it does**:

- Guides entity creation (Domain layer, XML mapping, API Platform config)
- Documents database-specific features (custom types, indexes)
- Provides repository implementation patterns
- Explains migration best practices
- Covers testing with database

**Key commands**: `make doctrine-migrations-migrate`, `make doctrine-migrations-generate`, `make setup-test-db`

**Structure**: Multi-file with supporting guides:

- `entity-creation-guide.md` - Complete entity workflow
- `entity-modification-guide.md` - Modifying entities safely
- `repository-patterns.md` - Repository implementation
- `mongodb-specifics.md` - Database-specific notes (adapt to MySQL/ORM here)
- `reference/troubleshooting.md` - Database issues

---

### 8. Load Testing (`load-testing/`)

**Purpose**: Create and manage K6 load tests for REST and GraphQL APIs

**When activated**:

- Creating load tests
- Writing K6 scripts
- Testing API performance
- Debugging load test failures
- Setting up performance monitoring

**What it does**:

- Provides patterns for REST and GraphQL load tests
- Documents K6 script structure and configuration
- Explains deterministic testing (no random operations)
- Covers IRI handling and data generation
- Troubleshoots common load testing issues

**Key commands**: `make load-tests`, `make smoke-load-tests`, `make average-load-tests`, `make stress-load-tests`, `make spike-load-tests`

**Structure**: Multi-file with comprehensive guides:

- `rest-api-patterns.md` - REST API templates
- `graphql-patterns.md` - GraphQL patterns
- `examples/rest-customer-crud.js` - Complete REST example
- `examples/graphql-customer-crud.js` - Complete GraphQL example
- `reference/configuration.md` - K6 configuration
- `reference/utils-extensions.md` - Extending Utils class
- `reference/troubleshooting.md` - Common issues and solutions

---

### 9. Implementing DDD Architecture (`implementing-ddd-architecture/`)

**Purpose**: Design and implement DDD patterns (entities, value objects, aggregates, CQRS)

**When activated**:

- Creating new entities, value objects, or aggregates
- Implementing bounded contexts or modules
- Designing repository interfaces and implementations
- Learning proper layer separation (Domain/Application/Infrastructure)
- Code review for architectural compliance

**What it does**:

- Guides creation of rich domain models (not anemic)
- Implements CQRS pattern with Commands and Handlers
- Configures Domain Events and Event Subscribers
- Provides CodelyTV-style directory structure patterns
- Documents where to place new files

**Note**: For fixing existing Deptrac violations, use **deptrac-fixer** skill instead.

**Key commands**: `make deptrac`

**Structure**: Multi-file with comprehensive guides:

- `SKILL.md` - Core patterns and quick reference
- `REFERENCE.md` - Detailed workflows and layer responsibilities
- `DIRECTORY-STRUCTURE.md` - File placement based on CodelyTV patterns
- `examples/` - Complete working code examples (entities, value objects, CQRS)

**Layer dependencies**:

- Domain: NO external dependencies (pure PHP)
- Application: Domain, Infrastructure, Symfony, API Platform
- Infrastructure: Domain, Application, Symfony, Doctrine

---

### 10. Deptrac Fixer (`deptrac-fixer/`)

**Purpose**: Diagnose and fix Deptrac architectural violations automatically

**When activated**:

- `make deptrac` reports violations
- Error message containing "must not depend on"
- Domain layer has framework imports (Symfony, Doctrine, API Platform)
- Infrastructure directly calls Application handlers
- Any architectural boundary violation

**What it does**:

- Parses Deptrac violation messages and identifies root cause
- Provides step-by-step fix strategies for each violation type
- Creates Value Objects to replace framework validation
- Configures XML mappings instead of Doctrine annotations
- Uses Command/Event Bus instead of direct handler calls

**Note**: To understand DDD architecture patterns (why layers exist), see **implementing-ddd-architecture** skill.

**Key principle**: **Fix the code, NEVER modify `deptrac.yaml`**

**Key commands**: `make deptrac`

**Structure**: Multi-file with comprehensive examples:

- `SKILL.md` - Core diagnostic and fix patterns
- `REFERENCE.md` - Advanced patterns and edge cases
- `CODELY-STRUCTURE.md` - **CodelyTV directory hierarchy** (ls -la style)
- `examples/01-domain-symfony-validation.php` - Fixing validator violations
- `examples/02-domain-doctrine-annotations.php` - Removing Doctrine imports
- `examples/03-domain-api-platform.php` - Moving API Platform config
- `examples/04-infrastructure-handler.php` - Using bus pattern

**Common fixes**:

- Domain → Symfony: Extract validation to Value Objects
- Domain → Doctrine: Use XML mappings in `config/doctrine/`
- Domain → API Platform: Move to YAML config or Application DTOs
- Infrastructure → Handler: Use CommandBusInterface instead

---

### 11. Complexity Management (`complexity-management/`)

**Purpose**: Maintain and improve code quality using PHPInsights without decreasing thresholds

**When activated**:

- PHPInsights fails with complexity issues
- Cyclomatic complexity is too high
- Code quality score drops
- Refactoring for better maintainability

**What it does**:

- Identifies high complexity methods using PHPMD
- Provides refactoring strategies (Extract Method, Strategy Pattern, Early Returns)
- Maintains 94% complexity and 100% quality/architecture/style scores
- Documents complexity metrics and monitoring

**Key commands**: `make phpinsights`, `make phpmd`

**Structure**: Multi-file with comprehensive guides:

- `SKILL.md` - Core workflow and strategies
- `refactoring-strategies.md` - Detailed refactoring patterns
- `reference/complexity-metrics.md` - Understanding metrics
- `reference/analysis-tools.md` - Tool usage guide
- `reference/monitoring.md` - Tracking quality over time
- `reference/troubleshooting.md` - Common issues

---

### 12. OpenAPI Development (`openapi-development/`)

**Purpose**: Guide for contributing to the OpenAPI layer (factories/sanitizers/augmenters/cleaners)

**When activated**:

- Adding endpoint factories or OpenAPI transformers
- Fixing OpenAPI validation errors (Spectral, OpenAPI diff, Schemathesis)

**Key commands**: `make generate-openapi-spec`, `make validate-openapi-spec`, `make openapi-diff`, `make schemathesis-validate`

---

### 13. Query Performance Analysis (`query-performance-analysis/`)

**Purpose**: Detect N+1 queries, analyze slow queries with EXPLAIN, identify missing indexes, and ensure safe index migrations

**When activated**:

- New or modified endpoints are slow
- Profiler shows many database queries for single operation
- Need to detect N+1 query problems
- Query execution time is high
- Planning safe index migrations for production
- Need to verify index effectiveness

**What it does**:

- Detects and fixes N+1 query problems using eager loading
- Analyzes slow queries with EXPLAIN and EXPLAIN ANALYZE
- Identifies missing indexes based on query patterns
- Guides safe online index migrations with ALGORITHM=INPLACE
- Provides performance thresholds and testing strategies

**Key commands**: `docker compose exec database mariadb -u root -p$DB_PASSWORD db`, `EXPLAIN`, `EXPLAIN ANALYZE`

> **Note**: `DB_PASSWORD` is sourced from the `.env` file or environment variables. Default value in development is `root` (see `docker-compose.yml`).

**Structure**: Multi-file with comprehensive guides:

- `SKILL.md` - Core workflow and quick reference
- `examples/n-plus-one-detection.md` - Complete N+1 detection and fix guide
- `examples/slow-query-analysis.md` - EXPLAIN analysis walkthrough
- `reference/index-strategies.md` - When to use which index type
- `reference/mysql-slow-query-guide.md` - Slow query log documentation
- `reference/performance-thresholds.md` - Acceptable performance limits

**Performance thresholds**:

- GET single: <50ms target, 100ms max
- GET collection (100 items): <200ms target, 500ms max
- Query count per endpoint: <5 target, 10 max

---

### 14. API Platform CRUD (`api-platform-crud/`)

**Purpose**: Create complete REST API CRUD operations using API Platform 4 with DDD and CQRS patterns

**When activated**:

- Adding new API resources (entities with REST endpoints)
- Implementing CRUD operations (Create, Read, Update, Delete)
- Creating DTOs for input/output transformation
- Configuring API Platform operations, filters, or pagination
- Working with state processors or providers
- Setting up GraphQL alongside REST

**What it does**:

- Guides complete CRUD implementation in 10 steps
- Provides YAML-based resource configuration patterns
- Documents DTO patterns for Create, Put, Patch operations
- Explains state processor implementation with CQRS
- Covers filter and pagination configuration
- Shows IRI resolution for entity references
- Includes complete Customer entity example

**Key commands**: `make cache-clear`, `make generate-openapi-spec`, `make deptrac`

**Structure**: Multi-file with comprehensive guides:

- `SKILL.md` (Core workflow and 10-step guide)
- `examples/complete-customer-crud.md` (Full working example)
- `reference/filters-and-pagination.md` (Filter configuration)
- `reference/troubleshooting.md` (Common issues and solutions)

---

### 15. Structurizr Architecture Sync (`structurizr-architecture-sync/`)

**Purpose**: Maintain Structurizr C4 architecture diagrams in sync with code changes

**When activated**:

- Adding new components (controllers, handlers, services, repositories)
- Creating new entities or aggregates
- Modifying component relationships or dependencies
- Implementing new architectural patterns (CQRS, events, subscribers)
- Adding infrastructure components (databases, caches, message brokers)
- Refactoring that changes component structure
- After fixing Deptrac violations

**What it does**:

- Guides workspace.dsl updates for architectural changes
- Documents C4 model component identification
- Provides relationship patterns and DSL syntax reference
- Ensures diagrams stay synchronized with code
- Covers manual positioning and committing both workspace.dsl and workspace.json

**Key commands**: Access Structurizr at `http://localhost:${STRUCTURIZR_PORT:-8080}`

**Structure**: Multi-file with comprehensive guides:

- `SKILL.md` (Core workflow and 5-step quick start)
- `examples/cqrs-pattern.md` (CQRS documentation patterns)
- `examples/api-endpoint.md` (API endpoint documentation)
- `examples/domain-entity.md` (Entity documentation)
- `examples/refactoring.md` (Refactoring diagram updates)
- `reference/c4-model-guide.md` (C4 model fundamentals)
- `reference/component-identification.md` (What to document)
- `reference/dsl-syntax.md` (Structurizr DSL syntax)
- `reference/relationship-patterns.md` (Common relationships)
- `reference/workspace-template.md` (Complete template)
- `reference/common-mistakes.md` (Pitfalls and solutions)

---

### 16. Cache Management (`cache-management/`)

**Purpose**: Implement production-grade caching with cache keys/TTLs/consistency classes per query, SWR (stale-while-revalidate), explicit invalidation, and tests for stale reads and cache warmup.

**When activated**:

- Adding caching to repositories or expensive queries
- Implementing cache invalidation via domain events
- Defining cache keys, TTLs, and consistency requirements
- Implementing SWR pattern
- Testing cache behavior (stale reads, cold start, invalidation)

**What it does**:

- Enforces Decorator pattern (`CachedXxxRepository` wraps inner repository)
- Introduces centralized `CacheKeyBuilder` to prevent key drift
- Uses `TagAwareCacheInterface` and tag-based invalidation
- Implements best-effort invalidation (never fail business ops)
- Provides example implementations and cache test patterns

**Key commands**: `make ci`, `make unit-tests`, `make integration-tests`

**Structure**: Multi-file with comprehensive guides:

- `SKILL.md` - Core workflow and quick reference
- `examples/cache-implementation.md` - Complete cache decorator implementation
- `examples/cache-testing.md` - Cache testing patterns and scenarios
- `reference/cache-policies.md` - Cache policy components and TTL guidance
- `reference/invalidation-strategies.md` - Invalidation strategies (event-driven, tag-based, time-based)
- `reference/swr-pattern.md` - Stale-while-revalidate pattern guide

---

## How Skills Work

### Cross-Platform Compatibility

These skills work across different AI agents:

| AI Agent                 | How It Works                                              |
| ------------------------ | --------------------------------------------------------- |
| **Claude Code**          | Automatic discovery and invocation via `Skill` tool       |
| **OpenAI (GPT-4/CODEX)** | Manual: Read skill markdown files and follow instructions |
| **GitHub Copilot**       | Manual: Read skill markdown files for guidance            |
| **Cursor**               | Manual: Use as reference documentation                    |
| **Other AI agents**      | Manual: Read markdown files as structured guides          |

### Automatic Discovery (Claude Code)

Claude Code automatically discovers and loads Skills from this directory. No manual activation is required.

### Invocation

**For Claude Code**: Skills are **model-invoked** — Claude autonomously decides when to use them based on:

- Task context and user request
- Skill descriptions (the `description` field in YAML frontmatter)
- Relevance to current work

**For OpenAI and Other Agents**: You manually read the appropriate skill file based on your task (see [AI-AGENT-GUIDE.md](AI-AGENT-GUIDE.md))

### Skill Structure

Each Skill consists of:

- A directory (e.g., `ci-workflow/`)
- A `SKILL.md` file with YAML frontmatter:

  ```yaml
  ---
  name: skill-name
  description: What this skill does and when to use it
  ---
  Detailed instructions...
  ```

**Multi-file Structure** (for complex skills):

Large skills use a multi-file approach following Claude Code best practices:

- **Main `SKILL.md`**: Core workflow and quick reference (<300 lines)
- **Supporting files**: Detailed patterns, guides, and reference documentation
- **`examples/`**: Complete working code examples
- **`reference/`**: Troubleshooting, configuration, and advanced topics
- **`update-scenarios/`**: Scenario-specific patterns (e.g., for documentation-sync)

**Example** - The `load-testing` skill structure:

```
load-testing/
├── SKILL.md (210 lines)
├── rest-api-patterns.md
├── graphql-patterns.md
├── examples/
│   ├── rest-customer-crud.js
│   └── graphql-customer-crud.js
└── reference/
    ├── configuration.md
    ├── utils-extensions.md
    └── troubleshooting.md
```

### Creating New Skills

To add a new Skill:

1. Create a directory: `.claude/skills/your-skill-name/`
2. Create `SKILL.md` with YAML frontmatter
3. Write clear description with usage triggers
4. Keep main file concise (<300 lines for complex skills)
5. Extract details into supporting files if needed
6. Include examples and commands

**Best practices**:

- Keep Skills focused on single capabilities
- Use lowercase-hyphen naming (e.g., `my-skill-name`)
- Write specific descriptions with concrete trigger terms
- Main SKILL.md should be quick reference, details in supporting files
- Include practical examples and actual commands
- Test Skills with real use cases

## Skill vs CLAUDE.md vs AGENTS.md

### CLAUDE.md (Concise reference)

- **Purpose**: Concise project instructions automatically loaded by Claude
- **Content**: Essential project overview, commands, architecture basics
- **Location**: Root directory
- **Usage**: Automatic context for every conversation

### AGENTS.md (Comprehensive guidelines)

- **Purpose**: Comprehensive repository guidelines and best practices
- **Content**: Complete development workflow, quality standards, troubleshooting
- **Location**: Root directory
- **Usage**: Reference documentation for complex scenarios

### Skills (Modular skill set)

- **Purpose**: Modular, reusable capabilities for specific workflows
- **Content**: Step-by-step instructions for focused tasks with supporting documentation
- **Location**: `.claude/skills/`
- **Usage**: Automatically activated when relevant to task
- **Structure**: Each Skill provides a focused quick-reference `SKILL.md` with additional supporting files for deeper guidance when needed
- **Scope**: Combined Skills coverage spans detailed examples, troubleshooting guides, and workflow playbooks

## Integration with Development Workflow

Skills integrate seamlessly with the project's development workflow:

1. **Before coding**: Documentation Sync Skill identifies what needs updating
2. **During coding**:
   - Quality Standards Skill maintains high code quality
   - Database Migrations Skill guides entity and schema changes
3. **After coding**:
   - Testing Workflow ensures comprehensive coverage
   - Load Testing Skill validates API performance
4. **Before commit**:
   - CI Workflow validates all quality checks pass
   - Documentation Sync confirms docs are updated
5. **During review**: Code Review Workflow handles PR feedback systematically

## Success Metrics

All Skills enforce these project standards:

- ✅ CI checks successfully passed
- 100% test coverage maintained
- 100% mutation score (0 escaped mutants)
- All quality thresholds met or exceeded
- Documentation synchronized with code
- Architecture boundaries respected

## Questions or Issues?

If a Skill needs improvement or you encounter issues:

1. Review the Skill's SKILL.md for detailed instructions
2. Check AGENTS.md for comprehensive guidelines
3. Consult CLAUDE.md for quick reference
4. Ask Claude to use a specific Skill if needed
