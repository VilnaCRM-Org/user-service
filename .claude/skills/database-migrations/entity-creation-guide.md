# Entity Creation Guide

## Complete Workflow

This guide walks through the complete process of creating a new entity in the VilnaCRM User Service using Hexagonal Architecture. Template examples reference MongoDB/Doctrine ODM—adapt steps to Doctrine ORM with MySQL (use `.orm.xml` mappings and `EntityManagerInterface`).

## Step 1: Define Entity in Domain Layer

### Location

`src/{Context}/Domain/Entity/{EntityName}.php`

Example: `src/Core/Customer/Domain/Entity/Customer.php`

### Entity Class Structure

```php
<?php

declare(strict_types=1);

namespace App\Core\Customer\Domain\Entity;

use DateTimeImmutable;

final class Customer
{
    public function __construct(
        private string $id,
        private string $name,
        private string $email,
        private string $phone,
        private string $type,
        private string $status,
        private bool $confirmed,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function updateName(string $name): void
    {
        $this->name = $name;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updateEmail(string $email): void
    {
        $this->email = $email;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function confirm(): void
    {
        $this->confirmed = true;
        $this->updatedAt = new DateTimeImmutable();
    }
}
```

### Key Principles

#### 1. No Doctrine Annotations

- Keep domain layer clean
- Use XML mappings for infrastructure concerns

#### 2. Immutable by Default

- Use `private` properties
- Return new instances for modifications when appropriate
- Update `updatedAt` timestamp when state changes

#### 3. Explicit Getters

- Provide clear, type-safe getters
- Use `is*()` for boolean properties

#### 4. Business Logic in Domain

- Encapsulate state changes in methods
- Validate business rules in entity methods

## Step 2: Create XML Mapping

### Location

`config/doctrine/{Entity}.mongodb.xml`

Example: `config/doctrine/Customer.mongodb.xml`

### Complete XML Mapping

```xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mongo-mapping
    xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                        https://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">

    <document
        name="App\Core\Customer\Domain\Entity\Customer"
        collection="customers"
        repository-class="App\Core\Customer\Infrastructure\Repository\CustomerRepository">

        <!-- Primary Key -->
        <field name="id" fieldName="id" id="true" strategy="NONE" type="ulid"/>

        <!-- Basic Fields -->
        <field name="name" fieldName="name" type="string"/>
        <field name="email" fieldName="email" type="string"/>
        <field name="phone" fieldName="phone" type="string"/>

        <!-- References (IRI format) -->
        <field name="type" fieldName="type" type="string"/>
        <field name="status" fieldName="status" type="string"/>

        <!-- Boolean Fields -->
        <field name="confirmed" fieldName="confirmed" type="boolean"/>

        <!-- Timestamps -->
        <field name="createdAt" fieldName="created_at" type="date_immutable"/>
        <field name="updatedAt" fieldName="updated_at" type="date_immutable"/>

        <!-- Indexes -->
        <indexes>
            <!-- Unique Constraint -->
            <index>
                <key name="email" order="asc"/>
                <option name="unique" value="true"/>
            </index>

            <!-- Performance Index -->
            <index>
                <key name="createdAt" order="desc"/>
            </index>

            <!-- Compound Index for Queries -->
            <index>
                <key name="status" order="asc"/>
                <key name="type" order="asc"/>
            </index>
        </indexes>
    </document>
</doctrine-mongo-mapping>
```

### Field Mapping Reference

| PHP Type            | Doctrine Type    | fieldName Convention        |
| ------------------- | ---------------- | --------------------------- |
| `string`            | `string`         | `snake_case`                |
| `int`               | `int`            | `snake_case`                |
| `float`             | `float`          | `snake_case`                |
| `bool`              | `boolean`        | `snake_case`                |
| `DateTimeImmutable` | `date_immutable` | `{field}_at` for timestamps |
| `DateTime`          | `date`           | `{field}_at` for timestamps |
| `array`             | `collection`     | `snake_case`                |

### ID Strategy Options

**`strategy="NONE"`** (Recommended):

- Generate ID in application code
- Use ULID type for time-ordered IDs
- Full control over ID generation

```xml
<field name="id" id="true" strategy="NONE" type="ulid"/>
```

**`strategy="UUID"`**:

- MongoDB generates UUID
- Less control, auto-generated

**`strategy="AUTO"`**:

- MongoDB ObjectId (not recommended for our pattern)

## Step 3: Configure API Platform Resource

### Location

`config/api_platform/resources/{resource}.yaml`

Example: `config/api_platform/resources/customer.yaml`

### Basic Configuration

```yaml
App\Core\Customer\Domain\Entity\Customer:
  shortName: Customer
  description: 'Customer resource for VilnaCRM'

  normalizationContext:
    groups: ['customer:read']

  denormalizationContext:
    groups: ['customer:write']

  operations:
    get_collection:
      class: 'ApiPlatform\Metadata\GetCollection'
      uriTemplate: '/customers'
      paginationEnabled: true
      paginationItemsPerPage: 30
      filters: ['customer.order_filter', 'customer.search_filter', 'customer.boolean_filter']

    get:
      class: 'ApiPlatform\Metadata\Get'
      uriTemplate: '/customers/{id}'

    post:
      class: 'ApiPlatform\Metadata\Post'
      uriTemplate: '/customers'
      input: 'App\Core\Customer\Application\DTO\CreateCustomerDto'
      processor: 'App\Core\Customer\Application\Processor\CreateCustomerProcessor'

    patch:
      class: 'ApiPlatform\Metadata\Patch'
      uriTemplate: '/customers/{id}'
      input: 'App\Core\Customer\Application\DTO\UpdateCustomerDto'
      processor: 'App\Core\Customer\Application\Processor\UpdateCustomerProcessor'

    delete:
      class: 'ApiPlatform\Metadata\Delete'
      uriTemplate: '/customers/{id}'
      processor: 'App\Core\Customer\Application\Processor\DeleteCustomerProcessor'
```

### With GraphQL Support

```yaml
App\Core\Customer\Domain\Entity\Customer:
  shortName: Customer

  # REST operations (as above)
  operations:
    # ... REST operations

  # GraphQL operations
  graphqlOperations:
    query:
      class: 'ApiPlatform\Metadata\GraphQl\Query'

    queryCollection:
      class: 'ApiPlatform\Metadata\GraphQl\QueryCollection'
      paginationType: 'page'

    create:
      class: 'ApiPlatform\Metadata\GraphQl\Mutation'
      name: 'createCustomer'
      resolver: 'App\Core\Customer\Application\Resolver\CreateCustomerResolver'

    update:
      class: 'ApiPlatform\Metadata\GraphQl\Mutation'
      name: 'updateCustomer'
      resolver: 'App\Core\Customer\Application\Resolver\UpdateCustomerResolver'

    delete:
      class: 'ApiPlatform\Metadata\GraphQl\DeleteMutation'
      name: 'deleteCustomer'
```

### Define Filters

In `config/services.yaml`:

```yaml
services:
  customer.order_filter:
    parent: 'api_platform.doctrine_mongodb.odm.order_filter'
    arguments:
      $properties:
        createdAt: ~
        name: ~
    tags: ['api_platform.filter']

  customer.search_filter:
    parent: 'api_platform.doctrine_mongodb.odm.search_filter'
    arguments:
      $properties:
        email: 'partial'
        name: 'partial'
    tags: ['api_platform.filter']

  customer.boolean_filter:
    parent: 'api_platform.doctrine_mongodb.odm.boolean_filter'
    arguments:
      $properties:
        confirmed: ~
    tags: ['api_platform.filter']
```

## Step 4: Register Resource Directory

Update `config/packages/api_platform.yaml`:

```yaml
api_platform:
  title: 'VilnaCRM User Service'
  version: '1.0.0'

  mapping:
    paths:
      - '%kernel.project_dir%/config/api_platform'
      - '%kernel.project_dir%/src/Core/Customer/Domain/Entity' # Add this line
      - '%kernel.project_dir%/src/Internal/Health/Domain/Entity'

  patch_formats:
    json: ['application/merge-patch+json']

  formats:
    jsonld: ['application/ld+json']
    json: ['application/json']
    html: ['text/html']

  docs_formats:
    jsonld: ['application/ld+json']
    jsonopenapi: ['application/vnd.openapi+json']
    html: ['text/html']
```

## Step 5: Generate and Apply Schema Changes

### Clear Cache

```bash
make cache-clear
```

### Validate Schema

```bash
docker compose exec php bin/console doctrine:mongodb:schema:validate
```

### Update Schema

```bash
docker compose exec php bin/console doctrine:mongodb:schema:update
```

### Or Generate Migration (Optional)

```bash
make doctrine-migrations-generate
# Edit migration file
make doctrine-migrations-migrate
```

## Step 6: Verify Entity Creation

### Check API Documentation

Visit: `https://localhost/api/docs`

Verify:

- Customer resource appears
- Operations (GET, POST, PATCH, DELETE) listed
- Schema shows all fields

### Test REST API

```bash
# Create customer
curl -X POST https://localhost/api/customers \
  -H "Content-Type: application/ld+json" \
  -d '{
    "name": "Test Customer",
    "email": "test@example.com",
    "phone": "+1-555-1234",
    "type": "/api/customer_types/01234",
    "status": "/api/customer_statuses/56789",
    "confirmed": false
  }'

# Get collection
curl https://localhost/api/customers

# Get single customer
curl https://localhost/api/customers/{id}
```

### Test GraphQL API

Visit: `https://localhost/api/graphql`

```graphql
mutation CreateCustomer {
  createCustomer(
    input: {
      name: "Test Customer"
      email: "test@example.com"
      phone: "+1-555-1234"
      type: "/api/customer_types/01234"
      status: "/api/customer_statuses/56789"
      confirmed: false
    }
  ) {
    customer {
      id
      name
      email
    }
  }
}
```

## Checklist

Before committing:

- [ ] Entity defined in Domain layer
- [ ] XML mapping created in `config/doctrine/`
- [ ] API Platform resource configured
- [ ] Resource directory registered in `api_platform.yaml`
- [ ] Filters defined if needed
- [ ] Cache cleared
- [ ] Schema validated
- [ ] Schema updated/migration applied
- [ ] API documentation verified
- [ ] REST endpoints tested
- [ ] GraphQL operations tested (if applicable)
- [ ] Integration tests written
- [ ] Documentation updated in `docs/design-and-architecture.md`

## Common Mistakes

### ❌ Using Annotations in Entity

```php
// ❌ DON'T DO THIS
#[ODM\Document(collection: 'customers')]
final class Customer { }
```

Use XML mappings instead.

### ❌ Public Properties

```php
// ❌ DON'T DO THIS
final class Customer {
    public string $name;
}
```

Use private properties with getters.

### ❌ Missing Indexes

```xml
<!-- ❌ Missing unique constraint on email -->
<document name="App\Core\Customer\Domain\Entity\Customer" collection="customers">
    <field name="email" type="string"/>
</document>
```

Always add appropriate indexes.

### ❌ Wrong Namespace in XML

```xml
<!-- ❌ Wrong namespace -->
<document name="App\Customer\Domain\Entity\Customer">
```

Ensure namespace matches PHP class.

## Next Steps

After creating the entity:

1. Create repository (see [repository-patterns.md](repository-patterns.md))
2. Write integration tests
3. Update documentation
4. Create load tests if needed
