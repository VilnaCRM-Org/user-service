# Example: Refactoring Components

Complete example of updating Structurizr documentation during refactoring.

## Scenario

Refactoring: **Split UserCommandHandler into separate handlers** following Single Responsibility Principle.

### Before Refactoring

```php
// Single handler for all user commands
class UserCommandHandler implements CommandHandlerInterface
{
    public function handleRegister(RegisterUserCommand $command): void { }
    public function handleUpdate(UpdateUserCommand $command): void { }
    public function handleConfirm(ConfirmUserCommand $command): void { }
}
```

### After Refactoring

```php
// Separate handlers
class RegisterUserCommandHandler implements CommandHandlerInterface { }
class UpdateUserCommandHandler implements CommandHandlerInterface { }
class ConfirmUserCommandHandler implements CommandHandlerInterface { }
```

## Step 1: Review Current workspace.dsl

**Before**:

```dsl
group "Application" {
    userCommandHandler = component "UserCommandHandler" "Handles all user commands" "CommandHandler" {
        tags "Item"
    }
}

# Relationships
userCommandHandler -> user "creates / updates / confirms"
userCommandHandler -> userRepository "uses"
userCommandHandler -> userRegisteredEvent "publishes"
userCommandHandler -> userConfirmedEvent "publishes"
userCommandHandler -> emailChangedEvent "publishes"
```

## Step 2: Identify Changes

**Components to add**:

- `RegisterUserCommandHandler`
- `UpdateUserCommandHandler`
- `ConfirmUserCommandHandler`

**Components to remove**:

- `UserCommandHandler` (replaced by specific handlers)

**Relationships to update**:

- Each handler has specific responsibilities
- Each handler publishes specific events

## Step 3: Update workspace.dsl

### Remove Old Component

```dsl
# DELETE this section
userCommandHandler = component "UserCommandHandler" "Handles all user commands" "CommandHandler" {
    tags "Item"
}
```

### Add New Components

```dsl
group "Application" {
    registerUserHandler = component "RegisterUserCommandHandler" "Handles user registration" "CommandHandler" {
        tags "Item"
    }

    updateUserHandler = component "UpdateUserCommandHandler" "Handles user updates" "CommandHandler" {
        tags "Item"
    }

    confirmUserHandler = component "ConfirmUserCommandHandler" "Handles user confirmation" "CommandHandler" {
        tags "Item"
    }
}
```

### Update Relationships

**Remove old relationships**:

```dsl
# DELETE these relationships
userCommandHandler -> user "creates / updates / confirms"
userCommandHandler -> userRepository "uses"
userCommandHandler -> userRegisteredEvent "publishes"
userCommandHandler -> userConfirmedEvent "publishes"
userCommandHandler -> emailChangedEvent "publishes"
```

**Add new specific relationships**:

```dsl
# RegisterUserCommandHandler relationships
registerUserHandler -> user "creates"
registerUserHandler -> userRepository "uses for persistence"
registerUserHandler -> userRegisteredEvent "publishes"

# UpdateUserCommandHandler relationships
updateUserHandler -> user "updates"
updateUserHandler -> userRepository "uses for persistence"
updateUserHandler -> emailChangedEvent "publishes"

# ConfirmUserCommandHandler relationships
confirmUserHandler -> user "confirms"
confirmUserHandler -> userRepository "uses for persistence"
confirmUserHandler -> userConfirmedEvent "publishes"
```

## Complete Updated Section

```dsl
# Application Layer
group "Application" {
    registerUserHandler = component "RegisterUserCommandHandler" "Handles user registration" "CommandHandler" {
        tags "Item"
    }

    updateUserHandler = component "UpdateUserCommandHandler" "Handles user updates" "CommandHandler" {
        tags "Item"
    }

    confirmUserHandler = component "ConfirmUserCommandHandler" "Handles user confirmation" "CommandHandler" {
        tags "Item"
    }
}

# Domain Layer (unchanged)
group "Domain" {
    user = component "User" "User aggregate" "Entity" {
        tags "Item"
    }

    userRegisteredEvent = component "UserRegisteredEvent" ...
    userConfirmedEvent = component "UserConfirmedEvent" ...
    emailChangedEvent = component "EmailChangedEvent" ...

    userRepositoryInterface = component "UserRepositoryInterface" ...
}

# Infrastructure Layer (unchanged)
group "Infrastructure" {
    userRepository = component "UserRepository" ...
}

# RegisterUserCommandHandler flow
registerUserHandler -> user "creates"
registerUserHandler -> userRepositoryInterface "depends on"
registerUserHandler -> userRegisteredEvent "publishes"

# UpdateUserCommandHandler flow
updateUserHandler -> user "updates"
updateUserHandler -> userRepositoryInterface "depends on"
updateUserHandler -> emailChangedEvent "publishes"

# ConfirmUserCommandHandler flow
confirmUserHandler -> user "confirms"
confirmUserHandler -> userRepositoryInterface "depends on"
confirmUserHandler -> userConfirmedEvent "publishes"

# Infrastructure (unchanged)
userRepository -> userRepositoryInterface "implements"
userRepository -> user "stores / retrieves"
userRepository -> database "persists to"
```

## Visual Result

**Before**: Single UserCommandHandler with multiple responsibilities

**After**: Three handlers, each with clear single responsibility:

1. RegisterUserCommandHandler → Creates → Publishes UserRegisteredEvent
2. UpdateUserCommandHandler → Updates → Publishes EmailChangedEvent
3. ConfirmUserCommandHandler → Confirms → Publishes UserConfirmedEvent

## Example 2: Extracting Service

**Before**: Handler contains complex business logic

```dsl
registerUserHandler = component "RegisterUserCommandHandler" "Handles user registration with validation and password hashing" "CommandHandler" {
    tags "Item"
}
```

**After**: Extracted domain service

```dsl
# Application layer
registerUserHandler = component "RegisterUserCommandHandler" "Handles user registration" "CommandHandler" {
    tags "Item"
}

# Domain layer
passwordHashingService = component "PasswordHashingService" "Hashes user passwords securely" "DomainService" {
    tags "Item"
}

# Relationship
registerUserHandler -> passwordHashingService "uses for password hashing"
passwordHashingService -> user "applies hashed password to"
```

## Example 3: Moving Component Between Layers

**Before**: Validator in Infrastructure layer (Deptrac violation)

```dsl
group "Infrastructure" {
    emailValidator = component "EmailValidator" "Validates email format" "Validator" {
        tags "Item"
    }
}
```

**After**: Moved to Domain layer (correct layer)

```dsl
group "Domain" {
    emailValidator = component "EmailValidator" "Validates email format" "Validator" {
        tags "Item"
    }
}
```

**Update relationships** (if any reference this component):

```dsl
# No changes to relationships if variable name stays same
userEmail -> emailValidator "validates via"
```

## Example 4: Introducing Interface (Hexagonal Architecture)

**Before**: Direct dependency on implementation

```dsl
# Handler depends directly on repository
registerUserHandler -> userRepository "uses"
```

**After**: Dependency on interface (port)

```dsl
# Add interface to Domain layer
group "Domain" {
    userRepositoryInterface = component "UserRepositoryInterface" "Repository port" "Interface" {
        tags "Item"
    }
}

# Handler depends on interface
registerUserHandler -> userRepositoryInterface "depends on"

# Repository implements interface
userRepository -> userRepositoryInterface "implements"
```

## Example 5: Renaming Component

**Before**:

```dsl
userHandler = component "UserHandler" ...

# Relationships
controller -> userHandler "uses"
```

**After**:

```dsl
userCommandHandler = component "UserCommandHandler" ...

# Update relationships
controller -> userCommandHandler "uses"
```

**Important**: Update both component definition AND all relationships.

## Verification Checklist

After refactoring:

- [ ] Old components removed
- [ ] New components added with correct layer grouping
- [ ] Component types accurate
- [ ] Descriptions updated
- [ ] All old relationships removed
- [ ] All new relationships added
- [ ] Variable names updated in relationships
- [ ] No orphaned components (components without relationships)
- [ ] No broken relationships (referencing deleted components)
- [ ] DSL syntax valid
- [ ] Diagram generated successfully
- [ ] Layer boundaries respected

## Common Refactoring Patterns

### Pattern 1: Split Handler

**When**: Handler has multiple responsibilities

**Action**: Create separate handlers for each responsibility

**Update**: Replace one component with multiple, update relationships

### Pattern 2: Extract Service

**When**: Handler contains complex business logic

**Action**: Extract domain service

**Update**: Add domain service component, add handler → service relationship

### Pattern 3: Extract Value Object

**When**: Primitive obsession in entity

**Action**: Create value object

**Update**: Add value object to domain, add entity → value object relationship

### Pattern 4: Move Component to Correct Layer

**When**: Deptrac violation

**Action**: Move component to correct layer group

**Update**: Change group in workspace.dsl, verify relationships still valid

### Pattern 5: Introduce Interface

**When**: Violating dependency inversion principle

**Action**: Create interface in domain, implement in infrastructure

**Update**: Add interface component, change dependency relationships

## Automation Tips

### Use Version Control

**Before refactoring**:

```bash
git checkout -b refactor/split-user-handler
```

**Update workspace.dsl in same commit**:

```bash
git add src/User/Application/CommandHandler/RegisterUserCommandHandler.php
git add src/User/Application/CommandHandler/UpdateUserCommandHandler.php
git add src/User/Application/CommandHandler/ConfirmUserCommandHandler.php
git add workspace.dsl
git commit -m "refactor: split UserCommandHandler into separate handlers"
```

### Review Changes

**Diff workspace.dsl**:

```bash
git diff workspace.dsl
```

**Verify**:

- Components removed
- Components added
- Relationships updated

## Common Mistakes

### Mistake 1: Forgetting to Remove Old Component

**Problem**: Old component still in workspace.dsl after refactoring

**Solution**: Explicitly remove old component definition and relationships

### Mistake 2: Forgetting to Update Relationships

**Problem**: Relationships still reference old component variable name

**Solution**: Search workspace.dsl for old variable name, update all occurrences

### Mistake 3: Creating Orphaned Components

**Problem**: New component added but no relationships

**Solution**: Always add relationships when adding components

### Mistake 4: Breaking Relationships

**Problem**: Deleted component but relationships still reference it

**Solution**: Search for component variable name before deleting

### Mistake 5: Inconsistent Variable Names

**Problem**: Component variable name doesn't match new class name

**Solution**: Use consistent camelCase matching class name

## Next Steps

After refactoring components:

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

3. **Review refactoring visually**: Ensure new structure is clearer

4. **Run Deptrac**: Verify layer boundaries respected

   ```bash
   make deptrac
   ```

5. **Update documentation**: Use [documentation-sync](../../documentation-sync/SKILL.md) skill

6. **Run CI checks**: Use [ci-workflow](../../ci-workflow/SKILL.md) skill

7. **Commit changes**: Include workspace.dsl in same commit as code changes
