# C4 Model Fundamentals

## What is C4?

The C4 model is a hierarchical set of diagrams for visualizing software architecture at different levels of abstraction:

1. **Context** - System in its environment (users, external systems)
2. **Container** - High-level technology choices (applications, databases)
3. **Component** - Components within a container (classes, modules)
4. **Code** - Implementation details (class diagrams, ER diagrams)

## Our Focus: Component Diagrams

For this project, we focus on **Level 3: Component diagrams** within the User Service container.

### Component Diagram Purpose

Show the internal structure of the User Service:

- Major building blocks (controllers, handlers, repositories)
- Responsibilities of each component
- Relationships and interactions between components
- Architectural layer groupings (Application/Domain/Infrastructure)

## Key Concepts

### Components

**Definition**: A grouping of related functionality behind a well-defined interface.

**In our codebase**:

- Controllers (API endpoints)
- Command handlers (CQRS pattern)
- Event subscribers (domain event handlers)
- Entities and value objects (domain model)
- Repositories (data access)
- External services (databases, caches, brokers)

**Not components** (too granular):

- DTOs
- Simple interfaces without logic
- Utility functions
- Test classes

### Relationships

**Definition**: Dependencies between components.

**Types**:

- **Uses**: Component A uses component B's functionality
- **Creates**: Component A instantiates component B
- **Triggers**: Event A triggers subscriber B
- **Implements**: Component implements an interface
- **Stores/Retrieves**: Repository interacts with entity
- **Persists to**: Repository writes to database
- **Sends via**: Component sends messages via broker

### Groupings

**Purpose**: Organize components by architectural layer for visual clarity.

**In our architecture**:

- `group "Application"` - Entry points and use case orchestration
- `group "Domain"` - Pure business logic
- `group "Infrastructure"` - External concerns and implementations

## C4 Model Benefits

### 1. Shared Understanding

- Developers see the same architecture
- New team members onboard faster
- Architectural decisions are visible

### 2. Architecture Validation

- Identify layer violations visually
- Spot missing relationships
- Detect architectural drift

### 3. Documentation as Code

- Architecture docs stay synchronized
- Version controlled alongside code
- Easily reviewable in PRs

### 4. Multiple Levels of Detail

- High-level overview for stakeholders
- Detailed view for developers
- Zoom in/out based on audience

## Our Implementation

### Workspace Structure

```text
workspace.dsl
├── Model
│   └── Software System: VilnaCRM
│       └── Container: User Service
│           ├── Component Groups
│           │   ├── Application (Controllers, Handlers)
│           │   ├── Domain (Entities, Value Objects, Events)
│           │   └── Infrastructure (Repositories, Buses, Subscribers)
│           ├── External Dependencies (Database, Cache, Broker)
│           └── Relationships
└── Views
    ├── Component View: Components_All
    └── Styles (Colors, Shapes)
```

### Visual Conventions

**Colors**:

- Blue (#34abeb): Standard components
- White: Text

**Shapes**:

- Rectangle: Standard component
- Cylinder: Database/Cache/Broker

**Groupings**:

- Clear boxes around architectural layers
- Separator: `/` (e.g., "Application/Controllers")

## Best Practices

### 1. Right Level of Abstraction

Show components at the "architectural unit" level:

✅ **Good**: `UserCommandHandler`, `UserRepository`, `User` entity

❌ **Too detailed**: `UserId` DTO, `UserMapper`, utility functions

### 2. Clear Relationships

Use descriptive relationship labels:

✅ **Good**: `handler -> repository "uses for persistence"`

❌ **Vague**: `handler -> repository`

### 3. Layer Separation

Make architectural boundaries visible:

```dsl
group "Application" {
    # Entry points
}

group "Domain" {
    # Business logic
}

group "Infrastructure" {
    # Technical concerns
}
```

### 4. External Dependencies Explicit

Show all external systems:

```dsl
database = component "Database" "Stores application data" "MariaDB" {
    tags "Database"
}
```

### 5. Consistency

- Use consistent naming (match class names)
- Use consistent relationship descriptions
- Use consistent component types

## Alignment with Hexagonal Architecture

Our C4 component diagram reflects hexagonal architecture principles:

| Hexagon Layer        | C4 Group       | Contains                                               |
| -------------------- | -------------- | ------------------------------------------------------ |
| **Driving Adapters** | Application    | Controllers, API handlers                              |
| **Application Core** | Application    | Command handlers, use cases                            |
| **Domain**           | Domain         | Entities, value objects, domain logic                  |
| **Driven Adapters**  | Infrastructure | Repositories, external service clients                 |
| **Ports**            | Domain         | Interfaces (repository interfaces, factory interfaces) |

**Visual representation**: The C4 diagram shows how hexagonal layers interact while maintaining clean boundaries.

## Common Pitfalls

### Pitfall 1: Over-Documentation

**Problem**: Adding every single class

**Solution**: Focus on architecturally significant components

### Pitfall 2: Under-Documentation

**Problem**: Missing key relationships or components

**Solution**: Document all components that participate in use cases

### Pitfall 3: Stale Diagrams

**Problem**: Diagrams drift from actual code

**Solution**: Update workspace.dsl in the same PR as code changes

### Pitfall 4: Unclear Relationships

**Problem**: Relationships without context

**Solution**: Use descriptive labels (e.g., "uses for validation" not just "uses")

### Pitfall 5: Mixed Abstraction Levels

**Problem**: Mixing high-level components with implementation details

**Solution**: Stay at the "architectural component" level consistently

## Further Reading

- **C4 Model Official Site**: <https://c4model.com/>
- **C4 Model FAQ**: <https://c4model.com/#faq>
- **Structurizr Documentation**: <https://docs.structurizr.com/>
- **Simon Brown's Blog**: <https://simonbrown.je/>
