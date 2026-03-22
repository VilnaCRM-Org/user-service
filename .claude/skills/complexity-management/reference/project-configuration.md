# Project-Specific Configuration

Configuration details specific to this project's PHPInsights setup.

## Excluded Sniffs

These sniffs are intentionally disabled in `phpinsights.php`:

```php
'remove' => [
    UnusedParameterSniff::class,              // Allow unused params in interfaces
    SuperfluousInterfaceNamingSniff::class,   // Allow "Interface" suffix
    SuperfluousExceptionNamingSniff::class,   // Allow "Exception" suffix
    SpaceAfterNotSniff::class,                // Symfony style preference
    ForbiddenSetterSniff::class,              // Allow setters where needed
    UseSpacingSniff::class,                   // Symfony style preference
],
```

**Do NOT** create issues for these patterns - they're explicitly allowed.

---

## Excluded Files

Some files are excluded from specific checks:

```php
ForbiddenNormalClasses::class => [
    'exclude' => [
        'src/Shared/Infrastructure/Bus/Command/InMemorySymfonyCommandBus',
        'src/Shared/Infrastructure/Bus/Event/InMemorySymfonyEventBus',
        'src/Core/Customer/Domain/Entity/Customer',
    ],
],
```

These are intentionally not marked `final` - architectural decision.

---

## Line Length Configuration

```php
LineLengthSniff::class => [
    'lineLimit' => 100,
    'ignoreComments' => true,
],
```

**Solutions for long lines**:

1. Break long method calls into multiple lines
2. Extract complex expressions into variables
3. Use named parameters (PHP 8+)
4. Refactor long argument lists into DTOs

---

## Common Patterns in This Codebase

### Command Handler Complexity

Command handlers should have low complexity:

```php
// ✅ GOOD: Low complexity (2-3)
final readonly class CreateCustomerCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private CustomerRepository $repository,
        private DomainEventPublisher $publisher
    ) {}

    public function __invoke(CreateCustomerCommand $command): void
    {
        $customer = Customer::create(
            $command->id,
            $command->name,
            $command->email
        );

        $this->repository->save($customer);
        $this->publisher->publish(...$customer->pullDomainEvents());
    }
}
```

**If complexity is high**: Business logic likely in wrong layer - move to Domain.

---

### Domain Entity Complexity

Domain entities can have higher complexity for business rules:

```php
// ✅ Acceptable: Complexity in domain logic
class Customer extends AggregateRoot
{
    public function updateSubscription(SubscriptionPlan $plan): void
    {
        // Complex validation logic is appropriate here
        $this->validateSubscriptionChange($plan);
        $this->applyDiscount($plan);
        $this->record(new SubscriptionChanged($this->id, $plan));
    }
}
```

**If too complex**: Extract to Domain Service or Value Object.

---

## Hexagonal Architecture Considerations

### Application Layer

**Keep complexity low**:

- Processors: Orchestration only (complexity 1-3)
- Transformers: Simple DTO → Entity conversion (complexity 1-2)
- Command Handlers: Minimal logic, delegate to Domain (complexity 2-4)

### Domain Layer

**Complexity is acceptable for business rules**:

- Entities: Complex business validation (complexity up to 8-10 acceptable)
- Value Objects: Validation and invariants (complexity 3-5)
- Domain Services: Complex business operations (complexity 5-8)

### Infrastructure Layer

**Keep simple**:

- Repositories: CRUD operations only (complexity 1-3)
- Event handlers: Simple side effects (complexity 1-2)

---

## Integration with Other Skills

- **quality-standards** skill: Broader quality enforcement including Psalm, Deptrac
- **ci-workflow** skill: PHPInsights runs as part of CI checks
- **testing-workflow** skill: Maintain test coverage while refactoring
- **database-migrations** skill: Ensure entity changes maintain architecture compliance
- **deptrac-fixer** skill: Ensure refactoring doesn't violate layer boundaries
