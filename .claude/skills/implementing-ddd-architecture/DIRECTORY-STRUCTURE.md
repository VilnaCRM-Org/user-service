# DDD Directory Structure Reference

**Learn where to place files in a Domain-Driven Design architecture by following proven patterns from [CodelyTV's php-ddd-example](https://github.com/CodelyTV/php-ddd-example).**

> Template examples mention MongoDB/ODM repositories; in this service, implement repositories with Doctrine ORM (MySQL) while keeping the same directory layout.

## Quick Reference: File Placement Decision Tree

```
Is it business logic?
├─ YES → Domain layer
│   ├─ Has identity? → Entity (Domain/Entity/)
│   ├─ No identity, immutable? → Value Object (Domain/ValueObject/)
│   ├─ Cluster of entities? → Aggregate (Domain/Entity/, extends AggregateRoot)
│   ├─ Something happened? → Domain Event (Domain/Event/)
│   ├─ Data access contract? → Repository Interface (Domain/Repository/)
│   └─ Business error? → Domain Exception (Domain/Exception/)
│
├─ Is it orchestration/use case?
│   ├─ YES → Application layer
│   │   ├─ Write operation? → Command + Handler (Application/Command/, Application/CommandHandler/)
│   │   ├─ Read operation? → Query + Handler (Application/Query/, Application/QueryHandler/)
│   │   ├─ React to event? → Event Subscriber (Application/EventSubscriber/)
│   │   ├─ API transformation? → DTO/Processor (Application/DTO/, Application/Processor/)
│   │   └─ GraphQL input? → Mutation Input (Application/MutationInput/)
│   │
└─ Is it technical/external concern?
    └─ YES → Infrastructure layer
        ├─ Database access? → Repository Implementation (Infrastructure/Repository/)
        ├─ Message dispatching? → Bus Implementation (Infrastructure/Bus/)
        ├─ Doctrine type? → Custom Type (Infrastructure/DoctrineType/)
        └─ External service? → Service Implementation (Infrastructure/Service/)
```

## Complete Directory Structure (CodelyTV Pattern)

```
src/
├── Mooc/                           # Bounded Context (Application)
│   ├── Courses/                    # Module (Aggregate Root)
│   │   ├── Application/            # Use cases & orchestration
│   │   │   ├── Create/             # Use case: Create Course
│   │   │   │   ├── CreateCourseCommand.php
│   │   │   │   ├── CreateCourseCommandHandler.php
│   │   │   │   └── CourseCreator.php
│   │   │   ├── Find/               # Use case: Find Course
│   │   │   │   ├── FindCourseQuery.php
│   │   │   │   ├── FindCourseQueryHandler.php
│   │   │   │   └── CourseFinder.php
│   │   │   └── Update/             # Use case: Update Course
│   │   │       ├── CourseRenamer.php
│   │   │       └── RenameCourseCommandHandler.php
│   │   │
│   │   ├── Domain/                 # Pure business logic
│   │   │   ├── Course.php                    # Aggregate Root entity
│   │   │   ├── CourseId.php                  # Value Object (ID)
│   │   │   ├── CourseName.php                # Value Object
│   │   │   ├── CourseDuration.php            # Value Object
│   │   │   ├── CourseCreatedDomainEvent.php  # Domain Event
│   │   │   ├── CourseNotExist.php            # Domain Exception
│   │   │   └── CourseRepository.php          # Repository Interface
│   │   │
│   │   └── Infrastructure/         # Technical implementations
│   │       ├── Persistence/
│   │       │   ├── DoctrineCourseRepository.php
│   │       │   └── FileCourseRepository.php
│   │       └── Mapping/
│   │           └── Course.orm.xml
│   │
│   ├── Videos/                     # Another Module
│   │   ├── Application/
│   │   ├── Domain/
│   │   └── Infrastructure/
│   │
│   └── Shared/                     # Shared within Mooc context
│       ├── Domain/
│       │   └── Criteria/
│       └── Infrastructure/
│
├── Backoffice/                     # Another Bounded Context
│   ├── Courses/
│   │   ├── Application/
│   │   ├── Domain/
│   │   └── Infrastructure/
│   └── Shared/
│
└── Shared/                         # Shared Kernel (cross-context)
    ├── Domain/
    │   ├── Aggregate/
    │   │   └── AggregateRoot.php
    │   ├── Bus/
    │   │   ├── Command/
    │   │   │   ├── CommandInterface.php
    │   │   │   ├── CommandBusInterface.php
    │   │   │   └── CommandHandlerInterface.php
    │   │   ├── Event/
    │   │   │   ├── DomainEvent.php
    │   │   │   ├── EventBusInterface.php
    │   │   │   └── DomainEventSubscriberInterface.php
    │   │   └── Query/
    │   │       ├── QueryInterface.php
    │   │       ├── QueryBusInterface.php
    │   │       └── QueryHandlerInterface.php
    │   ├── Collection/
    │   │   └── Collection.php
    │   ├── ValueObject/
    │   │   ├── Ulid.php
    │   │   ├── StringValueObject.php
    │   │   └── IntValueObject.php
    │   ├── Exception/
    │   │   └── DomainException.php
    │   └── Criteria/
    │       ├── Criteria.php
    │       ├── Filter.php
    │       └── Order.php
    │
    ├── Application/
    │   ├── Transformer/
    │   ├── Validator/
    │   └── OpenApi/
    │       ├── Factory/
    │       └── Processor/
    │
    └── Infrastructure/
        ├── Bus/
        │   ├── Command/
        │   │   └── InMemoryCommandBus.php
        │   └── Event/
        │       └── SymfonyEventBus.php
        ├── DoctrineType/
        │   ├── UlidType.php
        │   └── DomainUuidType.php
        └── Persistence/
            └── Doctrine/
```

## Our Project Structure (Adapted)

```
src/
├── Customer/                       # Bounded Context
│   ├── Application/                # Use cases
│   │   ├── Command/                # Write operations
│   │   │   ├── CreateCustomerCommand.php
│   │   │   └── UpdateCustomerCommand.php
│   │   ├── CommandHandler/         # Handle commands
│   │   │   ├── CreateCustomerHandler.php
│   │   │   └── UpdateCustomerHandler.php
│   │   ├── DTO/                    # Data Transfer Objects
│   │   │   └── CustomerInput.php
│   │   ├── EventSubscriber/        # React to domain events
│   │   │   └── SendWelcomeEmailOnCustomerCreated.php
│   │   ├── Processor/              # API Platform processors
│   │   │   └── CreateCustomerProcessor.php
│   │   ├── Resolver/               # GraphQL resolvers
│   │   │   └── CustomerResolver.php
│   │   ├── MutationInput/          # GraphQL inputs
│   │   │   └── CreateCustomerInput.php
│   │   ├── Transformer/            # Data transformations
│   │   │   └── CustomerToArrayTransformer.php
│   │   └── Factory/                # Object factories
│   │       └── CustomerFactory.php
│   │
│   ├── Domain/                     # Pure business logic
│   │   ├── Entity/                 # Domain entities
│   │   │   └── Customer.php
│   │   ├── ValueObject/            # Value objects
│   │   │   ├── Email.php
│   │   │   ├── CustomerName.php
│   │   │   └── LoyaltyPoints.php
│   │   ├── Event/                  # Domain events
│   │   │   ├── CustomerCreated.php
│   │   │   └── CustomerEmailChanged.php
│   │   ├── Repository/             # Repository interfaces
│   │   │   └── CustomerRepositoryInterface.php
│   │   ├── Exception/              # Domain exceptions
│   │   │   ├── InvalidEmailException.php
│   │   │   └── CustomerNotFoundException.php
│   │   ├── Collection/             # Domain collections
│   │   │   └── CustomerCollection.php
│   │   └── Factory/                # Domain factories (interfaces)
│   │       └── CustomerFactoryInterface.php
│   │
│   └── Infrastructure/             # Technical implementations
│       ├── Repository/             # Repository implementations
│       │   └── MongoDBCustomerRepository.php
│       └── Service/                # External service implementations
│           └── StripePaymentService.php
│
├── Internal/                       # Internal services
│   └── HealthCheck/
│       ├── Application/
│       ├── Domain/
│       └── Infrastructure/
│
└── Shared/                         # Shared kernel
    ├── Application/
    │   ├── Transformer/
    │   ├── Validator/
    │   ├── ErrorProvider/
    │   └── OpenApi/
    │       ├── Factory/
    │       ├── Builder/
    │       └── Processor/
    │
    ├── Domain/
    │   ├── Aggregate/
    │   │   └── AggregateRoot.php
    │   ├── Bus/
    │   │   ├── Command/
    │   │   │   ├── CommandInterface.php
    │   │   │   ├── CommandBusInterface.php
    │   │   │   └── CommandHandlerInterface.php
    │   │   └── Event/
    │   │       ├── DomainEvent.php
    │   │       ├── EventBusInterface.php
    │   │       └── DomainEventSubscriberInterface.php
    │   ├── ValueObject/
    │   │   └── Ulid.php
    │   └── Exception/
    │       └── DomainException.php
    │
    └── Infrastructure/
        ├── Bus/
        │   ├── Command/
        │   │   └── SymfonyCommandBus.php
        │   └── Event/
        │       └── SymfonyEventBus.php
        ├── DoctrineType/
        │   ├── UlidType.php
        │   └── DomainUuidType.php
        └── Transformer/
```

## File Naming Conventions

### Domain Layer

| Type                 | Naming Pattern                    | Example                           |
| -------------------- | --------------------------------- | --------------------------------- |
| Entity               | `{EntityName}.php`                | `Customer.php`                    |
| Value Object         | `{ConceptName}.php`               | `Email.php`, `Money.php`          |
| Domain Event         | `{Entity}{PastTenseAction}.php`   | `CustomerCreated.php`             |
| Repository Interface | `{Entity}RepositoryInterface.php` | `CustomerRepositoryInterface.php` |
| Domain Exception     | `{SpecificError}Exception.php`    | `InvalidEmailException.php`       |
| Collection           | `{Entity}Collection.php`          | `CustomerCollection.php`          |

### Application Layer

| Type             | Naming Pattern                  | Example                                   |
| ---------------- | ------------------------------- | ----------------------------------------- |
| Command          | `{Action}{Entity}Command.php`   | `CreateCustomerCommand.php`               |
| Command Handler  | `{Action}{Entity}Handler.php`   | `CreateCustomerHandler.php`               |
| Event Subscriber | `{Action}On{Event}.php`         | `SendEmailOnCustomerCreated.php`          |
| DTO              | `{Entity}{Type}.php`            | `CustomerInput.php`, `CustomerOutput.php` |
| Processor        | `{Action}{Entity}Processor.php` | `CreateCustomerProcessor.php`             |
| Transformer      | `{From}To{To}Transformer.php`   | `CustomerToArrayTransformer.php`          |

### Infrastructure Layer

| Type               | Naming Pattern                       | Example                         |
| ------------------ | ------------------------------------ | ------------------------------- |
| Repository         | `{Technology}{Entity}Repository.php` | `MongoDBCustomerRepository.php` |
| Doctrine Type      | `{ConceptName}Type.php`              | `UlidType.php`                  |
| External Service   | `{Provider}{Service}.php`            | `StripePaymentService.php`      |
| Bus Implementation | `{Framework}{Type}Bus.php`           | `SymfonyCommandBus.php`         |

## Creating New Files: Step-by-Step

### Creating a New Bounded Context

```bash
# 1. Create directory structure
mkdir -p src/Order/{Application/{Command,CommandHandler,EventSubscriber,DTO},Domain/{Entity,ValueObject,Event,Repository,Exception},Infrastructure/Repository}

# 2. Result:
src/Order/
├── Application/
│   ├── Command/
│   ├── CommandHandler/
│   ├── EventSubscriber/
│   └── DTO/
├── Domain/
│   ├── Entity/
│   ├── ValueObject/
│   ├── Event/
│   ├── Repository/
│   └── Exception/
└── Infrastructure/
    └── Repository/
```

### Adding a New Entity

1. **Entity** (Domain): `src/Order/Domain/Entity/Order.php`
2. **Value Objects** (Domain): `src/Order/Domain/ValueObject/OrderId.php`, etc.
3. **Repository Interface** (Domain): `src/Order/Domain/Repository/OrderRepositoryInterface.php`
4. **Domain Events** (Domain): `src/Order/Domain/Event/OrderPlaced.php`
5. **Exceptions** (Domain): `src/Order/Domain/Exception/InvalidOrderException.php`
6. **Doctrine Mapping** (Config): `config/doctrine/Order.mongodb.xml`
7. **Repository Implementation** (Infrastructure): `src/Order/Infrastructure/Repository/MongoDBOrderRepository.php`
8. **Command** (Application): `src/Order/Application/Command/PlaceOrderCommand.php`
9. **Handler** (Application): `src/Order/Application/CommandHandler/PlaceOrderHandler.php`

### Adding a New Feature to Existing Context

Adding "change email" feature to Customer:

```
src/Customer/
├── Application/
│   ├── Command/
│   │   └── ChangeCustomerEmailCommand.php     # NEW
│   └── CommandHandler/
│       └── ChangeCustomerEmailHandler.php     # NEW
└── Domain/
    ├── Entity/
    │   └── Customer.php                        # ADD method: changeEmail()
    └── Event/
        └── CustomerEmailChanged.php            # NEW
```

## Anti-Pattern: Wrong File Placement

### WRONG: Business logic in Infrastructure

```
src/Customer/Infrastructure/Service/CustomerValidator.php
// Business validation should be in Domain (Value Objects)
```

**Fix**: Move to `src/Customer/Domain/ValueObject/Email.php`

### WRONG: Framework code in Domain

```
src/Customer/Domain/Entity/Customer.php
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
```

**Fix**: Use XML mappings in `config/doctrine/Customer.mongodb.xml`

### WRONG: Use case logic in Entity

```
src/Customer/Domain/Entity/Customer.php
public function sendWelcomeEmail() // Application concern!
```

**Fix**: Move to `src/Customer/Application/EventSubscriber/SendWelcomeEmailOnCustomerCreated.php`

## Quick Checks

Before committing new files:

```bash
# Verify architecture
make deptrac

# Check no framework imports in Domain
grep -r "use Symfony\|use Doctrine\|use ApiPlatform" src/*/Domain/

# Ensure handlers are registered
grep -r "implements CommandHandlerInterface" src/*/Application/CommandHandler/
```

## Related Skills

- **[deptrac-fixer](../deptrac-fixer/SKILL.md)** - Fix violations when files are in wrong layers
- **[quality-standards](../quality-standards/SKILL.md)** - Maintain code quality standards

---

**Remember**: Structure reflects intent. Proper file placement makes the architecture self-documenting.
