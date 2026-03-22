# Example: Adding CQRS Pattern

Complete example of documenting a CQRS command flow in Structurizr.

## Scenario

Implementing a new feature: **Register User** using CQRS pattern.

### Components Implemented

**Application Layer**:

- `RegisterUserCommand` (command)
- `RegisterUserCommandHandler` (handler)

**Domain Layer**:

- `User` (entity/aggregate)
- `UserId` (value object)
- `UserEmail` (value object)
- `UserPassword` (value object)
- `UserRegisteredEvent` (domain event)
- `UserRepositoryInterface` (port)

**Infrastructure Layer**:

- `UserRepository` (adapter)
- `SendConfirmationEmailSubscriber` (event subscriber)
- `InMemorySymfonyEventBus` (event bus)

## Step 1: Add Application Layer Components

```dsl
group "Application" {
    registerUserCommandHandler = component "RegisterUserCommandHandler" "Handles user registration commands" "CommandHandler" {
        tags "Item"
    }
}
```

**Note**: We don't add `RegisterUserCommand` itself as it's just a data structure (DTO pattern).

## Step 2: Add Domain Layer Components

```dsl
group "Domain" {
    user = component "User" "User aggregate root" "Aggregate" {
        tags "Item"
    }

    userId = component "UserId" "User unique identifier" "ValueObject" {
        tags "Item"
    }

    userEmail = component "UserEmail" "User email with validation" "ValueObject" {
        tags "Item"
    }

    userPassword = component "UserPassword" "User password with hashing" "ValueObject" {
        tags "Item"
    }

    userRegisteredEvent = component "UserRegisteredEvent" "Event published when user is registered" "DomainEvent" {
        tags "Item"
    }

    userRepositoryInterface = component "UserRepositoryInterface" "Contract for user persistence" "Interface" {
        tags "Item"
    }
}
```

## Step 3: Add Infrastructure Layer Components

```dsl
group "Infrastructure" {
    userRepository = component "UserRepository" "MariaDB implementation of user persistence" "Repository" {
        tags "Item"
    }

    sendConfirmationEmailSubscriber = component "SendConfirmationEmailSubscriber" "Sends confirmation email on user registration" "EventSubscriber" {
        tags "Item"
    }

    inMemorySymfonyEventBus = component "InMemorySymfonyEventBus" "Handles event publishing" "EventBus" {
        tags "Item"
    }
}
```

## Step 4: Add External Dependencies (if not already present)

```dsl
database = component "Database" "MariaDB instance" "MariaDB" {
    tags "Database"
}

messageBroker = component "Message Broker" "AWS SQS for async messaging" "AWS SQS" {
    tags "Database"
}
```

## Step 5: Add Relationships

### Command Flow

```dsl
# Handler creates aggregate
registerUserCommandHandler -> user "creates"

# Handler depends on repository interface (hexagonal port)
registerUserCommandHandler -> userRepositoryInterface "depends on"

# Handler publishes event
registerUserCommandHandler -> userRegisteredEvent "publishes"
```

### Domain Model Relationships

```dsl
# User aggregate has value objects
user -> userId "has"
user -> userEmail "has"
user -> userPassword "has"

# User aggregate creates event
user -> userRegisteredEvent "creates"
```

### Infrastructure Relationships

```dsl
# Repository implements interface
userRepository -> userRepositoryInterface "implements"

# Repository stores user
userRepository -> user "stores / retrieves"

# Repository persists to database
userRepository -> database "persists to"
```

### Event Flow Relationships

```dsl
# Event triggers subscriber
userRegisteredEvent -> sendConfirmationEmailSubscriber "triggers"

# Subscriber uses event bus
sendConfirmationEmailSubscriber -> inMemorySymfonyEventBus "uses"

# Subscriber sends message via broker
sendConfirmationEmailSubscriber -> messageBroker "sends via"
```

## Complete workspace.dsl Section

```dsl
workspace {
    !identifiers hierarchical

    model {
        properties {
            "structurizr.groupSeparator" "/"
        }

        softwareSystem = softwareSystem "VilnaCRM" {
            userService = container "User Service" {

                group "Application" {
                    registerUserCommandHandler = component "RegisterUserCommandHandler" "Handles user registration commands" "CommandHandler" {
                        tags "Item"
                    }
                }

                group "Domain" {
                    user = component "User" "User aggregate root" "Aggregate" {
                        tags "Item"
                    }
                    userId = component "UserId" "User unique identifier" "ValueObject" {
                        tags "Item"
                    }
                    userEmail = component "UserEmail" "User email with validation" "ValueObject" {
                        tags "Item"
                    }
                    userPassword = component "UserPassword" "User password with hashing" "ValueObject" {
                        tags "Item"
                    }
                    userRegisteredEvent = component "UserRegisteredEvent" "Event published when user is registered" "DomainEvent" {
                        tags "Item"
                    }
                    userRepositoryInterface = component "UserRepositoryInterface" "Contract for user persistence" "Interface" {
                        tags "Item"
                    }
                }

                group "Infrastructure" {
                    userRepository = component "UserRepository" "MariaDB implementation of user persistence" "Repository" {
                        tags "Item"
                    }
                    sendConfirmationEmailSubscriber = component "SendConfirmationEmailSubscriber" "Sends confirmation email on user registration" "EventSubscriber" {
                        tags "Item"
                    }
                    inMemorySymfonyEventBus = component "InMemorySymfonyEventBus" "Handles event publishing" "EventBus" {
                        tags "Item"
                    }
                }

                database = component "Database" "MariaDB instance" "MariaDB" {
                    tags "Database"
                }
                messageBroker = component "Message Broker" "AWS SQS for async messaging" "AWS SQS" {
                    tags "Database"
                }

                # Command flow
                registerUserCommandHandler -> user "creates"
                registerUserCommandHandler -> userRepositoryInterface "depends on"
                registerUserCommandHandler -> userRegisteredEvent "publishes"

                # Domain model
                user -> userId "has"
                user -> userEmail "has"
                user -> userPassword "has"
                user -> userRegisteredEvent "creates"

                # Infrastructure implementation
                userRepository -> userRepositoryInterface "implements"
                userRepository -> user "stores / retrieves"
                userRepository -> database "persists to"

                # Event flow
                userRegisteredEvent -> sendConfirmationEmailSubscriber "triggers"
                sendConfirmationEmailSubscriber -> inMemorySymfonyEventBus "uses"
                sendConfirmationEmailSubscriber -> messageBroker "sends via"
            }
        }
    }

    views {
        component softwareSystem.userService "Components_All" {
            include *
        }

        styles {
            element "Item" {
                color white
                background #34abeb
            }
            element "Database" {
                color white
                shape cylinder
                background #34abeb
            }
        }
    }
}
```

## Visual Result

The generated diagram will show:

1. **Application Layer** (top):

   - RegisterUserCommandHandler

2. **Domain Layer** (middle):

   - User aggregate with value objects
   - UserRegisteredEvent
   - UserRepositoryInterface (port)

3. **Infrastructure Layer** (bottom):

   - UserRepository implementing the interface
   - SendConfirmationEmailSubscriber
   - InMemorySymfonyEventBus

4. **External Systems**:

   - Database (MariaDB)
   - Message Broker (AWS SQS)

5. **Flow**:
   - Handler → Creates User → Uses Repository Interface
   - Handler → Publishes UserRegisteredEvent
   - Event → Triggers Subscriber
   - Subscriber → Sends via Message Broker
   - Repository → Implements Interface
   - Repository → Persists to Database

## Verification Checklist

- [x] All command handler components documented
- [x] Domain model (entity + value objects) documented
- [x] Domain event documented
- [x] Repository interface (port) documented
- [x] Repository implementation (adapter) documented
- [x] Event subscriber documented
- [x] External dependencies (database, broker) documented
- [x] Command flow relationships clear
- [x] Domain model relationships clear
- [x] Infrastructure implementation relationships clear
- [x] Event flow relationships clear
- [x] Hexagonal architecture visible (port/adapter pattern)
- [x] Layer groupings correct
- [x] No DTOs included (RegisterUserCommand omitted)

## Common Questions

### Q: Should I include RegisterUserCommand?

**A**: No. Commands are data structures (DTOs) without behavior. They are not architecturally significant components.

### Q: Should I include all value objects?

**A**: Include value objects with significant business logic (validation, formatting). For simple wrapper value objects (like `FirstName`, `LastName`), consider omitting to reduce clutter.

### Q: How do I show that the handler uses the repository?

**A**: Show dependency on the repository **interface** (port), not the implementation. This highlights hexagonal architecture:

```dsl
registerUserCommandHandler -> userRepositoryInterface "depends on"
userRepository -> userRepositoryInterface "implements"
```

### Q: Should I show the event bus explicitly?

**A**: Yes, if it's a significant infrastructure component. It shows how events are distributed.

### Q: What if I have multiple subscribers for the same event?

**A**: Show all subscriber relationships:

```dsl
userRegisteredEvent -> sendConfirmationEmailSubscriber "triggers"
userRegisteredEvent -> updateAnalyticsSubscriber "triggers"
userRegisteredEvent -> logAuditSubscriber "triggers"
```

## Next Steps

After documenting the CQRS pattern:

1. **Validate DSL syntax**:

   ```bash
   structurizr-cli validate workspace.dsl
   ```

2. **Generate diagram**:

   ```bash
   docker run -it --rm -p 8080:8080 \
     -v $(pwd):/usr/local/structurizr \
     structurizr/lite
   ```

3. **Review visually**: Check http://localhost:8080

4. **Update documentation**: Use [documentation-sync](../../documentation-sync/SKILL.md) skill

5. **Run CI checks**: Use [ci-workflow](../../ci-workflow/SKILL.md) skill
