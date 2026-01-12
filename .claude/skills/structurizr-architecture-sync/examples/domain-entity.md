# Example: Adding Domain Entity

Complete example of documenting a new domain entity with value objects in Structurizr.

## Scenario

Implementing a new domain entity: **Order** with value objects and business logic.

### Components Implemented

**Domain Layer**:

- `Order` (aggregate root)
- `OrderId` (value object)
- `OrderStatus` (value object/enum)
- `OrderLine` (value object)
- `Money` (value object)
- `OrderCreatedEvent` (domain event)
- `OrderConfirmedEvent` (domain event)
- `OrderRepositoryInterface` (port)

**Infrastructure Layer**:

- `OrderRepository` (adapter)
- `OrderConfirmedSubscriber` (event subscriber)

## Step 1: Identify Components to Document

Use the [component identification guide](../reference/component-identification.md):

**Include**:

- ✅ Order (aggregate root with business logic)
- ✅ OrderId (unique identifier)
- ✅ OrderStatus (value object with state transitions)
- ✅ Money (value object with calculation logic)
- ✅ Domain events (state change notifications)
- ✅ Repository interface (hexagonal port)
- ✅ Repository implementation (adapter)
- ✅ Event subscribers (reactive behavior)

**Exclude**:

- ❌ OrderLine (too granular, internal detail of Order)
- ❌ OrderDTO (data structure, not component)
- ❌ OrderMapper (utility, not architectural)

## Step 2: Add Domain Layer Components

```dsl
group "Domain" {
    order = component "Order" "Order aggregate root managing order lifecycle" "Aggregate" {
        tags "Item"
    }

    orderId = component "OrderId" "Order unique identifier" "ValueObject" {
        tags "Item"
    }

    orderStatus = component "OrderStatus" "Order status with state transition logic" "ValueObject" {
        tags "Item"
    }

    money = component "Money" "Monetary value with currency and calculations" "ValueObject" {
        tags "Item"
    }

    orderCreatedEvent = component "OrderCreatedEvent" "Event published when order is created" "DomainEvent" {
        tags "Item"
    }

    orderConfirmedEvent = component "OrderConfirmedEvent" "Event published when order is confirmed" "DomainEvent" {
        tags "Item"
    }

    orderRepositoryInterface = component "OrderRepositoryInterface" "Contract for order persistence" "Interface" {
        tags "Item"
    }
}
```

## Step 3: Add Infrastructure Layer Components

```dsl
group "Infrastructure" {
    orderRepository = component "OrderRepository" "MariaDB implementation of order persistence" "Repository" {
        tags "Item"
    }

    orderConfirmedSubscriber = component "OrderConfirmedSubscriber" "Processes confirmed orders" "EventSubscriber" {
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

### Domain Model Relationships

```dsl
# Order has value objects
order -> orderId "has"
order -> orderStatus "has"
order -> money "contains total as"

# Order creates events
order -> orderCreatedEvent "creates on initialization"
order -> orderConfirmedEvent "creates on confirmation"
```

### Repository Relationships

```dsl
# Repository implements interface
orderRepository -> orderRepositoryInterface "implements"

# Repository stores order
orderRepository -> order "stores / retrieves"

# Repository persists to database
orderRepository -> database "persists to"
```

### Event Flow Relationships

```dsl
# Events trigger subscribers
orderConfirmedEvent -> orderConfirmedSubscriber "triggers"

# Subscriber sends notifications
orderConfirmedSubscriber -> messageBroker "sends confirmation via"
```

## Complete workspace.dsl Section

```dsl
# Domain Layer
group "Domain" {
    order = component "Order" "Order aggregate root managing order lifecycle" "Aggregate" {
        tags "Item"
    }

    orderId = component "OrderId" "Order unique identifier" "ValueObject" {
        tags "Item"
    }

    orderStatus = component "OrderStatus" "Order status with state transition logic" "ValueObject" {
        tags "Item"
    }

    money = component "Money" "Monetary value with currency and calculations" "ValueObject" {
        tags "Item"
    }

    orderCreatedEvent = component "OrderCreatedEvent" "Event published when order is created" "DomainEvent" {
        tags "Item"
    }

    orderConfirmedEvent = component "OrderConfirmedEvent" "Event published when order is confirmed" "DomainEvent" {
        tags "Item"
    }

    orderRepositoryInterface = component "OrderRepositoryInterface" "Contract for order persistence" "Interface" {
        tags "Item"
    }
}

# Infrastructure Layer
group "Infrastructure" {
    orderRepository = component "OrderRepository" "MariaDB implementation of order persistence" "Repository" {
        tags "Item"
    }

    orderConfirmedSubscriber = component "OrderConfirmedSubscriber" "Processes confirmed orders" "EventSubscriber" {
        tags "Item"
    }
}

# External Dependencies
database = component "Database" "MariaDB instance" "MariaDB" {
    tags "Database"
}

messageBroker = component "Message Broker" "AWS SQS for async messaging" "AWS SQS" {
    tags "Database"
}

# Domain model relationships
order -> orderId "has"
order -> orderStatus "has"
order -> money "contains total as"
order -> orderCreatedEvent "creates on initialization"
order -> orderConfirmedEvent "creates on confirmation"

# Repository relationships
orderRepository -> orderRepositoryInterface "implements"
orderRepository -> order "stores / retrieves"
orderRepository -> database "persists to"

# Event flow relationships
orderConfirmedEvent -> orderConfirmedSubscriber "triggers"
orderConfirmedSubscriber -> messageBroker "sends confirmation via"
```

## Visual Result

The generated diagram will show:

1. **Domain Layer**:

   - Order (aggregate) with value objects (OrderId, OrderStatus, Money)
   - Events (OrderCreatedEvent, OrderConfirmedEvent)
   - Repository interface (port)

2. **Infrastructure Layer**:

   - OrderRepository implementing the interface
   - OrderConfirmedSubscriber

3. **Relationships**:
   - Order → Has value objects
   - Order → Creates events
   - Repository → Implements interface
   - Repository → Stores Order
   - Repository → Persists to Database
   - Events → Trigger subscribers

## Advanced: Entity with Dependent Entities

If Order has related entities (e.g., User):

```dsl
# User relationship
order -> user "belongs to"

# Or if User is managed separately
order -> userId "references"
```

## Advanced: Value Object Validation

If value objects have complex validation:

```dsl
# Value object with validator
orderStatus = component "OrderStatus" "Order status with state transition validation" "ValueObject" {
    tags "Item"
}

orderStatusValidator = component "OrderStatusValidator" "Validates order status transitions" "Validator" {
    tags "Item"
}

orderStatus -> orderStatusValidator "validates transitions via"
```

**Note**: Only include validators if they contain significant business logic.

## Advanced: Factory Pattern

If entity creation is complex:

```dsl
# Domain factory
orderFactory = component "OrderFactory" "Creates order aggregates" "Factory" {
    tags "Item"
}

# Factory creates order
orderFactory -> order "creates"

# Handler uses factory
createOrderHandler -> orderFactory "uses"
```

## Verification Checklist

- [x] Aggregate root documented
- [x] Key value objects documented (not all - only significant ones)
- [x] Domain events documented
- [x] Repository interface (port) documented
- [x] Repository implementation (adapter) documented
- [x] Event subscribers documented
- [x] External dependencies documented
- [x] Aggregate-value object relationships clear
- [x] Event creation relationships clear
- [x] Repository implementation relationships clear
- [x] Event flow relationships clear
- [x] Internal collections (OrderLine) omitted
- [x] DTOs omitted
- [x] Mappers omitted

## Common Questions

### Q: Should I include all value objects?

**A**: Include value objects with:

- Complex validation logic
- Business calculations
- Shared across multiple aggregates

**Omit**: Simple wrappers (FirstName, LastName) or internal collections.

### Q: Should I include entity relationships?

**A**: Yes, show relationships between aggregates:

```dsl
order -> user "belongs to"
order -> product "contains"
```

### Q: How do I show aggregate boundaries?

**A**: Use the aggregate root as the main component, with value objects as children:

```dsl
order -> orderId "has"
order -> orderStatus "has"
```

This visually shows that OrderId and OrderStatus belong to Order.

### Q: Should I document invariants?

**A**: Invariants are business rules enforced by the aggregate. Mention them in the component description:

```dsl
order = component "Order" "Order aggregate root enforcing order total consistency" "Aggregate" {
    tags "Item"
}
```

### Q: How do I show state transitions?

**A**: Show events created during transitions:

```dsl
order -> orderCreatedEvent "creates on initialization"
order -> orderConfirmedEvent "creates on confirmation"
order -> orderCancelledEvent "creates on cancellation"
```

## Integration with Application Layer

Connect the domain entity to application layer:

```dsl
# Command handler creates order
createOrderHandler -> order "creates"

# Command handler uses repository interface
createOrderHandler -> orderRepositoryInterface "depends on"

# Handler publishes events
order -> orderCreatedEvent "creates"
```

## Next Steps

After documenting the domain entity:

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

3. **Review domain model**: Ensure aggregate boundaries are clear

4. **Update domain documentation**: Document the domain model in `docs/design-and-architecture.md`

5. **Update glossary**: Add domain terms to `docs/glossary.md`

6. **Use documentation-sync skill**: [documentation-sync](../../documentation-sync/SKILL.md)

7. **Run CI checks**: [ci-workflow](../../ci-workflow/SKILL.md)
