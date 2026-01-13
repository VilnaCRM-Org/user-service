# Example: Adding API Endpoint

Complete example of documenting a REST API endpoint with API Platform in Structurizr.

## Scenario

Implementing a new REST API endpoint: **GET /api/users/{id}** using API Platform.

### Components Implemented

**Application Layer**:

- `UserController` (API entry point)
- `UserItemProvider` (API Platform item provider)
- `UuidTransformer` (transforms UUID strings to value objects)

**Domain Layer**:

- `User` (entity)
- `Uuid` (value object)
- `UuidFactoryInterface` (factory port)

**Infrastructure Layer**:

- `UserRepository` (data access)
- `UuidFactory` (factory implementation)

## Step 1: Add Application Layer Components

```dsl
group "Application" {
    userController = component "UserController" "Handles user API requests" "Controller" {
        tags "Item"
    }

    userItemProvider = component "UserItemProvider" "Provides user items for API Platform" "ItemProvider" {
        tags "Item"
    }

    uuidTransformer = component "UuidTransformer" "Transforms UUID strings to value objects" "Transformer" {
        tags "Item"
    }
}
```

## Step 2: Add Domain Layer Components (if not already present)

```dsl
group "Domain" {
    user = component "User" "User entity" "Entity" {
        tags "Item"
    }

    uuid = component "Uuid" "UUID value object" "ValueObject" {
        tags "Item"
    }

    uuidFactoryInterface = component "UuidFactoryInterface" "Contract for UUID creation" "Interface" {
        tags "Item"
    }
}
```

## Step 3: Add Infrastructure Layer Components (if not already present)

```dsl
group "Infrastructure" {
    userRepository = component "UserRepository" "Retrieves users from MariaDB" "Repository" {
        tags "Item"
    }

    uuidFactory = component "UuidFactory" "Creates UUID value objects" "Factory" {
        tags "Item"
    }
}
```

## Step 4: Add External Dependencies (if not already present)

```dsl
database = component "Database" "MariaDB instance" "MariaDB" {
    tags "Database"
}
```

## Step 5: Add Relationships

### API Request Flow

```dsl
# Controller uses item provider
userController -> userItemProvider "delegates to"

# Item provider uses repository
userItemProvider -> userRepository "uses to retrieve user"

# Controller uses UUID transformer
userController -> uuidTransformer "uses to transform ID parameter"
```

### UUID Transformation Flow

```dsl
# Transformer depends on factory interface
uuidTransformer -> uuidFactoryInterface "uses"

# Factory implements interface
uuidFactory -> uuidFactoryInterface "implements"

# Factory creates UUID value object
uuidFactory -> uuid "creates"
```

### Data Access Flow

```dsl
# Repository retrieves user entity
userRepository -> user "retrieves"

# Repository reads from database
userRepository -> database "retrieves from"
```

## Complete workspace.dsl Addition

```dsl
# Application Layer
group "Application" {
    userController = component "UserController" "Handles user API requests" "Controller" {
        tags "Item"
    }

    userItemProvider = component "UserItemProvider" "Provides user items for API Platform" "ItemProvider" {
        tags "Item"
    }

    uuidTransformer = component "UuidTransformer" "Transforms UUID strings to value objects" "Transformer" {
        tags "Item"
    }
}

# Domain Layer
group "Domain" {
    user = component "User" "User entity" "Entity" {
        tags "Item"
    }

    uuid = component "Uuid" "UUID value object" "ValueObject" {
        tags "Item"
    }

    uuidFactoryInterface = component "UuidFactoryInterface" "Contract for UUID creation" "Interface" {
        tags "Item"
    }
}

# Infrastructure Layer
group "Infrastructure" {
    userRepository = component "UserRepository" "Retrieves users from MariaDB" "Repository" {
        tags "Item"
    }

    uuidFactory = component "UuidFactory" "Creates UUID value objects" "Factory" {
        tags "Item"
    }
}

# External Dependencies
database = component "Database" "MariaDB instance" "MariaDB" {
    tags "Database"
}

# API request flow
userController -> userItemProvider "delegates to"
userController -> uuidTransformer "uses to transform ID parameter"
userItemProvider -> userRepository "uses to retrieve user"

# UUID transformation
uuidTransformer -> uuidFactoryInterface "uses"
uuidFactory -> uuidFactoryInterface "implements"
uuidFactory -> uuid "creates"

# Data access
userRepository -> user "retrieves"
userRepository -> database "retrieves from"
```

## Visual Result

The generated diagram will show:

1. **Request Entry**:

   - UserController (entry point)

2. **Request Processing**:

   - Controller → UUID Transformer (transform ID)
   - Controller → Item Provider (retrieve data)
   - Item Provider → Repository (data access)

3. **UUID Transformation**:

   - Transformer → Factory Interface
   - Factory → Implements Interface
   - Factory → Creates UUID

4. **Data Access**:
   - Repository → Retrieves User
   - Repository → Reads from Database

## Alternative: State Processor Pattern

For **POST /api/users** (write operations), use state processor:

### Additional Components

```dsl
group "Application" {
    userStateProcessor = component "UserStateProcessor" "Processes user state changes" "StateProcessor" {
        tags "Item"
    }

    registerUserCommandHandler = component "RegisterUserCommandHandler" "Handles user registration" "CommandHandler" {
        tags "Item"
    }
}
```

### Additional Relationships

```dsl
# Processor uses command handler
userStateProcessor -> registerUserCommandHandler "delegates to"

# Handler creates user
registerUserCommandHandler -> user "creates"

# Handler uses repository
registerUserCommandHandler -> userRepository "uses for persistence"
```

## GraphQL Resolver Pattern

For **GraphQL user queries**, use resolver:

### Additional Components

```dsl
group "Application" {
    userResolver = component "UserResolver" "Resolves GraphQL user queries" "Resolver" {
        tags "Item"
    }
}
```

### Additional Relationships

```dsl
# Resolver uses repository
userResolver -> userRepository "uses to retrieve user"

# Resolver transforms results
userResolver -> uuidTransformer "uses to transform IDs"
```

## Verification Checklist

- [x] Controller documented
- [x] API Platform provider/processor documented
- [x] UUID transformer documented
- [x] Repository documented
- [x] Factory and interface documented
- [x] Domain entity documented
- [x] External dependencies documented
- [x] Request flow relationships clear
- [x] UUID transformation flow clear
- [x] Data access flow clear
- [x] Hexagonal architecture visible
- [x] Layer groupings correct
- [x] No DTOs included

## Common Questions

### Q: Should I document every REST endpoint?

**A**: Document controllers and their key dependencies. If multiple endpoints share the same controller and dependencies, one documentation is sufficient.

### Q: Should I include API Platform DTOs?

**A**: No. DTOs (input/output classes) are data structures. Document providers, processors, and resolvers instead.

### Q: How do I show API Platform's automatic wiring?

**A**: Show the main components (controller, provider/processor, repository). API Platform's internal wiring is framework detail, not architecture.

### Q: Should I document validators?

**A**: Only if they contain significant business logic. Simple constraint validators can be omitted.

### Q: How do I differentiate between read and write operations?

**A**: Use different relationship descriptions:

```dsl
# Read operation
itemProvider -> repository "retrieves from"

# Write operation
stateProcessor -> repository "persists via"
```

## Integration with CQRS

If using CQRS with API endpoints:

```dsl
# Write endpoint (command)
userController -> userStateProcessor "uses"
userStateProcessor -> registerUserCommandHandler "dispatches to"

# Read endpoint (query)
userController -> userItemProvider "uses"
userItemProvider -> userQueryHandler "queries via"
```

## Pagination and Filtering

For collection endpoints with filters:

```dsl
# Collection provider with filters
userCollectionProvider = component "UserCollectionProvider" "Provides paginated user collections" "CollectionProvider" {
    tags "Item"
}

# Provider uses repository with filters
userCollectionProvider -> userRepository "retrieves filtered results from"

# Repository applies filters
userRepository -> database "queries with filters"
```

## Next Steps

After documenting the API endpoint:

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

3. **Review API request flow**: Ensure clear path from controller to database

4. **Update API documentation**: Use [developing-openapi-specs](../../developing-openapi-specs/SKILL.md) skill

5. **Update documentation**: Use [documentation-sync](../../documentation-sync/SKILL.md) skill

6. **Run CI checks**: Use [ci-workflow](../../ci-workflow/SKILL.md) skill
