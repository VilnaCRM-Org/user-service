# Common Mistakes and How to Fix Them

This guide covers the most common mistakes when working with Structurizr workspace.dsl files and how to fix them.

## 1. Filtered Views Causing "Element Does Not Exist" Errors

### ❌ Wrong

```dsl
views {
    component softwareSystem.serviceName "Components_User" {
        include ->user->
        include ->userStatus->
        include ->userType->
        autolayout lr
    }
}
```

**Error Message**:

```text
workspace.dsl: The element "user" does not exist at line 242
```

### ✅ Correct

```dsl
views {
    component softwareSystem.serviceName "Components_All" {
        include *
    }
}
```

### Why This Happens

- The `->component->` syntax for filtered includes is error-prone
- Component variable names must match exactly
- Syntax is overly complex and not worth the effort
- Single comprehensive view is clearer

### Solution

**Always use a single view with `include *`** to show all components. If you need different views, use manual positioning in the UI rather than trying to create filtered DSL views.

---

## 2. External Dependencies Placed Inside Groups

### ❌ Wrong

```dsl
group "Infrastructure" {
    entityRepository = component "EntityRepository" "..." "Repository" {
        tags "Item"
    }

    database = component "Database" "Stores application data" "MariaDB" {
        tags "Database"
    }

    cache = component "Cache" "Caches data" "Redis" {
        tags "Database"
    }
}
```

### ✅ Correct

```dsl
group "Infrastructure" {
    entityRepository = component "EntityRepository" "..." "Repository" {
        tags "Item"
    }
}

// External dependencies OUTSIDE any group
database = component "Database" "Stores application data" "MariaDB" {
    tags "Database"
}

cache = component "Cache" "Caches data" "Redis" {
    tags "Database"
}
```

### Why This Matters

- External dependencies are not part of your infrastructure layer code
- They are external systems your service depends on
- Placing them outside groups makes this clear visually
- Follows user-service pattern exactly

### Solution

**Always place external dependencies** (database, cache, message broker) **at container level**, after all groups but before relationships.

---

## 3. Using `autolayout` Directive

### ❌ Wrong

```dsl
views {
    component softwareSystem.serviceName "Components_All" {
        include *
        autolayout lr 150 150
    }
}
```

or

```dsl
views {
    component softwareSystem.serviceName "Components_All" {
        include *
        autolayout tb 100 150
    }
}
```

### ✅ Correct

```dsl
views {
    component softwareSystem.serviceName "Components_All" {
        include *
    }
}
```

Then **position manually in the UI**:

1. Open <http://localhost:8080>
2. Drag components to arrange them
3. Click "Save workspace" button
4. Commit the generated `workspace.json` file

### Why autolayout Doesn't Work

- Automatic layout algorithms don't understand your architecture
- Results in messy, hard-to-read diagrams
- Can't control which components are near each other
- User-service pattern uses manual positioning

### Solution

**Never use `autolayout`**. Always position components manually in the Structurizr UI and save the layout to `workspace.json`.

---

## 4. Over-Documenting Internal Implementation Details

### ❌ Wrong (Too Many Components)

```dsl
group "Domain" {
    user = component "User" "Entity" {
        tags "Item"
    }
    userId = component "UserId" "ValueObject" {
        tags "Item"
    }
    userEmail = component "UserEmail" "ValueObject" {
        tags "Item"
    }
    userPhone = component "UserPhone" "ValueObject" {
        tags "Item"
    }
    userFactory = component "UserFactory" "Factory" {
        tags "Item"
    }
    userFactoryInterface = component "UserFactoryInterface" "Interface" {
        tags "Item"
    }
    userValidator = component "UserValidator" "Validator" {
        tags "Item"
    }
}
```

**Result**: Cluttered diagram with 40+ components that's hard to read.

### ✅ Correct (Focus on Significance)

```dsl
group "Domain" {
    user = component "User" "Represents a user aggregate" "Entity" {
        tags "Item"
    }
    confirmationToken = component "ConfirmationToken" "Represents user confirmation token" "Entity" {
        tags "Item"
    }
}
```

**Result**: Clean diagram with 20 components that shows the architecture.

### Why This Matters

- C4 diagrams are for **architecture**, not **implementation**
- Value objects, interfaces, factories are implementation details
- Focus on components that matter to understanding the system
- Target: 15-25 components per diagram

### Solution

**Only document architecturally significant components**:

✅ **DO document**:

- Processors (API handlers)
- Command Handlers
- Event Subscribers
- Entities (main domain objects)
- Domain Events
- Repositories
- Event Bus

❌ **DON'T document**:

- Value objects
- Factories
- Interfaces
- Transformers
- Base classes
- DTOs
- Validators
- Utilities

---

## 5. Adding Code-Style Comments

### ❌ Wrong

```dsl
group "Application" {
    // User Processors - These handle HTTP requests
    registerUserProcessor = component "RegisterUserProcessor" "..." {
        tags "Item"
    }
    // This one handles PATCH for partial updates
    userPatchProcessor = component "UserPatchProcessor" "..." {
        tags "Item"
    }
    // Full replacement with PUT
    userPutProcessor = component "UserPutProcessor" "..." {
        tags "Item"
    }
}
```

### ✅ Correct

```dsl
group "Application" {
    registerUserProcessor = component "RegisterUserProcessor" "Processes HTTP requests for user registration" "RequestProcessor" {
        tags "Item"
    }
    userPatchProcessor = component "UserPatchProcessor" "Processes HTTP requests for user updates" "RequestProcessor" {
        tags "Item"
    }
    userPutProcessor = component "UserPutProcessor" "Processes HTTP requests for user replacement" "RequestProcessor" {
        tags "Item"
    }
}
```

### Why Avoid Comments

- User-service pattern uses no comments
- Component descriptions should be self-documenting
- Comments clutter the DSL
- Use descriptive component names and descriptions instead

### Solution

**Use descriptive component descriptions** in the component definition itself. No need for separate comments.

---

## 6. Inconsistent Component Naming

### ❌ Wrong

```dsl
group "Application" {
    createProc = component "RegisterUserProcessor" "..." {
        tags "Item"
    }
    handler1 = component "UpdateUserCommandHandler" "..." {
        tags "Item"
    }
    mysql_user_repo = component "DoctrineUserRepository" "..." {
        tags "Item"
    }
}
```

### ✅ Correct

```dsl
group "Application" {
    registerUserProcessor = component "RegisterUserProcessor" "..." "RequestProcessor" {
        tags "Item"
    }
    updateUserCommandHandler = component "UpdateUserCommandHandler" "..." "CommandHandler" {
        tags "Item"
    }
}

group "Infrastructure" {
    doctrineUserRepository = component "DoctrineUserRepository" "..." "Repository" {
        tags "Item"
    }
}
```

### Why Consistency Matters

- Variable names should match class names (camelCase)
- Makes it easy to find components in DSL
- Follows user-service pattern
- Improves maintainability

### Solution

**Use consistent naming**:

- Variable name = camelCase version of class name
- Display name = exact class name
- Include component type (third parameter)

---

## 7. Missing Component Types

### ❌ Wrong

```dsl
registerUserProcessor = component "RegisterUserProcessor" "Processes HTTP requests" {
    tags "Item"
}
```

### ✅ Correct

```dsl
registerUserProcessor = component "RegisterUserProcessor" "Processes HTTP requests for user registration" "RequestProcessor" {
    tags "Item"
}
```

### Why Component Types Matter

- Shows what kind of component it is
- Helps understand architecture at a glance
- Follows user-service pattern
- Makes diagrams more informative

### Common Component Types

- `RequestProcessor` - HTTP/GraphQL request handlers
- `CommandHandler` - CQRS command handlers
- `EventSubscriber` - Domain event subscribers
- `Entity` - Domain entities
- `DomainEvent` - Domain events
- `Repository` - Data access
- `EventBus` - Event publishing
- `Controller` - Controllers for non-CRUD operations
- `MariaDB`, `Redis`, `AWS SQS` - External dependencies

---

## 8. Relationships Without Descriptions

### ❌ Wrong

```dsl
registerUserProcessor -> registerUserCommandHandler
registerUserCommandHandler -> user
registerUserCommandHandler -> doctrineUserRepository
```

### ✅ Correct

```dsl
registerUserProcessor -> registerUserCommandHandler "dispatches RegisterUserCommand"
registerUserCommandHandler -> user "creates"
registerUserCommandHandler -> doctrineUserRepository "persists via"
```

### Why Descriptions Matter

- Shows the nature of the relationship
- Makes the flow clear
- Helps understand data flow and dependencies
- Follows user-service pattern

### Common Relationship Descriptions

- `"dispatches XCommand"` - Processor to handler
- `"creates"` / `"updates"` / `"deletes"` - Handler to entity
- `"uses"` / `"persists via"` - Handler to repository
- `"save and load"` - Repository to entity
- `"accesses data"` - Repository to database
- `"publishes"` - Handler to event
- `"triggers"` - Event to subscriber
- `"sends to"` - Subscriber to external service

---

## 9. Circular Relationships

### ❌ Wrong

```dsl
userProcessor -> userHandler "dispatches command"
userHandler -> userProcessor "returns result"
```

### ✅ Correct

```dsl
userProcessor -> userHandler "dispatches RegisterUserCommand"
userHandler -> user "creates"
userHandler -> repository "persists via"
```

### Why Avoid Circulars

- Indicates design problem
- Makes diagrams hard to read
- Violates hexagonal architecture
- Not how CQRS/event-driven systems work

### Solution

**Model one-way dependencies**:

- Processors call handlers (not vice versa)
- Handlers use repositories (not vice versa)
- Events trigger subscribers (not vice versa)

---

## 10. Forgetting to Commit workspace.json

### ❌ Wrong

```bash
git add workspace.dsl
git commit -m "Update architecture"
```

**Result**: Manual positions are lost, diagram resets to default layout.

### ✅ Correct

```bash
git add workspace.dsl workspace.json
git commit -m "feat: update architecture with new processor"
```

### Why workspace.json Matters

- Stores manual component positions
- Generated when you click "Save workspace" in UI
- Must be committed along with workspace.dsl
- Without it, team members see unpositioned diagrams

### Solution

**Always commit both files** after making changes and positioning components.

---

## Quick Checklist

Before committing workspace.dsl changes:

- [ ] Using single view with `include *` (no filtered views)
- [ ] External dependencies outside groups
- [ ] No `autolayout` directive
- [ ] 15-25 components (not 40+)
- [ ] No code-style comments
- [ ] Consistent camelCase naming
- [ ] All components have type parameter
- [ ] All relationships have descriptions
- [ ] No circular dependencies
- [ ] workspace.json committed with workspace.dsl
- [ ] Diagram renders without errors at <http://localhost:8080>

## Getting Help

If you encounter errors:

1. **Check Structurizr UI** - Open <http://localhost:8080>, errors shown at top
2. **Validate syntax** - Compare with [workspace-template.md](workspace-template.md)
3. **Check examples** - Look at user-service (VilnaCRM organization reference): <https://github.com/VilnaCRM-Org/user-service/blob/main/workspace.dsl>
4. **Start fresh** - Sometimes easier to rebuild from template than debug

## Related Documentation

- [Workspace Template](workspace-template.md) - Complete working template
- [DSL Syntax](dsl-syntax.md) - Full DSL syntax reference
- [Component Identification](component-identification.md) - What to document
