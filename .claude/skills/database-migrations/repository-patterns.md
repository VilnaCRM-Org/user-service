# Repository Patterns

## Overview

Repositories implement the Repository Pattern as part of Hexagonal Architecture, providing abstraction between the domain layer and data persistence layer. Template examples use Doctrine ODM types; in this service, implement repositories with Doctrine ORM's `EntityManagerInterface` and SQL queries instead of `DocumentManager`.

## Repository Structure

### Step 1: Define Repository Interface (Domain Layer)

**Location**: `src/{Context}/Domain/Repository/{Entity}RepositoryInterface.php`

```php
<?php

declare(strict_types=1);

namespace App\Core\Customer\Domain\Repository;

use App\Core\Customer\Domain\Entity\Customer;

interface CustomerRepositoryInterface
{
    /**
     * Persist customer to database
     */
    public function save(Customer $customer): void;

    /**
     * Find customer by ID
     */
    public function findById(string $id): ?Customer;

    /**
     * Find customer by unique email
     */
    public function findByEmail(string $email): ?Customer;

    /**
     * Find all customers
     *
     * @return Customer[]
     */
    public function findAll(): array;

    /**
     * Remove customer from database
     */
    public function remove(Customer $customer): void;

    /**
     * Check if customer with email exists
     */
    public function existsByEmail(string $email): bool;

    /**
     * Find customers by status
     *
     * @return Customer[]
     */
    public function findByStatus(string $statusIri): array;
}
```

**Key Principles**:

- Interface in **Domain layer** (no infrastructure dependencies)
- Return domain entities, not arrays or DTOs
- Method names describe business operations
- Use type hints for IDE support and type safety

### Step 2: Implement Repository (Infrastructure Layer)

**Location**: `src/{Context}/Infrastructure/Repository/{Entity}Repository.php`

```php
<?php

declare(strict_types=1);

namespace App\Core\Customer\Infrastructure\Repository;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

final class CustomerRepository implements CustomerRepositoryInterface
{
    private DocumentRepository $repository;

    public function __construct(
        private readonly DocumentManager $documentManager
    ) {
        $this->repository = $documentManager->getRepository(Customer::class);
    }

    public function save(Customer $customer): void
    {
        $this->documentManager->persist($customer);
        $this->documentManager->flush();
    }

    public function findById(string $id): ?Customer
    {
        return $this->repository->find($id);
    }

    public function findByEmail(string $email): ?Customer
    {
        return $this->repository->findOneBy(['email' => $email]);
    }

    public function findAll(): array
    {
        return $this->repository->findAll();
    }

    public function remove(Customer $customer): void
    {
        $this->documentManager->remove($customer);
        $this->documentManager->flush();
    }

    public function existsByEmail(string $email): bool
    {
        return $this->repository->findOneBy(['email' => $email]) !== null;
    }

    public function findByStatus(string $statusIri): array
    {
        return $this->repository->findBy(['status' => $statusIri]);
    }
}
```

### Step 3: Register Repository

**In `config/services.yaml`**:

```yaml
services:
  # Concrete implementation
  App\Core\Customer\Infrastructure\Repository\CustomerRepository:
    arguments:
      $documentManager: '@doctrine_mongodb.odm.document_manager'

  # Alias interface to implementation
  App\Core\Customer\Domain\Repository\CustomerRepositoryInterface:
    alias: App\Core\Customer\Infrastructure\Repository\CustomerRepository
```

## Common Repository Methods

### Basic CRUD Operations

```php
interface CustomerRepositoryInterface
{
    // Create/Update
    public function save(Customer $customer): void;

    // Read
    public function findById(string $id): ?Customer;
    public function findAll(): array;

    // Delete
    public function remove(Customer $customer): void;
}
```

### Find Methods

```php
// Find by unique field
public function findByEmail(string $email): ?Customer;

// Find by non-unique field (returns array)
public function findByStatus(string $status): array;

// Find with criteria
public function findByMultipleCriteria(array $criteria): array;

// Find with pagination
public function findPaginated(int $page, int $limit): array;
```

### Existence Checks

```php
public function exists(string $id): bool
{
    return $this->repository->find($id) !== null;
}

public function existsByEmail(string $email): bool
{
    return $this->repository->findOneBy(['email' => $email]) !== null;
}
```

### Count Methods

```php
public function count(): int
{
    return $this->repository->createQueryBuilder()
        ->count()
        ->getQuery()
        ->execute();
}

public function countByStatus(string $status): int
{
    return $this->repository->createQueryBuilder()
        ->field('status')->equals($status)
        ->count()
        ->getQuery()
        ->execute();
}
```

## Advanced Query Patterns

### Query Builder

```php
public function findActiveCustomersCreatedAfter(\DateTimeImmutable $date): array
{
    return $this->repository->createQueryBuilder()
        ->field('confirmed')->equals(true)
        ->field('createdAt')->gte($date)
        ->sort('createdAt', 'DESC')
        ->getQuery()
        ->execute()
        ->toArray();
}
```

### Complex Queries

```php
public function findByComplexCriteria(
    ?string $status = null,
    ?bool $confirmed = null,
    ?string $searchTerm = null
): array {
    $qb = $this->repository->createQueryBuilder();

    if ($status !== null) {
        $qb->field('status')->equals($status);
    }

    if ($confirmed !== null) {
        $qb->field('confirmed')->equals($confirmed);
    }

    if ($searchTerm !== null) {
        $qb->addOr($qb->expr()->field('name')->equals(new \MongoRegex("/$searchTerm/i")));
        $qb->addOr($qb->expr()->field('email')->equals(new \MongoRegex("/$searchTerm/i")));
    }

    return $qb->sort('createdAt', 'DESC')
        ->getQuery()
        ->execute()
        ->toArray();
}
```

### Pagination

```php
public function findPaginated(int $page = 1, int $itemsPerPage = 30): array
{
    $offset = ($page - 1) * $itemsPerPage;

    return $this->repository->createQueryBuilder()
        ->limit($itemsPerPage)
        ->skip($offset)
        ->sort('createdAt', 'DESC')
        ->getQuery()
        ->execute()
        ->toArray();
}

public function getTotalCount(): int
{
    return $this->repository->createQueryBuilder()
        ->count()
        ->getQuery()
        ->execute();
}
```

## Transaction Handling

### Explicit Flush

```php
public function saveBatch(array $customers): void
{
    foreach ($customers as $customer) {
        $this->documentManager->persist($customer);
    }

    // Single flush for all entities
    $this->documentManager->flush();
}
```

### Without Auto-Flush

```php
public function saveWithoutFlush(Customer $customer): void
{
    $this->documentManager->persist($customer);
    // Caller must flush
}

public function flush(): void
{
    $this->documentManager->flush();
}
```

## Repository Best Practices

### 1. Keep Methods Focused

```php
// ✅ GOOD: Specific, clear purpose
public function findByEmail(string $email): ?Customer;
public function findByStatus(string $status): array;

// ❌ BAD: Generic, unclear
public function find(array $params): array;
```

### 2. Return Domain Entities

```php
// ✅ GOOD: Return entity
public function findById(string $id): ?Customer;

// ❌ BAD: Return array
public function findById(string $id): ?array;
```

### 3. Use Type Hints

```php
// ✅ GOOD: Explicit types
public function save(Customer $customer): void;
public function findById(string $id): ?Customer;

// ❌ BAD: No type hints
public function save($customer);
public function findById($id);
```

### 4. Handle Not Found Gracefully

```php
// ✅ GOOD: Return null for optional find
public function findById(string $id): ?Customer
{
    return $this->repository->find($id);
}

// ✅ GOOD: Throw exception for required find
public function getById(string $id): Customer
{
    $customer = $this->repository->find($id);

    if ($customer === null) {
        throw new CustomerNotFoundException("Customer with ID $id not found");
    }

    return $customer;
}
```

### 5. Avoid Business Logic in Repository

```php
// ❌ BAD: Business logic in repository
public function activateCustomer(string $id): void
{
    $customer = $this->findById($id);
    $customer->setActive(true);
    $this->save($customer);
}

// ✅ GOOD: Repository only handles persistence
public function save(Customer $customer): void
{
    $this->documentManager->persist($customer);
    $this->documentManager->flush();
}

// Business logic in service/command handler
public function handle(ActivateCustomerCommand $command): void
{
    $customer = $this->repository->getById($command->customerId);
    $customer->activate();  // Business logic in entity
    $this->repository->save($customer);
}
```

## Testing Repositories

### Unit Tests (with Mocks)

```php
<?php

namespace App\Tests\Unit\Core\Customer\Infrastructure\Repository;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Infrastructure\Repository\CustomerRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use PHPUnit\Framework\TestCase;

final class CustomerRepositoryTest extends TestCase
{
    public function testSaveCustomer(): void
    {
        $customer = $this->createMock(Customer::class);
        $dm = $this->createMock(DocumentManager::class);

        $dm->expects($this->once())
            ->method('persist')
            ->with($customer);

        $dm->expects($this->once())
            ->method('flush');

        $repository = new CustomerRepository($dm);
        $repository->save($customer);
    }
}
```

### Integration Tests (with Real Database)

```php
<?php

namespace App\Tests\Integration\Core\Customer\Infrastructure\Repository;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Tests\Integration\IntegrationTestCase;

final class CustomerRepositoryIntegrationTest extends IntegrationTestCase
{
    private CustomerRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->getContainer()->get(CustomerRepositoryInterface::class);
    }

    public function testSaveAndRetrieveCustomer(): void
    {
        $customer = new Customer(
            id: $this->faker->uuid(),
            name: $this->faker->name(),
            email: $this->faker->unique()->email(),
            phone: $this->faker->phoneNumber(),
            type: '/api/customer_types/01234',
            status: '/api/customer_statuses/56789',
            confirmed: false,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable()
        );

        $this->repository->save($customer);

        $retrieved = $this->repository->findById($customer->getId());

        $this->assertNotNull($retrieved);
        $this->assertEquals($customer->getName(), $retrieved->getName());
        $this->assertEquals($customer->getEmail(), $retrieved->getEmail());
    }

    public function testFindByEmailReturnsCustomer(): void
    {
        $email = $this->faker->unique()->email();

        $customer = new Customer(
            id: $this->faker->uuid(),
            name: $this->faker->name(),
            email: $email,
            phone: $this->faker->phoneNumber(),
            type: '/api/customer_types/01234',
            status: '/api/customer_statuses/56789',
            confirmed: false,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable()
        );

        $this->repository->save($customer);

        $retrieved = $this->repository->findByEmail($email);

        $this->assertNotNull($retrieved);
        $this->assertEquals($email, $retrieved->getEmail());
    }

    public function testRemoveCustomer(): void
    {
        $customer = new Customer(
            id: $this->faker->uuid(),
            name: $this->faker->name(),
            email: $this->faker->unique()->email(),
            phone: $this->faker->phoneNumber(),
            type: '/api/customer_types/01234',
            status: '/api/customer_statuses/56789',
            confirmed: false,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable()
        );

        $this->repository->save($customer);

        $id = $customer->getId();

        $this->repository->remove($customer);

        $retrieved = $this->repository->findById($id);

        $this->assertNull($retrieved);
    }
}
```

## Custom Repository Base Class

For shared repository functionality:

```php
<?php

namespace App\Shared\Infrastructure\Repository;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

abstract class AbstractMongoRepository
{
    protected DocumentRepository $repository;

    public function __construct(
        protected readonly DocumentManager $documentManager,
        string $entityClass
    ) {
        $this->repository = $documentManager->getRepository($entityClass);
    }

    protected function flush(): void
    {
        $this->documentManager->flush();
    }

    protected function clear(): void
    {
        $this->documentManager->clear();
    }

    public function count(): int
    {
        return $this->repository->createQueryBuilder()
            ->count()
            ->getQuery()
            ->execute();
    }
}
```

**Usage**:

```php
final class CustomerRepository extends AbstractMongoRepository implements CustomerRepositoryInterface
{
    public function __construct(DocumentManager $documentManager)
    {
        parent::__construct($documentManager, Customer::class);
    }

    public function save(Customer $customer): void
    {
        $this->documentManager->persist($customer);
        $this->flush();
    }
}
```

## Checklist

Before committing repository:

- [ ] Interface defined in Domain layer
- [ ] Implementation in Infrastructure layer
- [ ] Registered in `services.yaml`
- [ ] Methods return domain entities (not arrays)
- [ ] Type hints used for all parameters and return types
- [ ] No business logic in repository
- [ ] Unit tests written
- [ ] Integration tests written
- [ ] Documentation updated

## Next Steps

After creating repository:

1. Write unit tests for repository interface
2. Write integration tests with real database
3. Document repository methods in `docs/developer-guide.md`
4. Use repository in command handlers and services
