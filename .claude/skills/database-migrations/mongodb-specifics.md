# MongoDB-Specific Features

## Overview

This guide captures template-era MongoDB/ODM patterns. **The User Service uses MySQL with Doctrine ORM**—adapt concepts (types, indexing, repository boundaries) to ORM syntax and MySQL capabilities when applying anything from here.

## Custom Doctrine Types

### ULID Type

**Location**: `src/Shared/Infrastructure/DoctrineType/UlidType.php`

**Purpose**: Time-ordered, globally unique identifiers for MongoDB documents

**Characteristics**:

- 26-character string representation
- Sortable by creation time
- URL-safe (no special characters)
- Globally unique
- More efficient than UUID for time-series data

**Usage in XML Mapping**:

```xml
<document name="App\Core\Customer\Domain\Entity\Customer" collection="customers">
    <field name="id" fieldName="id" id="true" strategy="NONE" type="ulid"/>
</document>
```

**In Entity**:

```php
use Symfony\Component\Uid\Ulid;

final class Customer
{
    public function __construct(
        private string $id,
        // ...
    ) {}

    public static function create(/* ... */): self
    {
        return new self(
            id: (string) Ulid::generate(),  // Generate ULID
            // ...
        );
    }
}
```

**ULID Format**:

```
01HQ5ZK3M7RXVB8F2N1JYKC9TG
│├─────────┤│├──────────┤
││         ││
│Timestamp  │Random
```

### DomainUuid Type

**Location**: `src/Shared/Infrastructure/DoctrineType/DomainUuidType.php`

**Purpose**: RFC 4122 compliant UUIDs for domain entity identifiers

**Characteristics**:

- Standard UUID format (8-4-4-4-12)
- UUID version 4 (random)
- Widely compatible
- 36-character string with hyphens

**Usage in XML Mapping**:

```xml
<field name="id" fieldName="id" id="true" strategy="NONE" type="domain_uuid"/>
```

**In Entity**:

```php
use Symfony\Component\Uid\Uuid;

final class Customer
{
    public static function create(/* ... */): self
    {
        return new self(
            id: (string) Uuid::v4(),  // Generate UUID
            // ...
        );
    }
}
```

**UUID Format**:

```
550e8400-e29b-41d4-a716-446655440000
```

### Choosing Between ULID and UUID

| Use Case                    | Type   | Reason                           |
| --------------------------- | ------ | -------------------------------- |
| MongoDB \_id field          | ULID   | Time-ordered, efficient indexing |
| Public API identifiers      | ULID   | Shorter, URL-friendly            |
| Domain entity IDs           | Either | Both work, ULID preferred        |
| Legacy system compatibility | UUID   | Standard format                  |
| Event sourcing              | ULID   | Time ordering important          |

## Indexes

### Index Types

#### Single Field Index

```xml
<indexes>
    <index>
        <key name="email" order="asc"/>
    </index>
</indexes>
```

**MongoDB Command**:

```javascript
db.customers.createIndex({ email: 1 });
```

#### Unique Index

```xml
<indexes>
    <index>
        <key name="email" order="asc"/>
        <option name="unique" value="true"/>
    </index>
</indexes>
```

**MongoDB Command**:

```javascript
db.customers.createIndex({ email: 1 }, { unique: true });
```

#### Compound Index

```xml
<indexes>
    <index>
        <key name="status" order="asc"/>
        <key name="type" order="asc"/>
        <key name="createdAt" order="desc"/>
    </index>
</indexes>
```

**MongoDB Command**:

```javascript
db.customers.createIndex({ status: 1, type: 1, created_at: -1 });
```

**Order Matters**: Compound indexes are used left-to-right. This index supports:

- ✅ `{ status: "active" }`
- ✅ `{ status: "active", type: "premium" }`
- ✅ `{ status: "active", type: "premium", createdAt: { $gte: date } }`
- ❌ `{ type: "premium" }` (skips first field)
- ❌ `{ createdAt: { $gte: date } }` (skips first two fields)

#### Text Index

```xml
<indexes>
    <index>
        <key name="name"/>
        <key name="email"/>
        <option name="type" value="text"/>
    </index>
</indexes>
```

**MongoDB Command**:

```javascript
db.customers.createIndex({ name: 'text', email: 'text' });
```

**Query Usage**:

```php
$qb = $this->repository->createQueryBuilder();
$qb->text('search term');  // Searches name and email
```

#### Sparse Index

Index only documents that have the field:

```xml
<indexes>
    <index>
        <key name="taxId" order="asc"/>
        <option name="sparse" value="true"/>
    </index>
</indexes>
```

**MongoDB Command**:

```javascript
db.customers.createIndex({ tax_id: 1 }, { sparse: true });
```

#### TTL Index (Time-To-Live)

Automatically delete documents after expiration:

```xml
<indexes>
    <index>
        <key name="expiresAt" order="asc"/>
        <option name="expireAfterSeconds" value="3600"/>
    </index>
</indexes>
```

**MongoDB Command**:

```javascript
db.sessions.createIndex({ expires_at: 1 }, { expireAfterSeconds: 3600 });
```

### Index Best Practices

**1. Index Frequently Queried Fields**:

```xml
<!-- ✅ Index fields used in WHERE clauses -->
<index><key name="status"/></index>
<index><key name="createdAt" order="desc"/></index>
```

**2. Use Compound Indexes for Multiple Field Queries**:

```xml
<!-- ✅ For queries like: status = "active" AND type = "premium" -->
<index>
    <key name="status"/>
    <key name="type"/>
</index>
```

**3. Avoid Too Many Indexes**:

- Indexes slow down writes
- Consume disk space
- Aim for 3-5 indexes per collection max

**4. Index Sort Fields**:

```xml
<!-- ✅ For queries with ORDER BY created_at DESC -->
<index>
    <key name="createdAt" order="desc"/>
</index>
```

**5. Use Unique Indexes for Constraints**:

```xml
<!-- ✅ Enforce email uniqueness at database level -->
<index>
    <key name="email" order="asc"/>
    <option name="unique" value="true"/>
</index>
```

## Embedded Documents

### Embed One (Value Object)

**Value Object**:

```php
<?php

namespace App\Core\Customer\Domain\ValueObject;

final class Address
{
    public function __construct(
        private string $street,
        private string $city,
        private string $country,
        private string $postalCode
    ) {}

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function equals(Address $other): bool
    {
        return $this->street === $other->street
            && $this->city === $other->city
            && $this->country === $other->country
            && $this->postalCode === $other->postalCode;
    }
}
```

**XML Mapping for Address.mongodb.xml**:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mongo-mapping>
    <embedded-document name="App\Core\Customer\Domain\ValueObject\Address">
        <field name="street" fieldName="street" type="string"/>
        <field name="city" fieldName="city" type="string"/>
        <field name="country" fieldName="country" type="string"/>
        <field name="postalCode" fieldName="postal_code" type="string"/>
    </embedded-document>
</doctrine-mongo-mapping>
```

**In Customer Entity**:

```php
final class Customer
{
    public function __construct(
        private string $id,
        private string $name,
        private ?Address $address = null,
        // ...
    ) {}

    public function updateAddress(Address $address): void
    {
        $this->address = $address;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }
}
```

**XML Mapping for Customer.mongodb.xml**:

```xml
<document name="App\Core\Customer\Domain\Entity\Customer" collection="customers">
    <field name="id" id="true" type="ulid"/>
    <field name="name" type="string"/>

    <embed-one field="address" target-document="App\Core\Customer\Domain\ValueObject\Address"/>

    <!-- ... -->
</document>
```

**MongoDB Structure**:

```json
{
  "_id": "01HQ5ZK3M7RXVB8F2N1JYKC9TG",
  "name": "John Doe",
  "address": {
    "street": "123 Main St",
    "city": "New York",
    "country": "US",
    "postal_code": "10001"
  }
}
```

### Embed Many (Collections)

**Value Object**:

```php
<?php

namespace App\Core\Customer\Domain\ValueObject;

final class Tag
{
    public function __construct(
        private string $name,
        private string $color
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getColor(): string
    {
        return $this->color;
    }
}
```

**XML Mapping for Tag.mongodb.xml**:

```xml
<embedded-document name="App\Core\Customer\Domain\ValueObject\Tag">
    <field name="name" type="string"/>
    <field name="color" type="string"/>
</embedded-document>
```

**In Customer Entity**:

```php
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

final class Customer
{
    /** @var Collection<int, Tag> */
    private Collection $tags;

    public function __construct(
        private string $id,
        private string $name,
        // ...
    ) {
        $this->tags = new ArrayCollection();
    }

    public function addTag(Tag $tag): void
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function removeTag(Tag $tag): void
    {
        $this->tags->removeElement($tag);
        $this->updatedAt = new \DateTimeImmutable();
    }

    /** @return Collection<int, Tag> */
    public function getTags(): Collection
    {
        return $this->tags;
    }
}
```

**XML Mapping for Customer.mongodb.xml**:

```xml
<document name="App\Core\Customer\Domain\Entity\Customer">
    <embed-many field="tags" target-document="App\Core\Customer\Domain\ValueObject\Tag"/>
</document>
```

**MongoDB Structure**:

```json
{
  "_id": "01HQ5ZK3M7RXVB8F2N1JYKC9TG",
  "name": "John Doe",
  "tags": [
    { "name": "VIP", "color": "gold" },
    { "name": "Premium", "color": "blue" }
  ]
}
```

## References

### IRI References (Recommended)

Store full API Platform IRI as string:

```php
final class Customer
{
    public function __construct(
        private string $id,
        private string $type,  // IRI: "/api/customer_types/01234"
        private string $status  // IRI: "/api/customer_statuses/56789"
    ) {}
}
```

```xml
<field name="type" fieldName="type" type="string"/>
<field name="status" fieldName="status" type="string"/>
```

**Advantages**:

- Simple string storage
- Works with API Platform out of the box
- Easy to query
- No lazy loading complexity

**Disadvantages**:

- No referential integrity
- Must manually validate references exist

### DBRef References (Not Recommended for API Platform)

```xml
<reference-one field="type" target-document="App\Core\CustomerType\Domain\Entity\CustomerType"/>
```

**Why Not Recommended**:

- API Platform expects IRI strings
- Lazy loading adds complexity
- Performance overhead
- DBRef not standard MongoDB feature

## Field Types Reference

| PHP Type            | Doctrine Type    | MongoDB BSON | Example                                  |
| ------------------- | ---------------- | ------------ | ---------------------------------------- |
| `string`            | `string`         | String       | `"text"`                                 |
| `int`               | `int`            | Int32/Int64  | `42`                                     |
| `float`             | `float`          | Double       | `3.14`                                   |
| `bool`              | `boolean`        | Boolean      | `true`                                   |
| `array`             | `collection`     | Array        | `[1, 2, 3]`                              |
| `array`             | `hash`           | Object       | `{"key": "value"}`                       |
| `DateTime`          | `date`           | Date         | `ISODate("2024-01-01T00:00:00Z")`        |
| `DateTimeImmutable` | `date_immutable` | Date         | `ISODate("2024-01-01T00:00:00Z")`        |
| `Ulid`              | `ulid`           | String       | `"01HQ5ZK3M7RXVB8F2N1JYKC9TG"`           |
| `Uuid`              | `domain_uuid`    | String       | `"550e8400-e29b-41d4-a716-446655440000"` |

## MongoDB Transactions

Doctrine ODM supports MongoDB transactions (MongoDB 4.0+):

```php
$this->documentManager->transactional(function ($dm) use ($customer, $order) {
    $dm->persist($customer);
    $dm->persist($order);
    // Commits if no exception thrown
});
```

**Note**: Transactions require replica set or sharded cluster.

## Best Practices

### 1. Use Embedded Documents for Value Objects

```php
// ✅ GOOD: Embedded address
private Address $address;  // Embedded document

// ❌ BAD: Separate collection for address
private string $addressId;  // Reference to address collection
```

### 2. Use IRI Strings for Entity References

```php
// ✅ GOOD: IRI string
private string $type = "/api/customer_types/01234";

// ❌ BAD: DBRef
#[ReferenceOne(targetDocument: CustomerType::class)]
private CustomerType $type;
```

### 3. Index Frequently Queried Fields

```xml
<index><key name="status"/></index>
<index><key name="email" order="asc"/><option name="unique" value="true"/></index>
```

### 4. Use ULID for MongoDB \_id Fields

```xml
<field name="id" fieldName="id" id="true" type="ulid"/>
```

### 5. Keep Embedded Documents Small

Embedded documents are loaded with parent - keep them lightweight.

## Next Steps

- Review [entity-creation-guide.md](entity-creation-guide.md) for entity creation workflow
- Check [repository-patterns.md](repository-patterns.md) for repository implementation
- See [reference/troubleshooting.md](reference/troubleshooting.md) for common issues
