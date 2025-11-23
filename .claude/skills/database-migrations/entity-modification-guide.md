# Entity Modification Guide

## Overview

This guide covers modifying existing entities safely while maintaining backward compatibility and data integrity. Examples originate from the MongoDB template—adapt steps to Doctrine ORM/MySQL (generate migrations, update `.orm.xml`, and run `doctrine:schema:validate`).

## Adding New Fields

### Step 1: Update Entity Class

Add the new field with appropriate type and default value:

```php
<?php

declare(strict_types=1);

namespace App\Core\Customer\Domain\Entity;

final class Customer
{
    // ... existing fields

    private ?string $company = null;  // New field, nullable for existing data
    private ?string $taxId = null;

    public function __construct(
        string $id,
        string $name,
        string $email,
        // ... existing parameters
        ?string $company = null,        // Add to constructor as optional
        ?string $taxId = null
    ) {
        // ... existing assignments
        $this->company = $company;
        $this->taxId = $taxId;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function getTaxId(): ?string
    {
        return $this->taxId;
    }

    public function updateCompany(?string $company): void
    {
        $this->company = $company;
        $this->updatedAt = new \DateTimeImmutable();
    }
}
```

**Key Points**:

- New fields should be nullable (`?type`) to support existing documents
- Add to constructor as optional parameters
- Provide getters and update methods
- Update `updatedAt` timestamp in modifiers

### Step 2: Update XML Mapping

Add field definitions to `config/doctrine/{Entity}.mongodb.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mongo-mapping>
    <document name="App\Core\Customer\Domain\Entity\Customer" collection="customers">
        <!-- Existing fields -->
        <field name="id" id="true" type="ulid"/>
        <field name="name" type="string"/>
        <field name="email" type="string"/>

        <!-- NEW FIELDS -->
        <field name="company" fieldName="company" type="string" nullable="true"/>
        <field name="taxId" fieldName="tax_id" type="string" nullable="true"/>

        <!-- Existing indexes -->
        <indexes>
            <index>
                <key name="email" order="asc"/>
                <option name="unique" value="true"/>
            </index>

            <!-- NEW INDEX (optional) -->
            <index>
                <key name="company" order="asc"/>
            </index>
        </indexes>
    </document>
</doctrine-mongo-mapping>
```

### Step 3: Update API Platform Configuration

Add fields to serialization groups in `config/api_platform/resources/customer.yaml`:

```yaml
App\Core\Customer\Domain\Entity\Customer:
  shortName: Customer

  properties:
    id:
      identifier: true
    name:
      readable: true
      writable: true
    email:
      readable: true
      writable: true
    company: # NEW
      readable: true
      writable: true
    taxId: # NEW
      readable: true
      writable: true
```

### Step 4: Update DTOs and Processors

Update application layer if using DTOs:

**CreateCustomerDto.php**:

```php
final readonly class CreateCustomerDto
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $company = null,  // NEW
        public ?string $taxId = null,    // NEW
        // ... existing fields
    ) {}
}
```

**UpdateCustomerDto.php**:

```php
final readonly class UpdateCustomerDto
{
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public ?string $company = null,  // NEW
        public ?string $taxId = null,    // NEW
        // ... existing fields
    ) {}
}
```

### Step 5: Clear Cache and Update Schema

```bash
# Clear cache
make cache-clear

# Validate schema
docker compose exec php bin/console doctrine:mongodb:schema:validate

# Update schema (MongoDB will add fields as needed)
docker compose exec php bin/console doctrine:mongodb:schema:update
```

## Modifying Existing Fields

### Changing Field Type

**⚠️ WARNING**: Type changes can break existing data. Requires data migration.

#### Safe Type Changes

```php
// ✅ SAFE: string to nullable string
private string $phone;       // Before
private ?string $phone;      // After

// ✅ SAFE: Adding default value
private string $status;                    // Before
private string $status = 'pending';       // After (but constructor param must remain required)
```

#### Unsafe Type Changes (Require Migration)

```php
// ❌ UNSAFE: string to int
private string $age;    // Before
private int $age;       // After - REQUIRES DATA MIGRATION

// ❌ UNSAFE: non-nullable to nullable with different semantics
private bool $active;    // Before
private ?bool $active;   // After - existing `false` values may cause issues
```

#### Migration Strategy for Type Changes

**1. Add new field alongside old field**:

```php
private string $oldField;
private int $newField;
```

**2. Create data migration script**:

```bash
docker compose exec php bin/console app:migrate:customer-field
```

**3. Migration script example**:

```php
<?php

namespace App\Core\Customer\Command;

use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class MigrateCustomerFieldCommand extends Command
{
    protected static $defaultName = 'app:migrate:customer-field';

    public function __construct(
        private readonly CustomerRepositoryInterface $repository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $customers = $this->repository->findAll();

        foreach ($customers as $customer) {
            // Convert old field to new field
            $customer->setNewField((int) $customer->getOldField());
            $this->repository->save($customer);
        }

        $output->writeln('Migration complete!');
        return Command::SUCCESS;
    }
}
```

**4. Remove old field after verification**

### Renaming Fields

**Approach 1: Add new field, migrate, remove old**:

```xml
<!-- Step 1: Add new field -->
<field name="email" type="string"/>
<field name="emailAddress" type="string" nullable="true"/>  <!-- NEW -->

<!-- Step 2: Migrate data using command -->

<!-- Step 3: Make new field required, remove old -->
<field name="emailAddress" type="string"/>
```

**Approach 2: Change fieldName in XML** (MongoDB only):

```xml
<!-- Before -->
<field name="email" fieldName="email" type="string"/>

<!-- After -->
<field name="email" fieldName="email_address" type="string"/>
```

Then run manual MongoDB update:

```javascript
db.customers.updateMany({}, { $rename: { email: 'email_address' } });
```

## Adding Indexes

### Non-Unique Index

Safe to add anytime:

```xml
<indexes>
    <index>
        <key name="company" order="asc"/>
    </index>
</indexes>
```

```bash
docker compose exec php bin/console doctrine:mongodb:schema:update
```

### Unique Index

**⚠️ WARNING**: Can fail if duplicate data exists.

**Step 1: Check for duplicates**:

```javascript
// MongoDB shell
db.customers.aggregate([
  { $group: { _id: '$email', count: { $sum: 1 } } },
  { $match: { count: { $gt: 1 } } },
]);
```

**Step 2: Clean up duplicates if needed**

**Step 3: Add unique index**:

```xml
<indexes>
    <index>
        <key name="email" order="asc"/>
        <option name="unique" value="true"/>
    </index>
</indexes>
```

```bash
docker compose exec php bin/console doctrine:mongodb:schema:update
```

## Removing Fields

### Soft Removal (Recommended)

**Step 1: Mark field as deprecated**:

```php
/**
 * @deprecated Will be removed in v2.0. Use getNewField() instead.
 */
public function getOldField(): string
{
    return $this->oldField;
}
```

**Step 2: Stop writing to field in new code**

**Step 3: After deprecation period, remove from XML mapping**:

```xml
<!-- Remove field mapping -->
<!-- <field name="oldField" type="string"/> -->
```

**Step 4: Remove from entity class in next major version**

### Hard Removal

**⚠️ BREAKING CHANGE**: Only for major version bumps.

```php
// Remove from entity
// private string $oldField;

// Remove getter/setter methods
```

```xml
<!-- Remove from XML mapping -->
```

**Optional**: Clean up database:

```javascript
// MongoDB shell
db.customers.updateMany({}, { $unset: { old_field: '' } });
```

## Adding Relationships

### Reference to Another Entity (IRI)

```php
final class Customer
{
    // ... existing fields

    private ?string $assignedTo = null;  // IRI to User entity

    public function assignTo(string $userIri): void
    {
        $this->assignedTo = $userIri;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getAssignedTo(): ?string
    {
        return $this->assignedTo;
    }
}
```

```xml
<field name="assignedTo" fieldName="assigned_to" type="string" nullable="true"/>
```

### Embedded Document

```php
final class Address
{
    public function __construct(
        private string $street,
        private string $city,
        private string $country,
        private string $postalCode
    ) {}

    // Getters...
}

final class Customer
{
    private ?Address $address = null;

    public function updateAddress(Address $address): void
    {
        $this->address = $address;
        $this->updatedAt = new \DateTimeImmutable();
    }
}
```

```xml
<embed-one field="address" target-document="App\Core\Customer\Domain\ValueObject\Address">
    <discriminator-field name="type"/>
</embed-one>
```

## Testing Field Changes

### Unit Tests

```php
public function testNewFieldCanBeSet(): void
{
    $customer = new Customer(
        id: 'test-id',
        name: 'Test',
        email: 'test@example.com',
        company: 'Test Company'  // NEW
    );

    $this->assertSame('Test Company', $customer->getCompany());
}

public function testExistingCustomerWithoutNewFieldDoesNotBreak(): void
{
    $customer = new Customer(
        id: 'test-id',
        name: 'Test',
        email: 'test@example.com'
        // company omitted - tests backward compatibility
    );

    $this->assertNull($customer->getCompany());
}
```

### Integration Tests

```php
public function testNewFieldPersistedToDatabase(): void
{
    $customer = new Customer(
        id: $this->faker->uuid(),
        name: $this->faker->name(),
        email: $this->faker->email(),
        company: 'Test Company'
    );

    $this->repository->save($customer);

    $retrieved = $this->repository->findById($customer->getId());

    $this->assertNotNull($retrieved);
    $this->assertSame('Test Company', $retrieved->getCompany());
}
```

## Backward Compatibility Checklist

When modifying entities:

- [ ] New fields are nullable or have defaults
- [ ] Constructor parameters for new fields are optional
- [ ] Old code continues to work without new fields
- [ ] API remains backward compatible
- [ ] Database handles missing fields gracefully
- [ ] Tests verify both old and new behavior
- [ ] Data migration plan exists for breaking changes
- [ ] Documentation updated
- [ ] Changelog entry added

## Common Pitfalls

### ❌ Making New Field Required

```php
// ❌ BREAKS EXISTING DATA
public function __construct(
    string $id,
    string $name,
    string $company  // Required, but old data doesn't have it!
) {}
```

### ❌ Changing Type Without Migration

```php
// ❌ BREAKS EXISTING DATA
private string $age = '25';  // Before
private int $age = 25;       // After - string data can't be cast to int
```

### ❌ Adding Unique Constraint Without Checking Duplicates

```xml
<!-- ❌ WILL FAIL if duplicates exist -->
<index>
    <key name="phone" order="asc"/>
    <option name="unique" value="true"/>
</index>
```

### ❌ Removing Field Without Deprecation Period

```php
// ❌ BREAKING CHANGE
// Removed: public function getOldField(): string
```

## Next Steps

After modifying entity:

1. Run all tests: `make all-tests`
2. Run quality checks: `make ci`
3. Update API documentation: `make generate-openapi-spec`
4. Update architecture docs: `docs/design-and-architecture.md`
5. Add changelog entry: `docs/release-notes.md`
