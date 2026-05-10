# Relationship Patterns

Common relationship patterns and how to document them in Structurizr DSL.

## Quick Reference Table

| Relationship         | Pattern                        | Example DSL                                 | When to Use                                       |
| -------------------- | ------------------------------ | ------------------------------------------- | ------------------------------------------------- |
| **Uses**             | A uses B                       | `handler -> repository "uses"`              | Dependency injection, method calls                |
| **Creates**          | A creates B                    | `handler -> entity "creates"`               | Factory creates objects, handlers create entities |
| **Triggers**         | Event triggers component       | `event -> subscriber "triggers"`            | Events triggering subscribers                     |
| **Implements**       | Component implements interface | `repository -> interface "implements"`      | Repository implementing port                      |
| **Depends On**       | Component depends on interface | `handler -> interface "depends on"`         | Hexagonal architecture ports                      |
| **Publishes**        | Component publishes event      | `handler -> event "publishes"`              | Event-driven communication                        |
| **Persists To**      | Repository to database         | `repository -> database "persists to"`      | Data persistence                                  |
| **Stores/Retrieves** | Repository manages entity      | `repository -> entity "stores / retrieves"` | Entity lifecycle management                       |
| **Sends Via**        | Component sends via broker     | `subscriber -> broker "sends via"`          | Asynchronous messaging                            |
| **Validates**        | Validator validates component  | `validator -> entity "validates"`           | Business rule checking                            |

**Common Composite Patterns**:

- **CQRS**: Controller → Command Bus → Handler → Repository → Database
- **Event-Driven**: Handler → Event → Subscribers → External Systems
- **Hexagonal**: Handler → Port (interface) ← Adapter (implementation) → External System
- **Factory**: Client → Factory Interface ← Factory Implementation → Product

---

## Core Relationship Types

### 1. Uses

**Pattern**: Component A uses component B to perform functionality

**DSL**:

```dsl
componentA -> componentB "uses"
```

**When to use**:

- Dependency injection
- Method calls
- Service consumption

**Examples**:

```dsl
# Command handler uses repository
registerUserHandler -> userRepository "uses"

# Controller uses command bus
userController -> commandBus "uses"

# Subscriber uses external API
emailSubscriber -> emailService "uses"
```

**More specific**:

```dsl
registerUserHandler -> userRepository "uses for persistence"
userController -> commandBus "uses to dispatch commands"
emailSubscriber -> emailService "uses to send notifications"
```

### 2. Creates

**Pattern**: Component A creates (instantiates) component B

**DSL**:

```dsl
componentA -> componentB "creates"
```

**When to use**:

- Factories creating objects
- Handlers creating domain events
- Commands creating entities

**Examples**:

```dsl
# Factory creates value object
uuidFactory -> uuid "creates"

# Handler creates entity
registerUserHandler -> user "creates"

# Aggregate creates event
user -> userRegisteredEvent "creates"
```

### 3. Triggers

**Pattern**: Event A triggers component B (subscriber)

**DSL**:

```dsl
eventA -> componentB "triggers"
```

**When to use**:

- Domain events triggering subscribers
- Commands triggering handlers
- Messages triggering consumers

**Examples**:

```dsl
# Event triggers subscriber
userRegisteredEvent -> emailSubscriber "triggers"

# Event triggers multiple subscribers
userRegisteredEvent -> emailSubscriber "triggers"
userRegisteredEvent -> analyticsSubscriber "triggers"
userRegisteredEvent -> notificationSubscriber "triggers"
```

### 4. Implements

**Pattern**: Component implements an interface

**DSL**:

```dsl
implementation -> interface "implements"
```

**When to use**:

- Repository implementing repository interface
- Factory implementing factory interface
- Adapter implementing port

**Examples**:

```dsl
# Repository implements interface
userRepository -> userRepositoryInterface "implements"

# Factory implements interface
uuidFactory -> uuidFactoryInterface "implements"

# Adapter implements port
stripePaymentAdapter -> paymentGatewayInterface "implements"
```

### 5. Depends On

**Pattern**: Component A depends on interface B (hexagonal ports)

**DSL**:

```dsl
componentA -> interfaceB "depends on"
```

**When to use**:

- Application layer depending on domain interfaces
- Handlers depending on repository interfaces
- Following dependency inversion principle

**Examples**:

```dsl
# Handler depends on repository interface (not implementation)
registerUserHandler -> userRepositoryInterface "depends on"

# Transformer depends on factory interface
uuidTransformer -> uuidFactoryInterface "depends on"
```

### 6. Publishes

**Pattern**: Component publishes event to bus

**DSL**:

```dsl
component -> event "publishes"
```

**When to use**:

- Aggregates publishing domain events
- Handlers dispatching events
- Event-driven communication

**Examples**:

```dsl
# Handler publishes event
registerUserHandler -> userRegisteredEvent "publishes"

# Aggregate publishes multiple events
user -> userRegisteredEvent "publishes"
user -> emailChangedEvent "publishes"
user -> passwordChangedEvent "publishes"
```

### 7. Persists To / Retrieves From

**Pattern**: Repository interacts with database

**DSL**:

```dsl
repository -> database "persists to"
repository -> database "retrieves from"
# Or combined
repository -> database "persists to / retrieves from"
```

**When to use**:

- Repository-database relationships
- Data access patterns

**Examples**:

```dsl
# Repository persists to database
userRepository -> database "persists to"

# Repository retrieves from database
userRepository -> database "retrieves from"

# Combined (common)
userRepository -> database "persists to / retrieves from"
```

### 8. Stores / Retrieves

**Pattern**: Repository manages entity lifecycle

**DSL**:

```dsl
repository -> entity "stores"
repository -> entity "retrieves"
# Or combined
repository -> entity "stores / retrieves"
```

**When to use**:

- Repository-entity relationships
- Persistence abstraction

**Examples**:

```dsl
# Repository stores entity
userRepository -> user "stores"

# Repository retrieves entity
userRepository -> user "retrieves"

# Combined (common)
userRepository -> user "stores / retrieves"
```

### 9. Sends Via

**Pattern**: Component sends messages via broker

**DSL**:

```dsl
component -> broker "sends via"
```

**When to use**:

- Asynchronous messaging
- Message queue interactions
- Event publishing to external systems

**Examples**:

```dsl
# Subscriber sends message via broker
emailSubscriber -> messageBroker "sends via"

# Service publishes event via broker
notificationService -> messageBroker "sends via"
```

### 10. Validates

**Pattern**: Component validates another component

**DSL**:

```dsl
validator -> component "validates"
```

**When to use**:

- Validation logic
- Business rule checking

**Examples**:

```dsl
# Value object validates itself
userEmail -> emailFormat "validates"

# Validator validates entity
userValidator -> user "validates"
```

## Composite Patterns

### CQRS Command Flow

**Pattern**: Controller → Command Bus → Handler → Repository → Database

**DSL**:

```dsl
# Entry point
userController -> registerUserCommand "creates"
userController -> commandBus "dispatches via"

# Command handling
commandBus -> registerUserHandler "routes to"
registerUserHandler -> user "creates"
registerUserHandler -> userRepository "uses"

# Persistence
userRepository -> user "stores"
userRepository -> database "persists to"
```

### Event-Driven Flow

**Pattern**: Handler → Event → Multiple Subscribers → External Systems

**DSL**:

```dsl
# Event creation
registerUserHandler -> userRegisteredEvent "publishes"

# Event triggering subscribers
userRegisteredEvent -> emailSubscriber "triggers"
userRegisteredEvent -> analyticsSubscriber "triggers"
userRegisteredEvent -> auditSubscriber "triggers"

# Subscribers interacting with external systems
emailSubscriber -> messageBroker "sends via"
analyticsSubscriber -> analyticsService "sends to"
auditSubscriber -> auditLog "writes to"
```

### Hexagonal Architecture Pattern

**Pattern**: Handler → Domain Interface ← Infrastructure Implementation → External System

**DSL**:

```dsl
# Application depends on port
registerUserHandler -> userRepositoryInterface "depends on"

# Infrastructure implements port
userRepository -> userRepositoryInterface "implements"

# Infrastructure accesses external system
userRepository -> database "persists to"
```

### Factory Pattern

**Pattern**: Client → Factory Interface ← Factory Implementation → Product

**DSL**:

```dsl
# Client uses interface
uuidTransformer -> uuidFactoryInterface "uses"

# Implementation implements interface
uuidFactory -> uuidFactoryInterface "implements"

# Factory creates product
uuidFactory -> uuid "creates"
```

### Aggregate Pattern

**Pattern**: Handler → Aggregate → Value Objects, Events

**DSL**:

```dsl
# Handler creates aggregate
registerUserHandler -> user "creates"

# Aggregate has value objects
user -> userId "has"
user -> userEmail "has"
user -> userPassword "has"

# Aggregate creates events
user -> userRegisteredEvent "creates"
```

## Relationship Direction Best Practices

### Unidirectional (Preferred)

**Pattern**: Clear dependency direction

```dsl
# Good: Clear flow from application to infrastructure
handler -> repository "uses"
repository -> database "persists to"
```

**Why**: Shows clear dependency flow, easier to understand

### Bidirectional (Avoid Unless Necessary)

**Pattern**: Two-way relationship

```dsl
# Avoid: Confusing bidirectional relationship
componentA -> componentB "uses"
componentB -> componentA "notifies"
```

**Why**: Can indicate tight coupling, harder to reason about

**When acceptable**: Event-driven systems where A triggers B, B notifies A

```dsl
# Acceptable: Event notification pattern
handler -> event "publishes"
event -> subscriber "triggers"
```

## Relationship Description Guidelines

### Good Descriptions

**Characteristics**:

- Use verb phrases
- Be specific about intent
- Indicate direction of data/control flow

**Examples**:

```dsl
✅ handler -> repository "uses for user persistence"
✅ controller -> commandBus "dispatches commands to"
✅ subscriber -> messageBroker "sends confirmation email via"
✅ factory -> uuid "creates with random generation"
```

### Poor Descriptions

**Characteristics**:

- Too vague
- No context
- Doesn't indicate purpose

**Examples**:

```dsl
❌ handler -> repository "interacts"
❌ controller -> commandBus "talks to"
❌ subscriber -> messageBroker "does stuff"
❌ factory -> uuid "makes"
```

### Context-Specific Descriptions

Add context when relationships aren't obvious:

```dsl
# Generic (less helpful)
handler -> validator "uses"

# Context-specific (more helpful)
handler -> validator "uses to validate user email format"
```

## Cardinality Patterns

### One-to-One

**Pattern**: Single source, single destination

```dsl
uuidFactory -> uuid "creates"
```

### One-to-Many

**Pattern**: Single source, multiple destinations

```dsl
# Event triggers multiple subscribers
userRegisteredEvent -> emailSubscriber "triggers"
userRegisteredEvent -> analyticsSubscriber "triggers"
userRegisteredEvent -> auditSubscriber "triggers"
```

### Many-to-One

**Pattern**: Multiple sources, single destination

```dsl
# Multiple handlers use same repository
registerUserHandler -> userRepository "uses"
updateUserHandler -> userRepository "uses"
confirmUserHandler -> userRepository "uses"
```

## Layer-Crossing Relationships

### Application → Domain

**Allowed patterns**:

```dsl
# Handler depends on domain interface
handler -> repositoryInterface "depends on"

# Handler creates domain entity
handler -> user "creates"

# Handler publishes domain event
handler -> userRegisteredEvent "publishes"
```

### Domain → Infrastructure

**Forbidden**: Domain should not depend on infrastructure

```dsl
# ❌ VIOLATION: Domain depending on infrastructure
user -> userRepository "uses"

# ✅ CORRECT: Use interface in domain
user -> userRepositoryInterface "uses"
```

### Infrastructure → Domain

**Allowed patterns**:

```dsl
# Repository implements domain interface
userRepository -> userRepositoryInterface "implements"

# Repository stores domain entity
userRepository -> user "stores"

# Subscriber handles domain event
emailSubscriber -> userRegisteredEvent "listens to"
```

### Application → Infrastructure

**Allowed patterns**:

```dsl
# Handler uses infrastructure service
handler -> commandBus "dispatches via"

# Controller uses infrastructure transformer
controller -> uuidTransformer "uses"
```

## External System Relationships

### Database

```dsl
repository -> database "persists to"
repository -> database "retrieves from"
repository -> database "queries"
```

### Cache

```dsl
repository -> cache "caches in"
subscriber -> cache "invalidates"
service -> cache "reads from"
```

### Message Broker

```dsl
subscriber -> messageBroker "sends via"
consumer -> messageBroker "receives from"
publisher -> messageBroker "publishes to"
```

### External API

```dsl
adapter -> externalAPI "calls"
service -> externalAPI "integrates with"
subscriber -> externalAPI "sends notification to"
```

## Common Mistakes

### Mistake 1: Circular Dependencies

**Problem**:

```dsl
componentA -> componentB "uses"
componentB -> componentA "uses"
```

**Indication**: Architectural smell, tight coupling

**Solution**: Introduce interface or event-driven communication

### Mistake 2: Skipping Intermediate Components

**Problem**:

```dsl
# Bad: Skipping command bus
controller -> handler "calls directly"
```

**Should be**:

```dsl
controller -> commandBus "dispatches via"
commandBus -> handler "routes to"
```

### Mistake 3: Vague Relationships

**Problem**:

```dsl
componentA -> componentB "uses"
```

**Better**:

```dsl
componentA -> componentB "uses for user validation"
```

### Mistake 4: Missing Critical Relationships

**Problem**: Not documenting important dependencies

**Solution**: Document all architecturally significant relationships

## Verification Checklist

After adding relationships:

- [ ] All relationships have clear, descriptive labels
- [ ] Relationship direction matches actual dependency flow
- [ ] No circular dependencies (or justified if present)
- [ ] Layer boundaries respected (no domain → infrastructure)
- [ ] All critical dependencies documented
- [ ] External system relationships explicit
- [ ] Event flows complete (publisher → event → subscriber)
- [ ] Factory patterns complete (client → interface ← implementation → product)
- [ ] Repository patterns complete (handler → interface, repository → database)

## Further Reading

- **Dependency Inversion Principle**: Depend on interfaces, not implementations
- **Hexagonal Architecture**: Ports and adapters pattern
- **Domain Events**: Event-driven architecture patterns
- **CQRS**: Command and query separation
