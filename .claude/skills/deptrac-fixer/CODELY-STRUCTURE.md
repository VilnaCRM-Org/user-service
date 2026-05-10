# CodelyTV Directory Structure Reference

**Where to move files when fixing Deptrac violations.**

This reference shows the correct directory structure based on [CodelyTV's php-ddd-example](https://github.com/CodelyTV/php-ddd-example) - the industry standard for PHP DDD architecture.

## Complete CodelyTV Project Structure

```bash
# ls -la equivalent of CodelyTV php-ddd-example

src/
├── Analytics/                          # Bounded Context
├── Backoffice/                         # Bounded Context
├── Mooc/                               # Bounded Context (Main Application)
│   ├── Courses/                        # Module (Aggregate)
│   │   ├── Application/                # Use Cases Layer
│   │   │   ├── Create/                 # Use Case: Create Course
│   │   │   │   ├── CourseCreator.php
│   │   │   │   ├── CreateCourseCommand.php
│   │   │   │   └── CreateCourseCommandHandler.php
│   │   │   ├── Find/                   # Use Case: Find Course
│   │   │   │   ├── CourseFinder.php
│   │   │   │   ├── FindCourseQuery.php
│   │   │   │   └── FindCourseQueryHandler.php
│   │   │   └── Update/                 # Use Case: Update Course
│   │   │       ├── CourseRenamer.php
│   │   │       └── RenameCourseCommandHandler.php
│   │   │
│   │   ├── Domain/                     # Pure Business Logic Layer
│   │   │   ├── Course.php              # Aggregate Root entity
│   │   │   ├── CourseCreatedDomainEvent.php  # Domain Event
│   │   │   ├── CourseDuration.php      # Value Object
│   │   │   ├── CourseName.php          # Value Object
│   │   │   ├── CourseNotExist.php      # Domain Exception
│   │   │   └── CourseRepository.php    # Repository Interface
│   │   │
│   │   └── Infrastructure/             # Technical Implementation Layer
│   │       ├── Persistence/
│   │       │   ├── Doctrine/           # Doctrine-specific config
│   │       │   │   └── Course.orm.xml  # XML mapping (NOT in Domain!)
│   │       │   ├── DoctrineCourseRepository.php  # Repository impl
│   │       │   └── FileCourseRepository.php      # Alternative impl
│   │       └── Cdc/                    # Change Data Capture
│   │
│   ├── CoursesCounter/                 # Another Module
│   ├── Videos/                         # Another Module
│   ├── Steps/                          # Another Module
│   ├── Notifications/                  # Another Module
│   └── Shared/                         # Shared within Mooc context
│
├── Retention/                          # Bounded Context
└── Shared/                             # Shared Kernel (cross-context)
    ├── Domain/
    │   ├── Aggregate/
    │   │   └── AggregateRoot.php
    │   ├── Bus/
    │   │   ├── Command/
    │   │   │   ├── Command.php
    │   │   │   ├── CommandBus.php
    │   │   │   └── CommandHandler.php
    │   │   ├── Event/
    │   │   │   ├── DomainEvent.php
    │   │   │   ├── EventBus.php
    │   │   │   └── DomainEventSubscriber.php
    │   │   └── Query/
    │   │       ├── Query.php
    │   │       ├── QueryBus.php
    │   │       └── QueryHandler.php
    │   ├── Criteria/
    │   │   ├── Criteria.php
    │   │   ├── Filter.php
    │   │   └── Order.php
    │   ├── ValueObject/
    │   │   ├── IntValueObject.php
    │   │   ├── StringValueObject.php
    │   │   ├── SimpleUuid.php
    │   │   └── Uuid.php
    │   ├── Collection.php
    │   ├── Assert.php
    │   ├── DomainError.php
    │   ├── Logger.php                  # Interface only
    │   └── UuidGenerator.php           # Interface only
    │
    └── Infrastructure/
        ├── Bus/
        │   ├── Command/
        │   │   └── InMemorySymfonyCommandBus.php
        │   ├── Event/
        │   │   └── InMemorySymfonyEventBus.php
        │   └── Query/
        │       └── InMemorySymfonyQueryBus.php
        ├── Doctrine/
        │   └── DatabaseConnections.php
        └── Persistence/
            └── Doctrine/
```

## Our Project Structure (Adapted from CodelyTV)

```bash
src/
├── Customer/                           # Bounded Context
│   ├── Application/
│   │   ├── Command/                    # Commands (write operations)
│   │   │   ├── CreateCustomerCommand.php
│   │   │   └── UpdateCustomerCommand.php
│   │   ├── CommandHandler/             # Handle commands
│   │   │   ├── CreateCustomerHandler.php
│   │   │   └── UpdateCustomerHandler.php
│   │   ├── DTO/                        # Data Transfer Objects
│   │   │   └── CustomerInput.php
│   │   ├── EventSubscriber/            # React to domain events
│   │   │   └── SendWelcomeEmailOnCustomerCreated.php
│   │   ├── Processor/                  # API Platform processors
│   │   │   └── CreateCustomerProcessor.php
│   │   ├── Resolver/                   # GraphQL resolvers
│   │   └── Transformer/                # Data transformers
│   │
│   ├── Domain/
│   │   ├── Entity/                     # Domain entities
│   │   │   └── Customer.php            # NO Doctrine annotations!
│   │   ├── ValueObject/                # Value objects
│   │   │   ├── Email.php               # Self-validating
│   │   │   └── CustomerName.php
│   │   ├── Event/                      # Domain events
│   │   │   └── CustomerCreated.php
│   │   ├── Repository/                 # Repository interfaces
│   │   │   └── CustomerRepositoryInterface.php
│   │   ├── Exception/                  # Domain exceptions
│   │   │   └── InvalidEmailException.php
│   │   └── Collection/                 # Domain collections
│   │
│   └── Infrastructure/
│       └── Repository/                 # Repository implementations
│           └── MongoDBCustomerRepository.php
│
├── Internal/                           # Internal services
│   └── HealthCheck/
│
└── Shared/                             # Shared kernel
    ├── Application/
    │   ├── OpenApi/                    # OpenAPI configuration
    │   └── Transformer/
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
        │   └── Event/
        └── DoctrineType/
            ├── UlidType.php
            └── DomainUuidType.php

config/
└── doctrine/                           # Doctrine XML mappings
    ├── Customer.mongodb.xml            # NOT in Domain layer!
    └── Product.mongodb.xml
```

## Violation Fix Map: Where Files Should Go

When you see a Deptrac violation, use this map to know where to move or refactor the code:

### Domain → Symfony Validation

**Violation**: `uses Symfony\Component\Validator\Constraints`

| FROM (Wrong)                                                     | TO (Correct)                                                             |
| ---------------------------------------------------------------- | ------------------------------------------------------------------------ |
| `src/Customer/Domain/Entity/Customer.php` with `#[Assert\Email]` | Pure Domain entity + YAML validation in `config/validator/Customer.yaml` |

**Move validation from Domain to Application**:

```
Customer.php (Domain)           →   Customer.php (Domain) - Pure entity
├─ #[Assert\Email] email        →   ├─ private string $email (no validation)
├─ #[Assert\NotBlank] name      →   └─ private string $name (no validation)
└─ #[Assert\Length] name        →
                                    Application/DTO/CustomerCreate.php
                                    └─ public properties

                                    config/validator/Customer.yaml
                                    ├─ email: Email, NotBlank
                                    ├─ name: NotBlank, Length
                                    └─ UniqueEmail custom validator
```

---

### Domain → Doctrine Annotations

**Violation**: `uses Doctrine\ODM\MongoDB\Mapping\Annotations`

| FROM (Wrong)                                                     | TO (Correct)                           |
| ---------------------------------------------------------------- | -------------------------------------- |
| `src/Customer/Domain/Entity/Customer.php` with `#[ODM\Document]` | `config/doctrine/Customer.mongodb.xml` |

**Move annotations to XML**:

```
Customer.php                    →   config/doctrine/Customer.mongodb.xml
├─ #[ODM\Document]              →   <document name="..." collection="...">
├─ #[ODM\Id]                    →     <field name="id" id="true"/>
├─ #[ODM\Field]                 →     <field name="email" type="string"/>
└─ #[ODM\EmbedOne]              →     <embed-one field="..."/>
```

---

### Domain → API Platform

**Violation**: `uses ApiPlatform\Metadata\ApiResource`

| FROM (Wrong)                                                    | TO (Correct)                                                                                                   |
| --------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------- |
| `src/Customer/Domain/Entity/Customer.php` with `#[ApiResource]` | Option 1: `config/api_platform/Customer.yaml`<br>Option 2: `src/Customer/Application/DTO/CustomerResource.php` |

**Option 1: Move to YAML config**:

```
Customer.php                    →   config/api_platform/Customer.yaml
├─ #[ApiResource(...)]          →   resources:
├─ #[Get]                       →     App\...\Customer:
├─ #[GetCollection]             →       operations:
└─ #[Post]                      →         get: ~
```

**Option 2: Move to Application DTO**:

```
Customer.php (Domain)           →   CustomerResource.php (Application/DTO/)
└─ Clean entity, no API         →   ├─ #[ApiResource]
                                    ├─ #[Get], #[Post]
                                    └─ fromEntity() method
```

---

### Infrastructure → Application Handler

**Violation**: `uses App\...\Application\CommandHandler\...Handler`

| FROM (Wrong)                                                                                     | TO (Correct)                               |
| ------------------------------------------------------------------------------------------------ | ------------------------------------------ |
| `src/Customer/Infrastructure/EventListener/DoctrineListener.php`<br>injecting `SendEmailHandler` | Use `CommandBusInterface` or Domain Events |

**Refactor to use bus**:

```
DoctrineListener.php            →   DoctrineListener.php (refactored)
├─ SendEmailHandler $handler    →   CommandBusInterface $commandBus
└─ ($handler)(new Command())    →   $commandBus->dispatch(new Command())
```

**Better: Use Domain Events**:

```
DoctrineListener.php            →   DELETE (no longer needed)

Customer.php                    →   Customer.php
└─ save() method                →   └─ $this->record(new CustomerCreated(...))

SendEmailOnCustomerCreated.php  →   (Application/EventSubscriber/)
└─ subscribedTo()               →   └─ handles CustomerCreated
```

## Quick File Placement Checklist

### Domain Layer Files (NO framework imports!)

```bash
src/{Context}/Domain/
├── Entity/
│   └── {EntityName}.php                # Aggregate roots and entities
├── ValueObject/
│   └── {ConceptName}.php               # Email.php, Money.php, CustomerId.php
├── Event/
│   └── {Entity}{PastAction}.php        # CustomerCreated.php, OrderPlaced.php
├── Repository/
│   └── {Entity}RepositoryInterface.php # Interface only, not implementation
├── Exception/
│   └── {Specific}Exception.php         # InvalidEmailException.php
└── Collection/
    └── {Entity}Collection.php          # CustomerCollection.php
```

### Application Layer Files (CAN use Symfony, API Platform)

```bash
src/{Context}/Application/
├── Command/
│   └── {Action}{Entity}Command.php     # CreateCustomerCommand.php
├── CommandHandler/
│   └── {Action}{Entity}Handler.php     # CreateCustomerHandler.php
├── EventSubscriber/
│   └── {Action}On{Event}.php           # SendEmailOnCustomerCreated.php
├── DTO/
│   └── {Entity}{Type}.php              # CustomerInput.php, CustomerOutput.php
├── Processor/
│   └── {Action}{Entity}Processor.php   # CreateCustomerProcessor.php
└── Transformer/
    └── {From}To{To}Transformer.php     # CustomerToArrayTransformer.php
```

### Infrastructure Layer Files (Implements Domain interfaces)

```bash
src/{Context}/Infrastructure/
└── Repository/
    └── {Technology}{Entity}Repository.php  # MongoDBCustomerRepository.php

config/doctrine/
└── {Entity}.mongodb.xml                    # Customer.mongodb.xml
```

## Real-World Examples from CodelyTV

### Example 1: Course Module Structure

```bash
src/Mooc/Courses/
├── Application/
│   └── Create/
│       ├── CourseCreator.php               # Service that creates course
│       ├── CreateCourseCommand.php         # Immutable command object
│       └── CreateCourseCommandHandler.php  # Handles the command
│
├── Domain/
│   ├── Course.php                          # Aggregate root (NO Doctrine!)
│   ├── CourseCreatedDomainEvent.php        # Event when created
│   ├── CourseDuration.php                  # Value Object
│   ├── CourseName.php                      # Value Object
│   ├── CourseNotExist.php                  # Domain exception
│   └── CourseRepository.php                # Interface only
│
└── Infrastructure/
    └── Persistence/
        ├── Doctrine/
        │   └── Course.orm.xml              # XML mapping
        ├── DoctrineCourseRepository.php    # Implements CourseRepository
        └── FileCourseRepository.php        # Alternative implementation
```

### Example 2: Shared Kernel Structure

```bash
src/Shared/Domain/
├── ValueObject/
│   ├── IntValueObject.php      # Base class for int VOs
│   ├── StringValueObject.php   # Base class for string VOs
│   ├── SimpleUuid.php          # UUID value object
│   └── Uuid.php                # UUID with validation
│
└── Bus/
    ├── Command/
    │   ├── Command.php         # Command marker interface
    │   ├── CommandBus.php      # Bus interface
    │   └── CommandHandler.php  # Handler interface
    │
    └── Event/
        ├── DomainEvent.php             # Base event class
        ├── EventBus.php                # Event bus interface
        └── DomainEventSubscriber.php   # Subscriber interface
```

## Summary: Fix Violation → Move File

| Violation Type                 | Source File                  | Destination                                             |
| ------------------------------ | ---------------------------- | ------------------------------------------------------- |
| Domain → Symfony Validator     | Entity with `#[Assert]`      | New Value Object in Domain/ValueObject/                 |
| Domain → Doctrine              | Entity with `#[ODM]`         | XML mapping in config/doctrine/                         |
| Domain → API Platform          | Entity with `#[ApiResource]` | YAML in config/api_platform/ OR DTO in Application/DTO/ |
| Infrastructure → Handler       | Direct handler injection     | Use CommandBusInterface                                 |
| Application → External Service | Direct service call          | Create Domain interface, Infrastructure implementation  |

---

**Remember**: The structure reflects the business domain. Files should be where they conceptually belong, not where it's convenient for the framework.
