# API Platform Configuration Patterns

Detailed configuration patterns for API Platform 4 resources in this project.

## Core Architecture Pattern

```
REST Request → API Platform → Processor → DTO → Transformer → Entity → Command → Handler → Repository → Database (Doctrine ORM)
```

**Layer Responsibilities:**

- **API Platform Config** (YAML): Defines operations, input/output, routing
- **Processors** (Application): Orchestrate request handling, dispatch commands
- **DTOs** (Application): Decouple API input from domain model
- **Transformers** (Application): Convert DTOs to domain entities
- **Commands** (Application): Encapsulate write operation intent
- **Handlers** (Application): Execute business logic, call repositories
- **Entities** (Domain): Pure business logic, no framework dependencies
- **Repositories** (Infrastructure): Persist to database

---

## YAML vs PHP Attributes

This repository uses **YAML-based configuration** (not PHP attributes) to maintain clean separation:

```yaml
# config/api_platform/resources/{entity}.yaml
App\Core\{Context}\Domain\Entity\{Entity}:
  shortName: { Entity }
  operations:
    ApiPlatform\Metadata\Get: ~
    ApiPlatform\Metadata\GetCollection: ~
    ApiPlatform\Metadata\Post: ~
```

**Benefits:**

- Domain entities remain framework-agnostic (no annotations/attributes)
- API configuration is centralized and versionable
- Supports DDD/hexagonal architecture
- Easier to maintain and test
- Configuration can be generated/validated independently

**Anti-pattern (DO NOT use in this project):**

```php
// ❌ AVOID: PHP Attributes couple domain to framework
#[ApiResource]
class Customer {  }
```

---

## Operation Types

| Operation     | HTTP Method | Purpose          | Input DTO      | Processor Required    |
| ------------- | ----------- | ---------------- | -------------- | --------------------- |
| GetCollection | GET         | List resources   | None           | No (default provider) |
| Get           | GET         | Single resource  | None           | No (default provider) |
| Post          | POST        | Create resource  | {Entity}Create | Yes                   |
| Put           | PUT         | Full replacement | {Entity}Put    | Yes                   |
| Patch         | PATCH       | Partial update   | {Entity}Patch  | Yes                   |
| Delete        | DELETE      | Remove resource  | None           | No (default)          |

**Example: Full CRUD Configuration**

```yaml
App\Core\Customer\Domain\Entity\Customer:
  shortName: Customer
  operations:
    ApiPlatform\Metadata\GetCollection:
      paginationItemsPerPage: 30

    ApiPlatform\Metadata\Get: ~

    ApiPlatform\Metadata\Post:
      input: App\Core\Customer\Application\DTO\CustomerCreate
      processor: App\Core\Customer\Application\Processor\CreateCustomerProcessor

    ApiPlatform\Metadata\Put:
      input: App\Core\Customer\Application\DTO\CustomerPut
      processor: App\Core\Customer\Application\Processor\UpdateCustomerProcessor

    ApiPlatform\Metadata\Patch:
      input: App\Core\Customer\Application\DTO\CustomerPatch
      processor: App\Core\Customer\Application\Processor\PatchCustomerProcessor

    ApiPlatform\Metadata\Delete: ~
```

---

## Pagination Configuration

### Cursor-Based Pagination (Recommended)

```yaml
paginationPartial: true
paginationViaCursor:
  - { field: 'ulid', direction: 'desc' }
order: { 'ulid': 'desc' }
```

**Benefits:**

- Consistent results even with concurrent inserts
- Better performance for large datasets
- Handles deep pagination efficiently

### Offset Pagination (Alternative)

```yaml
paginationEnabled: true
paginationItemsPerPage: 30
paginationMaximumItemsPerPage: 100
```

**Use when:**

- Need page numbers for UI
- Smaller datasets
- Random access to pages required

---

## IRI Resolution in Processors

When DTOs contain references to other entities (e.g., `type`, `status`), use the IRI Converter:

### Pattern

```php
use ApiPlatform\Metadata\IriConverterInterface;

final readonly class CreateCustomerProcessor implements ProcessorInterface
{
    public function __construct(
        private IriConverterInterface $iriConverter,
        private CommandBusInterface $commandBus,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Customer
    {
        // Convert IRI string to entity
        $type = $this->iriConverter->getResourceFromIri($data->type);

        $command = new CreateCustomerCommand(
            ulid: (string) new Ulid(),
            type: $type->getUlid(),  // Extract ID from resolved entity
            // ...
        );

        return $this->commandBus->dispatch($command);
    }
}
```

### DTO with IRI Reference

```php
final readonly class CustomerCreate
{
    public function __construct(
        public ?string $initials = null,
        public ?string $email = null,
        public ?string $type = null,  // IRI: "/api/customer_types/{ulid}"
        public ?string $status = null, // IRI: "/api/customer_statuses/{ulid}"
    ) {}
}
```

### API Request Example

```json
POST /api/customers
{
  "initials": "JD",
  "email": "john@example.com",
  "type": "/api/customer_types/01HQXYZ...",
  "status": "/api/customer_statuses/01HQABC..."
}
```

---

## Custom Operations

Beyond standard CRUD, you can define custom operations:

```yaml
App\Core\Customer\Domain\Entity\Customer:
  operations:
    # Custom collection operation
    get_active:
      class: ApiPlatform\Metadata\GetCollection
      uriTemplate: '/customers/active'
      filters: ['customer.status_filter']

    # Custom item operation
    activate:
      class: ApiPlatform\Metadata\Put
      uriTemplate: '/customers/{id}/activate'
      input: false # No input DTO
      processor: App\Core\Customer\Application\Processor\ActivateCustomerProcessor
```

---

## Security Configuration

```yaml
App\Core\Customer\Domain\Entity\Customer:
  security: "is_granted('ROLE_USER')"
  operations:
    ApiPlatform\Metadata\Post:
      security: "is_granted('ROLE_ADMIN')"

    ApiPlatform\Metadata\Delete:
      security: "is_granted('ROLE_ADMIN')"
```

---

## Normalization/Denormalization Groups

Control which fields are exposed in API responses:

```yaml
App\Core\Customer\Domain\Entity\Customer:
  normalizationContext:
    groups: ['customer:read']
  denormalizationContext:
    groups: ['customer:write']
```

See `filters-and-pagination.md` for filter configuration patterns.
