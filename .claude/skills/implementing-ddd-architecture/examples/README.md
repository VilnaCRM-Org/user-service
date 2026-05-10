# DDD Architecture Examples

This directory contains comprehensive code examples demonstrating Domain-Driven Design (DDD) patterns and Hexagonal Architecture principles used in this project.

## Files Overview

### 01-entity-example.php

**Complete Rich Domain Entity Example**

Demonstrates:

- Rich domain model (NOT anemic)
- Business logic encapsulated in entity methods
- Aggregate root pattern with domain events
- Named constructors for clarity
- Invariant enforcement
- State transitions
- NO external dependencies (pure PHP)

Key Concepts:

- `extends AggregateRoot` for event recording
- Business methods (not setters): `publish()`, `changePrice()`, `rename()`
- Domain events: `ProductCreated`, `ProductPriceChanged`, `ProductPublished`
- Immutable value objects: `ProductName`, `Money`, `ProductStatus`

### 02-value-object-examples.php

**Pragmatic Value Object Usage (When and When NOT to use them)**

**IMPORTANT**: This example shows when to use Value Objects and when to use primitives.

Demonstrates:

- ❌ ANTI-PATTERNS: Email/CustomerName VOs with validation (use YAML instead)
- ✅ CORRECT: Actual Customer entity with primitives + YAML validation
- ✅ WHEN TO USE: Money (with operations), ProductStatus (type-safe enum), ULID (special concept)
- ✅ DECISION CRITERIA: Only use VOs when you need behavior/operations

Key Principles:

- **Default to primitives** (string $email, string $phone)
- **Validate in YAML** (config/validator/), NOT in Value Objects
- **Add VOs only when needed** (Money::add(), ULID conversion logic)
- **Follow actual codebase patterns** (src/Core/Customer uses primitives)

### 03-cqrs-pattern-example.php

**CQRS Pattern (Command Query Responsibility Segregation)**

Demonstrates:

- Commands (write operations): `CreateProductCommand`, `UpdateProductPriceCommand`
- Command Handlers: Orchestration without business logic
- Repository pattern: Interface in Domain, implementation in Infrastructure
- Command Bus usage
- API Platform integration with processors
- Complete flow: DTO → Command → Handler → Domain → Repository

Key Concepts:

- Commands are immutable DTOs representing intent
- Handlers implement `CommandHandlerInterface`
- Handlers orchestrate, domain contains logic
- Auto-registration via service tags
- Hexagonal architecture: Ports (interfaces) and Adapters (implementations)

### 04-fixing-deptrac-violations.php

**Common Deptrac Violations and Fixes (PRAGMATIC APPROACH)**

**IMPORTANT**: Uses ACTUAL codebase patterns (YAML validation, primitives, factories).

Demonstrates:

- **BEFORE/AFTER** code for common violations
- How to identify and fix architectural issues using actual patterns
- Why you should NEVER modify `deptrac.yaml`

Violations Covered:

1. **Domain → Symfony (validators)**: Fix with YAML validation + primitives
2. **Domain → Doctrine (annotations)**: Fix with XML mappings
3. **Domain → API Platform (attributes)**: Fix with YAML config
4. **Infrastructure → Application (handlers)**: Fix with Command/Event Bus
5. **Using 'new' instead of factories**: Fix with Factory pattern
6. **Anemic domain models**: Fix by moving logic to entities

Step-by-Step Workflow:

- Run `make deptrac`
- Read violation message
- Identify the problem
- Plan the refactor
- Fix the code
- Verify with `make deptrac`

## How to Use These Examples

### For LLM Agents

When working on a task:

1. **Creating an Entity?**
   → Reference `01-entity-example.php`

   - Extend `AggregateRoot`
   - Use named constructors
   - Business logic in methods, not setters
   - Record domain events

2. **Need Validation?**
   → Reference `02-value-object-examples.php`

   - **Default**: Use YAML validation (config/validator/)
   - **Primitives**: string $email, string $phone (NOT Value Objects)
   - **Value Objects**: Only when you need behavior (Money::add(), ULID)
   - **See decision criteria** in example file

3. **Implementing a Use Case?**
   → Reference `03-cqrs-pattern-example.php`

   - Create Command (intent)
   - Create Handler (orchestration)
   - Call domain methods
   - Use repository for persistence

4. **Deptrac Violation?**
   → Reference `04-fixing-deptrac-violations.php`
   - Find similar violation
   - Apply the fix pattern
   - NEVER change `deptrac.yaml`

### For Developers

These examples serve as:

- **Templates** for new features
- **Reference** for architectural patterns
- **Training material** for onboarding
- **Standards documentation**

## Layer Dependency Rules

```
Infrastructure → Application → Domain
         ↓            ↓           ↓
     External      Use Cases   Business Logic
```

### Domain Layer

- **Allowed**: Pure PHP, domain value objects
- **Forbidden**: Symfony, Doctrine, API Platform, ANY framework

### Application Layer

- **Allowed**: Domain, Infrastructure, Symfony, API Platform, GraphQL
- **Forbidden**: Business logic (delegate to Domain)

### Infrastructure Layer

- **Allowed**: Domain, Application, Symfony, Doctrine
- **Forbidden**: Business logic (delegate to Domain)

## Quick Checklist

Before committing code, ensure:

- [ ] `make deptrac` passes with zero violations
- [ ] Domain layer has NO framework imports
- [ ] Business logic is in Domain entities, NOT in handlers
- [ ] Handlers only orchestrate, don't contain logic
- [ ] **Validation uses YAML** (config/validator/), NOT annotations or VOs
- [ ] **Primitives by default** (string $email), VOs only when needed (Money, ULID)
- [ ] **Factories used in production code**, NOT direct 'new' keyword
- [ ] Commands implement `CommandInterface`
- [ ] Handlers implement `CommandHandlerInterface`
- [ ] Repository interfaces in Domain, implementations in Infrastructure
- [ ] Doctrine mappings use XML, not annotations
- [ ] API Platform config in YAML, not attributes
- [ ] Aggregates extend `AggregateRoot` and use `record()` for events

## Additional Resources

- **Project Documentation**: `CLAUDE.md` (project root) - Development commands and workflow
- **Architecture Guidelines**: `.claude/skills/ddd-architecture/skill.md` - Primary DDD documentation
- **Deptrac Config**: `deptrac.yaml` (project root) - Architectural layer definitions
- **CodelyTV Example**: https://github.com/CodelyTV/php-ddd-example - Reference implementation

## Common Patterns Summary

| Pattern                   | Domain    | Application  | Infrastructure  |
| ------------------------- | --------- | ------------ | --------------- |
| Entities                  | ✅ Define | ❌           | ❌              |
| Value Objects             | ✅ Define | ❌           | ❌              |
| Repository Interface      | ✅ Define | ❌           | ❌              |
| Repository Implementation | ❌        | ❌           | ✅ Implement    |
| Commands                  | ❌        | ✅ Define    | ❌              |
| Command Handlers          | ❌        | ✅ Implement | ❌              |
| Domain Events             | ✅ Define | ❌           | ❌              |
| Event Subscribers         | ❌        | ✅ Implement | ❌              |
| DTOs                      | ❌        | ✅ Define    | ❌              |
| Transformers              | ❌        | ✅ Implement | ❌              |
| API Processors            | ❌        | ✅ Implement | ❌              |
| Doctrine Mappings         | ❌        | ❌           | ✅ Config (XML) |
| Message Bus Impl          | ❌        | ❌           | ✅ Implement    |

---

**Remember**: These examples are living documentation. They reflect the actual patterns used in this codebase. Follow them closely to maintain architectural consistency.
