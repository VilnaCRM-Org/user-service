# Complete workspace.dsl Template

This template shows the complete structure of a workspace.dsl file following the user-service pattern.

## Full Template

```dsl
workspace {

    !identifiers hierarchical

    model {
        properties {
            "structurizr.groupSeparator" "/"
        }

        softwareSystem = softwareSystem "VilnaCRM" {
            serviceName = container "User Service" {

                group "Application" {
                    // Processors (HTTP/GraphQL handlers)
                    registerUserProcessor = component "RegisterUserProcessor" "Processes HTTP requests for user registration" "RequestProcessor" {
                        tags "Item"
                    }
                    userPatchProcessor = component "UserPatchProcessor" "Processes HTTP requests for user updates" "RequestProcessor" {
                        tags "Item"
                    }
                    userPutProcessor = component "UserPutProcessor" "Processes HTTP requests for user replacement" "RequestProcessor" {
                        tags "Item"
                    }

                    // Command Handlers (CQRS)
                    registerUserCommandHandler = component "RegisterUserCommandHandler" "Handles RegisterUserCommand" "CommandHandler" {
                        tags "Item"
                    }
                    updateUserCommandHandler = component "UpdateUserCommandHandler" "Handles UpdateUserCommand" "CommandHandler" {
                        tags "Item"
                    }

                    // Event Subscribers
                    userRegisteredSubscriber = component "UserRegisteredSubscriber" "Handles UserRegisteredEvent" "EventSubscriber" {
                        tags "Item"
                    }
                    userConfirmedSubscriber = component "UserConfirmedSubscriber" "Handles UserConfirmedEvent" "EventSubscriber" {
                        tags "Item"
                    }

                    // Controllers (for non-CRUD operations)
                    healthCheckController = component "HealthCheckController" "Handles health check requests" "Controller" {
                        tags "Item"
                    }
                }

                group "Domain" {
                    // Entities
                    user = component "User" "Represents the user entity" "Entity" {
                        tags "Item"
                    }
                    confirmationToken = component "ConfirmationToken" "Represents a user confirmation token" "Entity" {
                        tags "Item"
                    }

                    // Domain Events
                    userRegisteredEvent = component "UserRegisteredEvent" "Represents user registration event" "DomainEvent" {
                        tags "Item"
                    }
                    userConfirmedEvent = component "UserConfirmedEvent" "Represents user confirmation event" "DomainEvent" {
                        tags "Item"
                    }
                }

                group "Infrastructure" {
                    // Repositories
                    userRepository = component "UserRepository" "Manages access to users" "Repository" {
                        tags "Item"
                    }
                    confirmationTokenRepository = component "ConfirmationTokenRepository" "Manages access to confirmation tokens" "Repository" {
                        tags "Item"
                    }

                    // Infrastructure Services
                    eventBus = component "EventBus" "Handles event publishing" "EventBus" {
                        tags "Item"
                    }
                    mailer = component "Mailer" "Manages sending emails" "MailService" {
                        tags "Item"
                    }
                }

                // External Dependencies (OUTSIDE groups)
                database = component "Database" "Stores application data" "MariaDB" {
                    tags "Database"
                }
                cache = component "Cache" "Caches application data" "Redis" {
                    tags "Database"
                }
                messageBroker = component "Message Broker" "Handles asynchronous messaging" "AWS SQS" {
                    tags "Database"
                }

                // Relationships - Processor → Handler Flow
                registerUserProcessor -> registerUserCommandHandler "dispatches RegisterUserCommand"
                userPatchProcessor -> updateUserCommandHandler "dispatches UpdateUserCommand"
                userPutProcessor -> updateUserCommandHandler "dispatches UpdateUserCommand"

                // Relationships - Handler → Entity → Repository
                registerUserCommandHandler -> user "creates"
                updateUserCommandHandler -> user "updates"
                registerUserCommandHandler -> userRepository "persists via"
                updateUserCommandHandler -> userRepository "uses"

                // Relationships - Repository → Database
                userRepository -> user "save and load"
                userRepository -> database "accesses data"
                confirmationTokenRepository -> confirmationToken "save and load"
                confirmationTokenRepository -> database "accesses data"

                // Relationships - Event Flow
                registerUserCommandHandler -> userRegisteredEvent "publishes"
                userRegisteredEvent -> userRegisteredSubscriber "triggers"
                userConfirmedEvent -> userConfirmedSubscriber "triggers"

                // Relationships - Subscriber → External Services
                userRegisteredSubscriber -> messageBroker "sends to"
                userRegisteredSubscriber -> mailer "sends email via"

                // Relationships - Health Check
                healthCheckController -> eventBus "publishes via"
                eventBus -> userRegisteredEvent "dispatches"
            }
        }
    }

    views {
        component softwareSystem.serviceName "Components_All" {
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

## Key Structure Points

### 1. Header

```dsl
workspace {
    !identifiers hierarchical

    model {
        properties {
            "structurizr.groupSeparator" "/"
        }
```

- `!identifiers hierarchical` - Use hierarchical identifiers
- `groupSeparator "/"` - Use forward slash for group separation

### 2. Software System and Container

```dsl
softwareSystem = softwareSystem "VilnaCRM" {
    serviceName = container "User Service" {
        // Components go here
    }
}
```

- Software system name: "VilnaCRM"
- Container name: Your service name (e.g., "User Service", "Core Service")

### 3. Layer Groups

**Three groups in this order**:

```dsl
group "Application" { ... }
group "Domain" { ... }
group "Infrastructure" { ... }
```

**Component placement**:

- **Application**: Processors, Handlers, Subscribers, Controllers
- **Domain**: Entities, Domain Events
- **Infrastructure**: Repositories, Event Bus, Infrastructure services

### 4. External Dependencies

Place **OUTSIDE any group**, after all groups:

```dsl
database = component "Database" "..." "MariaDB" {
    tags "Database"
}
cache = component "Cache" "..." "Redis" {
    tags "Database"
}
messageBroker = component "Message Broker" "..." "AWS SQS" {
    tags "Database"
}
```

### 5. Relationships Section

Place **AFTER** all component definitions:

```dsl
// Processor → Handler
processor -> handler "dispatches XCommand"

// Handler → Entity → Repository
handler -> entity "creates/updates"
handler -> repository "uses"

// Repository → Database
repository -> entity "save and load"
repository -> database "accesses data"

// Event Flow
handler -> event "publishes"
event -> subscriber "triggers"
subscriber -> messageBroker "sends to"
```

### 6. Views and Styles

```dsl
views {
    component softwareSystem.serviceName "Components_All" {
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
```

**DO NOT add**:

- Multiple views (use single `Components_All`)
- `autolayout` directive (position manually in UI)

## Adapting This Template

### For Your Service

1. **Replace service name**:

   ```dsl
   serviceName = container "User Service" {  // Your service name
   ```

2. **Add your components** in appropriate groups:

   - List all processors
   - List all command handlers
   - List all event subscribers
   - List your entities
   - List your repositories

3. **Define relationships**:

   - Start with processor → handler flows
   - Add handler → entity → repository chains
   - Add event flows if using events

4. **Keep it focused**:
   - Target 15-25 components
   - Focus on architectural significance
   - Omit factories, transformers, value objects

## Real Examples

- **User Service**: See `/workspace.dsl` in project root
- **User Service** (VilnaCRM organization reference): <https://github.com/VilnaCRM-Org/user-service/blob/main/workspace.dsl>

## Component Counts by Service

| Service      | Components | Notes               |
| ------------ | ---------- | ------------------- |
| User Service | 23         | Good balance        |
| Core Service | 21         | Clean and focused   |
| Target Range | 15-25      | Optimal for clarity |

## Next Steps

1. Copy this template
2. Replace placeholder names with your components
3. Verify syntax: Check <http://localhost:8080> for errors
4. Position components in UI and save
5. Commit both workspace.dsl and workspace.json
