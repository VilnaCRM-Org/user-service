# Structurizr Architecture Sync - Examples

Practical examples for common Structurizr workspace.dsl update scenarios.

## Available Examples

### 1. [CQRS Pattern](cqrs-pattern.md)

**When to use**: Implementing command handlers with CQRS pattern

**Covers**:

- Adding command handlers
- Domain entities and value objects
- Domain events
- Event subscribers
- Repository interfaces (hexagonal ports)
- Complete event-driven flow

**Scenario**: Creating a new user using CQRS with event-driven architecture

---

### 2. [API Endpoint](api-endpoint.md)

**When to use**: Adding REST or GraphQL API endpoints

**Covers**:

- Controllers and API Platform providers
- Item providers and state processors
- GraphQL resolvers
- UUID transformers
- Request flow documentation
- Read vs. write operation patterns

**Scenario**: Implementing GET /api/users/{id} REST endpoint

---

### 3. [Domain Entity](domain-entity.md)

**When to use**: Creating new domain entities and aggregates

**Covers**:

- Aggregate roots
- Value objects (when to include/exclude)
- Domain events
- Repository interfaces and implementations
- Event subscribers
- Entity relationships

**Scenario**: Implementing Order aggregate with value objects and events

---

### 4. [Refactoring](refactoring.md)

**When to use**: Updating workspace.dsl during code refactoring

**Covers**:

- Splitting handlers (Single Responsibility Principle)
- Extracting domain services
- Moving components between layers
- Introducing interfaces (hexagonal architecture)
- Renaming components
- Removing components

**Scenario**: Refactoring monolithic UserCommandHandler into separate handlers

---

## How to Use These Examples

### 1. Choose the Right Example

Match your scenario to one of the examples above:

- **Adding features with CQRS** → CQRS Pattern
- **Creating API endpoints** → API Endpoint
- **Creating domain model** → Domain Entity
- **Changing existing architecture** → Refactoring

### 2. Follow the Step-by-Step Guide

Each example provides:

1. **Scenario description**: What you're implementing
2. **Components to add**: What to document
3. **Step-by-step instructions**: How to update workspace.dsl
4. **Complete DSL section**: Copy-paste ready code
5. **Visual result**: What the diagram will show
6. **Verification checklist**: Ensure completeness
7. **Common questions**: FAQ for the scenario

### 3. Adapt to Your Needs

Examples are templates - modify them for your specific:

- Component names
- Layer structure
- Relationships
- Business logic

### 4. Validate Your Changes

After following an example:

```bash
# Validate DSL syntax
structurizr-cli validate workspace.dsl

# Generate diagram
docker run -it --rm -p 8080:8080 \
  -v $(pwd):/usr/local/structurizr \
  structurizr/lite

# Access at http://localhost:8080
```

## Quick Reference

| You're Adding...                | Use Example   | Key Concepts                  |
| ------------------------------- | ------------- | ----------------------------- |
| Command handler with events     | CQRS Pattern  | Handlers, events, subscribers |
| REST API endpoint               | API Endpoint  | Controllers, providers        |
| GraphQL operation               | API Endpoint  | Resolvers, queries            |
| New entity/aggregate            | Domain Entity | Aggregates, value objects     |
| Event subscriber                | CQRS Pattern  | Event flow, messaging         |
| Splitting existing component    | Refactoring   | Component removal/addition    |
| Moving component between layers | Refactoring   | Layer boundaries              |
| Repository implementation       | Domain Entity | Hexagonal ports/adapters      |

## Combining Examples

Some scenarios require combining multiple examples:

### Adding a Complete Feature

1. **Domain Entity** → Create the aggregate
2. **CQRS Pattern** → Add command handlers
3. **API Endpoint** → Expose via REST/GraphQL
4. **Refactoring** (if needed) → Optimize structure

### Event-Driven Architecture

1. **Domain Entity** → Create entity with events
2. **CQRS Pattern** → Add handlers and subscribers
3. **Refactoring** (if needed) → Split responsibilities

## Tips for Success

### 1. Start Simple

- Add core components first
- Add relationships second
- Validate incrementally

### 2. Focus on Architecture

- Document architecturally significant components
- Omit DTOs and utilities
- Show layer boundaries clearly

### 3. Use Consistent Naming

- Match class names: `UserCommandHandler` → `userCommandHandler`
- Use descriptive relationships: "uses for persistence" not "uses"
- Follow layer grouping: Application/Domain/Infrastructure

### 4. Validate Visually

- Generate diagrams after changes
- Check for clarity and logical flow
- Ensure layer separation is visible

### 5. Update Incrementally

- Update workspace.dsl with each PR
- Don't wait for major refactors
- Keep documentation in sync with code

## Common Patterns Across Examples

All examples demonstrate:

- **Layer grouping**: Application/Domain/Infrastructure
- **Hexagonal architecture**: Ports and adapters pattern
- **Relationship clarity**: Descriptive relationship labels
- **Component types**: Consistent typing (Handler, Entity, Repository, etc.)
- **External dependencies**: Explicit database/broker documentation

## When Examples Don't Cover Your Case

If you don't find an exact match:

1. **Find the closest example** (e.g., similar component type)
2. **Review reference documentation**:
   - [Component Identification Guide](../reference/component-identification.md)
   - [Relationship Patterns](../reference/relationship-patterns.md)
   - [DSL Syntax Reference](../reference/dsl-syntax.md)
3. **Follow general workflow** from main [SKILL.md](../SKILL.md)
4. **Validate frequently** to catch errors early

## Contributing Examples

Have a useful scenario not covered here? Consider:

1. Document your pattern
2. Create a pull request
3. Help others learn from your experience

## Need More Help?

- **Main skill documentation**: [SKILL.md](../SKILL.md)
- **Reference documentation**:
  - [Workspace Template](../reference/workspace-template.md) - Complete workspace.dsl structure
  - [Common Mistakes](../reference/common-mistakes.md) - Pitfalls and solutions
  - [Component Identification](../reference/component-identification.md) - What to document
  - [Relationship Patterns](../reference/relationship-patterns.md) - Common patterns
  - [DSL Syntax](../reference/dsl-syntax.md) - Complete syntax reference
  - [C4 Model Guide](../reference/c4-model-guide.md) - Understanding C4
- **Decision guide**: `.claude/skills/SKILL-DECISION-GUIDE.md`
- **External resources**:
  - C4 Model documentation: <https://c4model.com/>
  - Structurizr DSL docs: <https://docs.structurizr.com/dsl>
  - User Service example (VilnaCRM organization reference): <https://github.com/VilnaCRM-Org/user-service>
