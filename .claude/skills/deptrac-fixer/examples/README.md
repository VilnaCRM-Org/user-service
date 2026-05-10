# Deptrac Fixer Examples

This directory contains practical before/after examples for fixing common Deptrac architectural violations using **pragmatic patterns** from the actual codebase.

## 🎯 Pragmatic Approach

These examples follow the **actual patterns** used in `src/Core/Customer/`:

- ✅ **Primitives by default** (string $email, string $phone)
- ✅ **YAML validation** (config/validator/), NOT annotations or VO constructors
- ✅ **Factory pattern** for entity creation (injectable factories)
- ✅ **Value Objects only when needed** (Money with operations, ULID special concept)
- ✅ **No framework dependencies in Domain** (pure PHP)

## Examples Overview

### 1. [01-domain-symfony-validation.php](01-domain-symfony-validation.php)

**Fixing Domain → Symfony validator constraint violations**

**Key Patterns:**

- ❌ BEFORE: Symfony validation attributes in domain entity
- ✅ AFTER: Primitives in entity + YAML validation in Application layer
- Shows: Custom validators (UniqueEmail, Initials), DTO validation config
- Demonstrates: Where validation belongs (config/validator/Customer.yaml)

### 2. [02-domain-doctrine-annotations.php](02-domain-doctrine-annotations.php)

**Removing Doctrine ODM annotations from domain entities**

**Key Patterns:**

- ❌ BEFORE: Doctrine annotations on entity properties
- ✅ AFTER: Pure PHP entity with primitives, XML mappings in config/doctrine/
- Shows: Factory pattern (ProductFactoryInterface), domain collections (TagCollection)
- Demonstrates: When to use Value Objects (Money with operations vs primitives)

### 3. [03-domain-api-platform.php](03-domain-api-platform.php)

**Moving API Platform configuration out of domain**

**Key Patterns:**

- ❌ BEFORE: API Platform attributes on domain entity
- ✅ AFTER Option 1: YAML configuration (recommended for simple CRUD)
- ✅ AFTER Option 2: Application DTOs (for complex transformations)
- Shows: YAML validation, primitives in entity, no annotations on DTOs
- Demonstrates: Actual Customer entity pattern from codebase

### 4. [04-infrastructure-handler.php](04-infrastructure-handler.php)

**Using bus pattern instead of direct handler calls**

**Key Patterns:**

- ❌ BEFORE: Infrastructure directly instantiating domain entities
- ✅ AFTER: Command bus + Factory pattern
- Shows: CommandBusInterface injection, CustomerFactoryInterface usage
- Demonstrates: Proper layer separation with command/handler pattern

### 5. [05-complete-entity-refactoring.php](05-complete-entity-refactoring.php)

**Full entity refactoring with all patterns combined**

**Key Patterns:**

- Complete example combining all fixes
- Shows real-world refactoring workflow
- Demonstrates all pragmatic patterns together

## How to Use These Examples

1. **Identify your violation type** from `make deptrac` output
2. **Find matching example** that addresses the violation
3. **Follow the PRAGMATIC pattern**, not "pure DDD"
4. **Apply the pattern** to your specific code
5. **Verify with** `make deptrac` after changes

## Quick Reference

| Violation Pattern          | Example | Key Solution                                  |
| -------------------------- | ------- | --------------------------------------------- |
| Domain → Symfony Validator | 01      | Primitives + YAML validation (no annotations) |
| Domain → Doctrine          | 02      | XML mappings + Factory pattern + Primitives   |
| Domain → API Platform      | 03      | YAML config + Primitives + No DTO annotations |
| Infrastructure → Handler   | 04      | CommandBus + Factory injection                |
| All Combined               | 05      | Complete pragmatic workflow                   |

## Validation Strategy (CRITICAL)

**✅ CORRECT - Three-Layer Validation:**

1. **Application Layer (DTOs)** - Format/structure validation
   - Location: `config/validator/Customer.yaml`
   - Rules: NotBlank, Email, Length, custom validators
   - NO annotations on DTO classes!

2. **Domain Layer (Entities)** - Business invariants only
   - Location: Entity business methods
   - Rules: State transitions, business rules
   - NO validation in constructors (trust DTO layer)!

3. **Custom Validators** - Business logic validation
   - Location: `src/{Context}/Application/Validator/`
   - Examples: UniqueEmail, Initials, business-specific rules

**❌ WRONG:**

```php
// Don't do this in DTOs
final class CustomerCreate {
    #[Assert\Email]  // ❌ No annotations!
    public string $email;
}

// Don't do this in Value Objects
final readonly class Email {
    public function __construct(string $value) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {  // ❌ Use YAML!
            throw new InvalidEmailException();
        }
    }
}
```

**✅ CORRECT:**

```php
// Simple DTO with no annotations
final class CustomerCreate {
    public string $email;  // ✅ Validated in YAML
}

// config/validator/Customer.yaml
App\Customer\Application\DTO\CustomerCreate:
  properties:
    email:
      - NotBlank: { message: 'not.blank' }
      - Email: { message: 'email.invalid' }
      - App\Shared\Application\Validator\UniqueEmail: ~
```

## Factory Pattern (CRITICAL)

**✅ CORRECT - Use Injectable Factories:**

```php
// Factory interface in Domain
interface CustomerFactoryInterface {
    public function create(
        string $initials,  // Primitives, not VOs
        string $email,
        string $phone
    ): Customer;
}

// Usage in Application Handler
final readonly class CreateCustomerHandler {
    public function __construct(
        private CustomerFactoryInterface $customerFactory  // ✅ Inject
    ) {}

    public function __invoke(CreateCustomerCommand $command): void {
        $customer = $this->customerFactory->create(  // ✅ Use factory
            $command->initials,
            $command->email,
            $command->phone
        );
    }
}
```

**❌ WRONG:**

```php
// Don't use 'new' in production code
$customer = new Customer($initials, $email, $phone);  // ❌

// Don't use static factory methods
$customer = Customer::create($initials, $email);  // ❌
```

**✅ Tests can use 'new' directly** - this is OK and simpler for testing.

## Value Objects Decision Guide

**✅ CREATE VALUE OBJECTS WHEN:**

1. **Domain behavior exists** - Money::add(), Money::subtract()
2. **Special domain concept** - ULID (conversion logic, special ID strategy)
3. **Complex immutable type** - Money (amount + currency must match)
4. **Type-safe enumerations** - ProductStatus, OrderStatus

**❌ DON'T CREATE VALUE OBJECTS FOR:**

1. **Simple strings** - string $email, string $phone (use YAML validation)
2. **Simple booleans** - bool $confirmed, bool $active
3. **Simple numbers** - int $quantity, float $discount
4. **Validation-only fields** - Use YAML config instead

**Decision Tree:**

```
Does the field need domain behavior (methods/operations)?
├─ YES → Consider Value Object (e.g., Money::add())
└─ NO → Is it a special domain concept?
    ├─ YES → Consider Value Object (e.g., ULID)
    └─ NO → Use primitive type (e.g., string $email)
```

## Directory Structure Guide

When moving files, consult **[CODELY-STRUCTURE.md](../CODELY-STRUCTURE.md)** for:

- Complete CodelyTV directory hierarchy
- WHERE files should go after fixing violations
- File naming conventions per layer

## Testing Your Fixes

After applying any fix:

```bash
# Verify architecture
make deptrac

# Ensure tests pass
make unit-tests

# Check for type issues
make psalm

# Verify quality metrics
make phpinsights
```

## Key Principles

1. **Default to primitives** - Only use VOs when you need behavior
2. **Validate in YAML** - No annotations, no VO validation constructors
3. **Use factories** - Injectable factories, not 'new' or static methods
4. **Follow actual codebase** - See `src/Core/Customer/` for reference
5. **Keep it simple** - Pragmatic over "pure DDD"

## Real Codebase Reference

All examples match patterns from:

- **Entity**: `src/Core/Customer/Domain/Entity/Customer.php`
- **Validation**: `config/validator/Customer.yaml`
- **Factory**: `src/Core/Customer/Domain/Factory/CustomerFactoryInterface.php`

**Remember**: These are not theoretical examples - they reflect the actual patterns used in this codebase. Follow them closely to maintain consistency.
