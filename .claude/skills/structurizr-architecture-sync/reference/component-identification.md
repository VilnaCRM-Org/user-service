# Component Identification Guide

How to determine what should be documented as a component in the Structurizr workspace.

## TL;DR - Quick Decision Guide

**✅ DO Document**:

- **Application Layer**: Controllers, Command Handlers, Query Handlers, API Processors, GraphQL Resolvers, Event Subscribers
- **Domain Layer**: Entities, Aggregates, Domain Events, Repository Interfaces (ports), Domain Services
- **Infrastructure Layer**: Repositories, Event Buses, Command Buses, External Adapters
- **External Systems**: Database, Cache, Message Broker, External APIs

**❌ DON'T Document**:

- DTOs and Input/Output objects
- Utilities, helpers, formatters
- Test classes and fixtures
- Framework configuration
- Simple interfaces without business logic
- Value objects (unless architecturally significant)
- Factories and transformers (unless critical)

**When in Doubt**: Ask yourself "Would removing this from the diagram significantly change my understanding of the architecture?"

- **Yes** → Document it
- **No** → Skip it

**Target**: 15-25 components per diagram for optimal clarity.

---

## Core Question

**"Is this class architecturally significant?"**

If yes → Document it as a component

If no → Omit it

## Architecturally Significant Components

### Definition

A component is architecturally significant if:

1. **It represents a key architectural decision** (e.g., CQRS pattern, hexagonal architecture)
2. **It has business logic** (not just data passing)
3. **It participates in important use cases**
4. **It crosses architectural boundaries** (e.g., repository bridging domain and infrastructure)
5. **It has dependencies or is depended upon** by other significant components
6. **A developer would need to know about it** to understand the system architecture

### Rule of Thumb

**Include if**: Removing it would change the architecture diagram significantly

**Exclude if**: Removing it would just reduce clutter

## Component Categories

### ✅ INCLUDE: Application Layer

| Component Type       | Reasoning                           | Example                 |
| -------------------- | ----------------------------------- | ----------------------- |
| **Controllers**      | Entry points for requests           | `HealthCheckController` |
| **Command Handlers** | CQRS pattern, orchestrate use cases | `RegisterUserHandler`   |
| **Query Handlers**   | CQRS read side                      | `GetUserQueryHandler`   |
| **API Processors**   | API Platform state processors       | `UserStateProcessor`    |
| **API Resolvers**    | GraphQL resolvers                   | `UserResolver`          |

**Why**: These represent the application's entry points and use case orchestration.

### ✅ INCLUDE: Domain Layer

| Component Type            | Reasoning                                   | Example                     |
| ------------------------- | ------------------------------------------- | --------------------------- |
| **Entities**              | Core business objects                       | `User`, `ConfirmationToken` |
| **Aggregates**            | Consistency boundaries                      | `User` (aggregate)          |
| **Value Objects**         | Important domain concepts with validation   | `Email`, `UserId`           |
| **Domain Events**         | State change notifications                  | `UserRegisteredEvent`       |
| **Factory Interfaces**    | Contracts for object creation               | `UuidFactoryInterface`      |
| **Repository Interfaces** | Contracts for persistence (hexagonal ports) | `UserRepositoryInterface`   |
| **Domain Services**       | Stateless business logic                    | `PasswordHashingService`    |

**Why**: These represent core business logic and domain model.

### ✅ INCLUDE: Infrastructure Layer

| Component Type        | Reasoning                                           | Example                           |
| --------------------- | --------------------------------------------------- | --------------------------------- |
| **Repositories**      | Data access implementations                         | `UserRepository`                  |
| **Event Subscribers** | React to domain events                              | `SendConfirmationEmailSubscriber` |
| **Event Buses**       | Event publishing mechanism                          | `InMemorySymfonyEventBus`         |
| **Command Buses**     | Command dispatching mechanism                       | `SymfonyCommandBus`               |
| **Factories**         | Factory implementations                             | `UuidFactory`                     |
| **Transformers**      | Data transformation (API Platform)                  | `UuidTransformer`                 |
| **External Adapters** | Integrations with external services                 | `StripePaymentAdapter`            |
| **Doctrine Types**    | Custom field types (if architecturally significant) | `UuidType`                        |

**Why**: These implement infrastructure concerns and adapt external services.

### ✅ INCLUDE: External Dependencies

| Component Type      | Reasoning                | Example             |
| ------------------- | ------------------------ | ------------------- |
| **Databases**       | Persistence layer        | MariaDB, PostgreSQL |
| **Caches**          | Caching layer            | Redis               |
| **Message Brokers** | Asynchronous messaging   | AWS SQS, RabbitMQ   |
| **External APIs**   | Third-party integrations | Stripe API          |
| **Search Engines**  | Search capabilities      | Elasticsearch       |

**Why**: These are critical infrastructure dependencies.

## ❌ EXCLUDE: Not Components

### Data Transfer Objects (DTOs)

**Reasoning**: DTOs are data structures without behavior, not architectural components.

**Examples**:

- `RegisterUserDTO`
- `UserResponseDTO`
- Input/Output objects for API Platform

**Exception**: None. DTOs should never be components.

### Simple Interfaces

**Reasoning**: Interfaces without business significance add clutter.

**Examples**:

- Empty marker interfaces
- Simple getter/setter interfaces
- Framework-required interfaces with no domain logic

**Exception**: Repository interfaces and factory interfaces in Domain layer (these are hexagonal ports).

### Utility Classes

**Reasoning**: Generic utilities are implementation details.

**Examples**:

- `StringHelper`
- `DateFormatter`
- `ArrayUtils`

**Exception**: If a utility represents a significant cross-cutting concern (e.g., custom encryption service).

### Framework Classes

**Reasoning**: Framework internals are not part of application architecture.

**Examples**:

- Symfony kernel
- Bundle configuration
- Doctrine migrations
- Framework event listeners (unless domain-specific)

**Exception**: Custom framework extensions that represent architectural decisions.

### Test Classes

**Reasoning**: Tests are not part of production architecture.

**Examples**:

- Unit tests
- Integration tests
- Test fixtures
- Test doubles (mocks, stubs)

**Exception**: None. Tests should never appear in production architecture diagrams.

### Configuration Classes

**Reasoning**: Configuration is infrastructure concern, not architecture.

**Examples**:

- Service configuration
- Parameter classes
- Environment variable readers

**Exception**: Configuration that represents significant architectural decisions (e.g., multi-tenancy configuration).

### Private Helper Methods

**Reasoning**: Internal implementation details, not components.

**Examples**:

- Private methods within a class
- Internal state management
- Helper functions

**Exception**: None.

## Decision Matrix

Use this matrix to decide if a class should be a component:

| Question                                     | Weight | Yes = Points |
| -------------------------------------------- | ------ | ------------ |
| Does it participate in a use case?           | High   | +3           |
| Does it have business logic?                 | High   | +3           |
| Does it cross architectural boundaries?      | High   | +3           |
| Is it an entry point (controller, handler)?  | High   | +3           |
| Is it a domain entity or aggregate?          | High   | +3           |
| Does it implement a hexagonal port?          | Medium | +2           |
| Is it an infrastructure adapter?             | Medium | +2           |
| Does it handle domain events?                | Medium | +2           |
| Is it a repository or factory?               | Medium | +2           |
| Would a new developer need to know about it? | Medium | +2           |
| Is it reused across multiple components?     | Low    | +1           |
| Does it have significant dependencies?       | Low    | +1           |
| Is it just data transfer?                    | High   | -3           |
| Is it a test class?                          | High   | -3           |
| Is it framework boilerplate?                 | Medium | -2           |
| Is it a private helper?                      | Medium | -2           |

**Scoring**:

- **≥ 5 points**: Definitely document as component
- **2-4 points**: Probably document (use judgment)
- **≤ 1 point**: Probably omit
- **Negative points**: Definitely omit

## Examples

### Example 1: RegisterUserCommandHandler

**Analysis**:

- Entry point? ✅ (+3)
- Business logic? ✅ (+3)
- Crosses boundaries? ✅ (+3)
- Participates in use case? ✅ (+3)
- New dev needs to know? ✅ (+2)

**Score**: +14

**Decision**: ✅ **Include** - Critical application layer component

---

### Example 2: User Entity

**Analysis**:

- Domain entity? ✅ (+3)
- Business logic? ✅ (+3)
- Participates in use case? ✅ (+3)
- Reused across components? ✅ (+1)
- New dev needs to know? ✅ (+2)

**Score**: +12

**Decision**: ✅ **Include** - Core domain model

---

### Example 3: RegisterUserDTO

**Analysis**:

- Data transfer? ❌ (-3)
- No business logic
- Just data structure

**Score**: -3

**Decision**: ❌ **Exclude** - Pure data structure

---

### Example 4: UserRepository

**Analysis**:

- Infrastructure adapter? ✅ (+2)
- Crosses boundaries? ✅ (+3)
- Implements port? ✅ (+2)
- Participates in use case? ✅ (+3)
- New dev needs to know? ✅ (+2)

**Score**: +12

**Decision**: ✅ **Include** - Critical infrastructure component

---

### Example 5: StringHelper

**Analysis**:

- Just utility? ❌ (-2)
- No business logic
- Generic helper

**Score**: -2

**Decision**: ❌ **Exclude** - Generic utility

---

### Example 6: SendConfirmationEmailSubscriber

**Analysis**:

- Event handler? ✅ (+2)
- Participates in use case? ✅ (+3)
- Infrastructure adapter? ✅ (+2)
- New dev needs to know? ✅ (+2)

**Score**: +9

**Decision**: ✅ **Include** - Important event-driven behavior

---

### Example 7: UserTest (PHPUnit test)

**Analysis**:

- Test class? ❌ (-3)
- Not production code

**Score**: -3

**Decision**: ❌ **Exclude** - Test infrastructure

## Granularity Guidelines

### Too Granular (❌ Avoid)

**Problem**: Diagram becomes cluttered with trivial details

**Example**:

```dsl
# Too detailed - private methods as components
validateEmail = component "validateEmail" ...
formatPhoneNumber = component "formatPhoneNumber" ...
checkDuplicates = component "checkDuplicates" ...
```

**Better**: Document the containing component

```dsl
userValidator = component "UserValidator" "Validates user data" ...
```

### Too Coarse (❌ Avoid)

**Problem**: Diagram hides important architectural decisions

**Example**:

```dsl
# Too coarse - entire layer as one component
applicationLayer = component "Application Layer" ...
domainLayer = component "Domain Layer" ...
```

**Better**: Document key components within layers

```dsl
group "Application" {
    registerUserHandler = component "RegisterUserHandler" ...
    updateUserHandler = component "UpdateUserHandler" ...
}

group "Domain" {
    user = component "User" ...
    confirmationToken = component "ConfirmationToken" ...
}
```

### Just Right (✅ Target)

**Goal**: Balance between detail and clarity

**Example**:

```dsl
group "Application" {
    # Key entry points
    userController = component "UserController" ...
    userCommandHandler = component "UserCommandHandler" ...
}

group "Domain" {
    # Core domain model
    user = component "User" "User aggregate" ...
    userEmail = component "UserEmail" "Email value object with validation" ...
}

group "Infrastructure" {
    # Infrastructure adapters
    userRepository = component "UserRepository" ...
    emailSubscriber = component "SendConfirmationEmailSubscriber" ...
}
```

## When in Doubt

**Ask yourself**:

1. **Would removing this from the diagram make the architecture less clear?**

   - Yes → Include it
   - No → Omit it

2. **Would a new team member ask about this component during onboarding?**

   - Yes → Include it
   - No → Omit it

3. **Does this represent an architectural pattern (CQRS, DDD, Hexagonal)?**

   - Yes → Include it
   - No → Omit it

4. **Is this just moving data around without logic?**
   - Yes → Omit it
   - No → Include it

## Common Mistakes

### Mistake 1: Including All Value Objects

**Problem**: Not all value objects are architecturally significant

**Guideline**: Include value objects that:

- Have complex validation logic
- Are shared across multiple aggregates
- Represent important domain concepts

**Omit**: Simple wrapper value objects (e.g., `FirstName`, `LastName`)

### Mistake 2: Omitting Event Subscribers

**Problem**: Event-driven architecture not visible

**Guideline**: Always include event subscribers - they represent reactive behavior

### Mistake 3: Including Framework Extensions

**Problem**: Diagram cluttered with framework details

**Guideline**: Omit framework extensions unless they represent architectural decisions

### Mistake 4: Over-Documenting Infrastructure

**Problem**: Too many infrastructure details

**Guideline**: Document infrastructure adapters, not internal infrastructure mechanisms

## Review Checklist

After identifying components, verify:

- [ ] All entry points documented (controllers, handlers)
- [ ] Core domain entities documented
- [ ] Key value objects with logic documented
- [ ] Repository implementations documented
- [ ] Event subscribers documented
- [ ] External dependencies documented
- [ ] No DTOs included
- [ ] No test classes included
- [ ] No utilities included (unless significant)
- [ ] No framework boilerplate included
- [ ] Diagram is clear at a glance (not cluttered)
- [ ] All significant architectural patterns visible

## Further Guidance

For borderline cases, consult:

- **Team members**: Get consensus on significance
- **Architecture decision records**: Check if component is documented
- **Code reviews**: See if component is frequently discussed
- **Onboarding docs**: Check if component is mentioned for new developers
