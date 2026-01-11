# Deptrac Fixer Reference Guide

**Complete reference for understanding and fixing all types of Deptrac architectural violations.**

## Understanding Deptrac Output

### Violation Message Structure

```
[LAYER_A] must not depend on [LAYER_B]
  [FILE_PATH]:[LINE_NUMBER]
    [VIOLATION_TYPE] [DEPENDENCY_DETAILS]
```

**Example**:

```
Domain must not depend on Doctrine
  src/Customer/Domain/Entity/Customer.php:8
    uses Doctrine\ODM\MongoDB\Mapping\Annotations as ODM
```

### Violation Types

| Type         | Description              | Example                           |
| ------------ | ------------------------ | --------------------------------- |
| `uses`       | Import statement         | `uses Symfony\Component\...`      |
| `extends`    | Class inheritance        | `extends DoctrineRepository`      |
| `implements` | Interface implementation | `implements SymfonyInterface`     |
| `instanceof` | Type checking            | `if ($x instanceof DoctrineType)` |
| `static`     | Static method call       | `DoctrineClass::method()`         |

## Complete Fix Patterns

### 1. Domain Layer Violations

#### 1.1 Domain → Symfony Validator

**Violation**:

```php
namespace App\Customer\Domain\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class Customer
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    private string $name;

    #[Assert\Email]
    private string $email;
}
```

**Complete Fix**:

```php
// Step 1: Remove Symfony from Domain - Pure entity
// src/Customer/Domain/Entity/Customer.php
namespace App\Customer\Domain\Entity;

class Customer
{
    private string $name;
    private string $email;

    public function __construct(string $name, string $email)
    {
        $this->name = $name;
        $this->email = $email;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): string
    {
        return $this->email;
    }
}

// Step 2: Add YAML validation in Application layer
// config/validator/Customer.yaml
```

```yaml
App\Core\Customer\Application\DTO\CustomerCreate:
  properties:
    name:
      - NotBlank: { message: 'not.blank' }
      - Length:
          min: 2
          max: 100
          minMessage: 'Customer name must be at least 2 characters'
          maxMessage: 'Customer name cannot exceed 100 characters'
    email:
      - NotBlank: { message: 'not.blank' }
      - Email: { message: 'email.invalid' }
      - App\Shared\Application\Validator\UniqueEmail: ~
```

```php
// Step 3: Application DTO (clean, no attributes)
// src/Core/Customer/Application/DTO/CustomerCreate.php
namespace App\Core\Customer\Application\DTO;

final class CustomerCreate
{
    public string $name;
    public string $email;
}
```

#### 1.2 Domain → Doctrine ODM Annotations

**Violation**:

```php
namespace App\Product\Domain\Entity;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ODM\Document(collection: 'products')]
class Product
{
    #[ODM\Id(type: 'ulid', strategy: 'NONE')]
    private Ulid $id;

    #[ODM\Field(type: 'string')]
    private string $name;

    #[ODM\EmbedOne(targetDocument: Money::class)]
    private Money $price;

    #[ODM\ReferenceMany(targetDocument: Category::class)]
    private Collection $categories;
}
```

**Complete Fix**:

```php
// Step 1: Clean Entity
// src/Product/Domain/Entity/Product.php
namespace App\Product\Domain\Entity;

use App\Product\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Ulid;

class Product
{
    private Ulid $id;
    private string $name;
    private Money $price;
    private array $categories;

    // Pure PHP, no Doctrine imports
}

// Step 2: Create XML Mapping
// config/doctrine/Product.mongodb.xml
```

```xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                        xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                        http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">

    <document name="App\Product\Domain\Entity\Product" collection="products">
        <field name="id" type="ulid" id="true" strategy="NONE"/>
        <field name="name" type="string"/>
        <embed-one field="price" target-document="App\Product\Domain\ValueObject\Money"/>
        <reference-many field="categories" target-document="App\Category\Domain\Entity\Category"/>
    </document>
</doctrine-mongo-mapping>
```

```php
// Step 3: If using Collections, create Domain Collection
// src/Product/Domain/Collection/CategoryCollection.php
namespace App\Product\Domain\Collection;

final class CategoryCollection
{
    private array $items = [];

    public function add(Category $category): void
    {
        $this->items[] = $category;
    }

    public function all(): array
    {
        return $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }
}
```

#### 1.3 Domain → API Platform

**Violation**:

```php
namespace App\Customer\Domain\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;

#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
    ],
    normalizationContext: ['groups' => ['customer:read']],
    denormalizationContext: ['groups' => ['customer:write']]
)]
class Customer
{
    private Ulid $id;
}
```

**Complete Fix Option 1: YAML Configuration**

```php
// Clean Entity
namespace App\Customer\Domain\Entity;

class Customer
{
    private Ulid $id;
    // Pure domain entity
}
```

```yaml
# config/packages/api_platform.yaml
api_platform:
  mapping:
    paths: ['%kernel.project_dir%/config/api_platform']

# config/api_platform/resources/Customer.yaml
resources:
  App\Customer\Domain\Entity\Customer:
    operations:
      ApiPlatform\Metadata\Get: ~
      ApiPlatform\Metadata\GetCollection: ~
      ApiPlatform\Metadata\Post: ~
    normalizationContext:
      groups: ['customer:read']
    denormalizationContext:
      groups: ['customer:write']
```

**Complete Fix Option 2: Application Layer DTO**

```php
// src/Customer/Application/DTO/CustomerResource.php
namespace App\Customer\Application\DTO;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;

#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
    ]
)]
final class CustomerResource
{
    public string $id;
    public string $email;
    public string $name;

    public static function fromEntity(Customer $customer): self
    {
        $dto = new self();
        $dto->id = $customer->id()->value();
        $dto->email = $customer->email()->value();
        $dto->name = $customer->name()->value();
        return $dto;
    }
}
```

### 2. Infrastructure Layer Violations

#### 2.1 Infrastructure → Application Handler (Direct Call)

**Violation**:

```php
namespace App\Customer\Infrastructure\Doctrine\EventListener;

use App\Customer\Application\CommandHandler\UpdateSearchIndexHandler;
use App\Customer\Application\Command\UpdateSearchIndexCommand;
use Doctrine\Persistence\Event\LifecycleEventArgs;

final class CustomerDoctrineListener
{
    public function __construct(
        private UpdateSearchIndexHandler $handler // Direct dependency!
    ) {}

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof Customer) {
            ($this->handler)(new UpdateSearchIndexCommand($entity->id()));
        }
    }
}
```

**Complete Fix**:

```php
// Option 1: Use Command Bus
namespace App\Customer\Infrastructure\Doctrine\EventListener;

use App\Customer\Application\Command\UpdateSearchIndexCommand;
use App\Customer\Domain\Entity\Customer;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;

final class CustomerDoctrineListener
{
    public function __construct(
        private CommandBusInterface $commandBus
    ) {}

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof Customer) {
            $this->commandBus->dispatch(
                new UpdateSearchIndexCommand($entity->id())
            );
        }
    }
}

// Option 2: Use Domain Events (Preferred)
// The entity already records domain events

// Domain Entity
class Customer extends AggregateRoot
{
    public static function create(Ulid $id, Email $email): self
    {
        $customer = new self($id, $email);
        $customer->record(new CustomerCreated($id, $email));
        return $customer;
    }
}

// Application Event Subscriber handles it
namespace App\Customer\Application\EventSubscriber;

use App\Customer\Domain\Event\CustomerCreated;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;

final class UpdateSearchIndexOnCustomerCreated implements DomainEventSubscriberInterface
{
    public static function subscribedTo(): array
    {
        return [CustomerCreated::class];
    }

    public function __invoke(CustomerCreated $event): void
    {
        // Update search index
    }
}

// No Doctrine listener needed!
```

### 3. Complex Refactoring Scenarios

#### 3.1 Extracting Business Logic from Handler to Domain

**Before (Handler with business logic)**:

```php
namespace App\Order\Application\CommandHandler;

class PlaceOrderHandler implements CommandHandlerInterface
{
    public function __invoke(PlaceOrderCommand $command): void
    {
        $order = $this->orderRepository->findById($command->orderId);

        // Business logic in handler - WRONG!
        if ($order->getStatus() !== 'pending') {
            throw new InvalidOrderStateException();
        }

        $total = 0;
        foreach ($order->getItems() as $item) {
            $total += $item->getPrice() * $item->getQuantity();
        }

        if ($total < 10) {
            throw new MinimumOrderNotMetException();
        }

        $order->setStatus('placed');
        $order->setPlacedAt(new \DateTimeImmutable());
        $this->orderRepository->save($order);
    }
}
```

**After (Business logic in domain)**:

```php
// Domain Entity
namespace App\Order\Domain\Entity;

class Order extends AggregateRoot
{
    public function place(): void
    {
        $this->ensureCanBePlaced();
        $this->ensureMinimumOrderMet();

        $this->status = OrderStatus::placed();
        $this->placedAt = new \DateTimeImmutable();

        $this->record(new OrderPlaced($this->id, $this->total()));
    }

    private function ensureCanBePlaced(): void
    {
        if (!$this->status->isPending()) {
            throw new InvalidOrderStateException(
                "Order can only be placed when pending"
            );
        }
    }

    private function ensureMinimumOrderMet(): void
    {
        if ($this->total()->isLessThan(Money::fromCents(1000))) {
            throw new MinimumOrderNotMetException(
                "Order total must be at least $10"
            );
        }
    }

    public function total(): Money
    {
        return $this->items->calculateTotal();
    }
}

// Clean Handler
namespace App\Order\Application\CommandHandler;

class PlaceOrderHandler implements CommandHandlerInterface
{
    public function __invoke(PlaceOrderCommand $command): void
    {
        $order = $this->orderRepository->findById($command->orderId);
        $order->place(); // Delegate to domain
        $this->orderRepository->save($order);
    }
}
```

## Debugging Complex Violations

### Multiple Violations in Same File

```bash
make deptrac 2>&1 | grep -A 2 "Customer.php"
```

Fix order:

1. Remove all framework imports first
2. Create necessary Value Objects
3. Update constructors and methods
4. Create XML mappings if needed
5. Update tests

### Circular Dependency Suspicions

If fixing one violation introduces another:

```bash
# Check all layers for the class
make deptrac 2>&1 | grep "CustomerService"
```

Solution: Usually means the class is in the wrong layer entirely.

## Automated Fix Scripts

### Quick Domain Cleanup Script

```bash
#!/bin/bash
# Find Symfony imports in Domain layer
grep -r "use Symfony" src/*/Domain/ --include="*.php"

# Find Doctrine imports in Domain layer
grep -r "use Doctrine" src/*/Domain/ --include="*.php"

# Find API Platform imports in Domain layer
grep -r "use ApiPlatform" src/*/Domain/ --include="*.php"
```

### Verify Clean Domain

```bash
#!/bin/bash
# This should return empty for pure domain
find src/*/Domain -name "*.php" -exec grep -l "use Symfony\|use Doctrine\|use ApiPlatform" {} \;
```

## Edge Cases

### When Domain Needs External Service

If domain logic truly needs an external service (rare):

```php
// Define interface in Domain
namespace App\Payment\Domain\Service;

interface PaymentGatewayInterface
{
    public function charge(Money $amount, PaymentMethod $method): PaymentResult;
}

// Implement in Infrastructure
namespace App\Payment\Infrastructure\Service;

use App\Payment\Domain\Service\PaymentGatewayInterface;
use Stripe\Stripe;

final class StripePaymentGateway implements PaymentGatewayInterface
{
    public function charge(Money $amount, PaymentMethod $method): PaymentResult
    {
        // Use Stripe SDK here
    }
}

// Handler injects interface
namespace App\Payment\Application\CommandHandler;

class ProcessPaymentHandler implements CommandHandlerInterface
{
    public function __construct(
        private PaymentGatewayInterface $gateway // Interface from domain
    ) {}
}
```

## Verification Checklist

After fixing violations:

- [ ] `make deptrac` passes with zero violations
- [ ] `make unit-tests` passes
- [ ] `make integration-tests` passes
- [ ] `make psalm` shows no new errors
- [ ] Domain classes have no framework imports
- [ ] Value Objects validate their invariants
- [ ] Doctrine mappings are in XML format
- [ ] API Platform config is in YAML or Application layer
- [ ] Handlers only orchestrate, no business logic
- [ ] Events are recorded in aggregates, handled in subscribers

## Performance Considerations

When fixing violations:

1. Value Objects should be lightweight
2. Don't over-engineer - not everything needs a VO
3. Use readonly where possible
4. Consider serialization impact for DTOs
5. XML mappings are parsed once at cache warmup

## Common Mistakes to Avoid

1. **Moving domain entity to application**: Loses business logic encapsulation
2. **Creating wrapper services**: Still violates architecture
3. **Ignoring violations**: Technical debt accumulates
4. **Over-abstracting**: Not every string needs a Value Object
5. **Forgetting tests**: Fix pattern must be tested

---

**The architecture is the foundation. Respect it, and it will serve you well.**
