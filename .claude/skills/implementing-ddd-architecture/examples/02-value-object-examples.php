<?php

declare(strict_types=1);

/**
 * Example: Pragmatic Value Object Usage
 *
 * IMPORTANT: This file shows when to use Value Objects and when NOT to.
 * Not every field needs to be a Value Object!
 *
 * See: ../REFERENCE.md - "When to Use Value Objects (Pragmatic Approach)"
 */

// ============================================================================
// ❌ DON'T DO THIS - Over-Engineering with Value Objects
// ============================================================================

namespace App\Customer\Domain\ValueObject;

use App\Customer\Domain\Exception\InvalidEmailException;
use App\Customer\Domain\Exception\InvalidCustomerNameException;

/**
 * ❌ BAD: Email Value Object with validation
 *
 * Why this is wrong:
 * - Email validation should be in Application layer (YAML config)
 * - No domain behavior needed (just a string)
 * - Creates unnecessary complexity
 * - Not used in actual codebase (src/Core/Customer uses string $email)
 */
final readonly class EmailBad
{
    private string $value;

    public function __construct(string $value)
    {
        // ❌ Don't validate here - use YAML validation in DTOs
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException("Invalid email: {$value}");
        }
        $this->value = strtolower(trim($value));
    }

    public function value(): string
    {
        return $this->value;
    }
}

/**
 * ❌ BAD: CustomerName Value Object
 *
 * Why this is wrong:
 * - Just a string with length validation
 * - Validation should be in YAML
 * - No domain behavior
 * - Adds maintenance burden
 */
final readonly class CustomerNameBad
{
    private const MIN_LENGTH = 2;
    private const MAX_LENGTH = 255;

    private string $value;

    public function __construct(string $value)
    {
        // ❌ Don't validate here - use YAML validation in DTOs
        $trimmed = trim($value);
        if (strlen($trimmed) < self::MIN_LENGTH || strlen($trimmed) > self::MAX_LENGTH) {
            throw new InvalidCustomerNameException("Invalid name length");
        }
        $this->value = $trimmed;
    }

    public function value(): string
    {
        return $this->value;
    }
}

// ============================================================================
// ✅ DO THIS - Pragmatic Approach (ACTUAL CODEBASE PATTERN)
// ============================================================================

/**
 * ✅ GOOD: Use primitives + YAML validation
 *
 * Location: src/Core/Customer/Domain/Entity/Customer.php
 */
namespace App\Core\Customer\Domain\Entity;

use App\Shared\Domain\ValueObject\UlidInterface;
use DateTimeImmutable;

final class Customer
{
    public function __construct(
        private string $initials,           // ✅ Primitive - validated via YAML
        private string $email,              // ✅ Primitive - validated via YAML
        private string $phone,              // ✅ Primitive - validated via YAML
        private string $leadSource,         // ✅ Primitive - just a label
        private CustomerType $type,         // Entity reference
        private CustomerStatus $status,     // Entity reference
        private ?bool $confirmed,           // ✅ Primitive - simple boolean
        private UlidInterface $ulid,        // ✅ Value Object - special domain concept
        private DateTimeImmutable $createdAt = new DateTimeImmutable(),
        private DateTimeImmutable $updatedAt = new DateTimeImmutable(),
    ) {
        // NO validation here - trust the input has been validated in DTO layer
    }

    public function update(CustomerUpdate $updateData): void
    {
        // Simple assignment - no validation needed
        $this->email = $updateData->newEmail;
        $this->phone = $updateData->newPhone;
        $this->updatedAt = new DateTimeImmutable();
    }
}

/**
 * ✅ GOOD: YAML validation in Application layer
 *
 * Location: config/validator/Customer.yaml
 */
/*
App\Core\Customer\Application\DTO\CustomerCreate:
  properties:
    initials:
      - NotBlank: { message: 'not.blank' }
      - Length:
          max: 255
      - App\Shared\Application\Validator\Initials: ~
    email:
      - NotBlank: { message: 'not.blank' }
      - Email: { message: 'email.invalid' }
      - Length:
          max: 255
      - App\Shared\Application\Validator\UniqueEmail: ~
    phone:
      - NotBlank: { message: 'not.blank' }
      - Length:
          max: 255
*/

// ============================================================================
// ✅ WHEN TO USE VALUE OBJECTS - Good Examples
// ============================================================================

/**
 * ✅ GOOD: ULID Value Object
 *
 * Justified because:
 * - Special ID strategy (not UUID, not auto-increment)
 * - Needs conversion logic between Symfony ULID and domain ULID
 * - Used across ALL entities
 * - Domain concept with specific behavior
 *
 * Location: src/Shared/Domain/ValueObject/UlidInterface.php
 */
namespace App\Shared\Domain\ValueObject;

interface UlidInterface extends \Stringable
{
    public function toString(): string;
    public function toRfc4122(): string;
    public function equals(self $other): bool;
}

/**
 * ✅ GOOD: Money Value Object
 *
 * Justified because:
 * - Has domain behavior (add, subtract, multiply)
 * - Complex type (amount + currency)
 * - Needs to ensure currency matches for operations
 * - Shared across multiple entities (Order, Invoice, Payment)
 *
 * Use this when you need operations on values!
 */
namespace App\Catalog\Domain\ValueObject;

use App\Catalog\Domain\Exception\InvalidMoneyException;

final readonly class Money
{
    private int $amountInCents;
    private string $currency;

    public function __construct(int $amountInCents, string $currency = 'USD')
    {
        // ✅ Validation here is OK - it's a business rule about money
        if ($amountInCents < 0) {
            throw new InvalidMoneyException("Money amount cannot be negative");
        }

        $this->amountInCents = $amountInCents;
        $this->currency = strtoupper($currency);
    }

    public function amountInCents(): int
    {
        return $this->amountInCents;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function amountInDollars(): float
    {
        return $this->amountInCents / 100;
    }

    public function isZero(): bool
    {
        return $this->amountInCents === 0;
    }

    public function equals(Money $other): bool
    {
        return $this->amountInCents === $other->amountInCents
            && $this->currency === $other->currency;
    }

    /**
     * ✅ Value objects with BEHAVIOR - this is the key!
     */
    public function add(Money $other): self
    {
        $this->ensureSameCurrency($other);

        return new self(
            $this->amountInCents + $other->amountInCents,
            $this->currency
        );
    }

    public function subtract(Money $other): self
    {
        $this->ensureSameCurrency($other);

        return new self(
            $this->amountInCents - $other->amountInCents,
            $this->currency
        );
    }

    public function multiplyBy(float $multiplier): self
    {
        return new self(
            (int) round($this->amountInCents * $multiplier),
            $this->currency
        );
    }

    private function ensureSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidMoneyException(
                "Cannot operate on different currencies: {$this->currency} and {$other->currency}"
            );
        }
    }

    public function __toString(): string
    {
        return sprintf("%s %.2f", $this->currency, $this->amountInDollars());
    }
}

/**
 * ✅ GOOD: Status/Enum Value Object
 *
 * Justified because:
 * - Type-safe alternative to string constants
 * - Prevents invalid states
 * - Expressive query methods (isDraft(), isPublished())
 * - Can use PHP 8.1+ enums or this pattern
 */
namespace App\Catalog\Domain\ValueObject;

use App\Catalog\Domain\Exception\InvalidProductStatusException;

final readonly class ProductStatus
{
    private const DRAFT = 'draft';
    private const PUBLISHED = 'published';
    private const ARCHIVED = 'archived';

    private const VALID_STATUSES = [
        self::DRAFT,
        self::PUBLISHED,
        self::ARCHIVED,
    ];

    private string $value;

    private function __construct(string $value)
    {
        if (!in_array($value, self::VALID_STATUSES, true)) {
            throw new InvalidProductStatusException("Invalid status: {$value}");
        }
        $this->value = $value;
    }

    /**
     * Named constructors for type safety
     */
    public static function draft(): self
    {
        return new self(self::DRAFT);
    }

    public static function published(): self
    {
        return new self(self::PUBLISHED);
    }

    public static function archived(): self
    {
        return new self(self::ARCHIVED);
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * ✅ Expressive query methods
     */
    public function isDraft(): bool
    {
        return $this->value === self::DRAFT;
    }

    public function isPublished(): bool
    {
        return $this->value === self::PUBLISHED;
    }

    public function isArchived(): bool
    {
        return $this->value === self::ARCHIVED;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(ProductStatus $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

// ============================================================================
// DECISION GUIDE: When to Use Value Objects
// ============================================================================

/**
 * ✅ CREATE VALUE OBJECTS WHEN:
 *
 * 1. DOMAIN BEHAVIOR EXISTS
 *    - Money::add(), Money::subtract(), Money::multiply()
 *    - Address::isSameCountry(), Address::formatForShipping()
 *    - DateRange::overlaps(), DateRange::duration()
 *
 * 2. SPECIAL DOMAIN CONCEPT
 *    - ULID (custom ID strategy with conversion logic)
 *    - Percentage (0-100 with business rules)
 *    - Temperature (with unit conversions)
 *
 * 3. COMPLEX IMMUTABLE TYPE
 *    - Money (amount + currency, must match for operations)
 *    - Coordinates (latitude + longitude)
 *    - Dimensions (width + height + depth)
 *
 * 4. TYPE-SAFE ENUMERATIONS
 *    - ProductStatus (draft, published, archived)
 *    - OrderStatus (pending, confirmed, shipped)
 *    - PaymentMethod (credit_card, paypal, bank_transfer)
 *
 * 5. SHARED ACROSS ENTITIES
 *    - Money used in Order, Invoice, Payment, Refund
 *    - Address used in Customer, Warehouse, Supplier
 *    - Phone used in Customer, Employee, Supplier
 *
 * ❌ DON'T CREATE VALUE OBJECTS WHEN:
 *
 * 1. SIMPLE STRINGS WITHOUT BEHAVIOR
 *    - string $email (validated in YAML)
 *    - string $phone (validated in YAML)
 *    - string $leadSource (just a label)
 *    - string $notes (free text)
 *
 * 2. SIMPLE BOOLEANS
 *    - bool $confirmed
 *    - bool $isActive
 *    - bool $deleted
 *
 * 3. SIMPLE NUMBERS WITHOUT OPERATIONS
 *    - int $quantity (no special behavior)
 *    - float $discount (just a percentage)
 *    - int $stockLevel (just a number)
 *
 * 4. VALIDATION-ONLY FIELDS
 *    - Use YAML validation in Application layer instead
 *    - Don't create Value Objects just for validation
 *
 * VALIDATION FLOW IN THIS CODEBASE:
 *
 * 1. API Request → DTO (Application Layer)
 * 2. Symfony Validator validates using YAML config
 * 3. Custom validators run (UniqueEmail, Initials)
 * 4. If valid → Transform to domain entity (primitives)
 * 5. Domain entity only enforces business invariants
 *
 * REAL CODEBASE COMPARISON:
 *
 * ❌ WRONG (Over-engineered):
 * class Customer {
 *     private EmailAddress $email;        // Unnecessary VO
 *     private PhoneNumber $phone;         // Unnecessary VO
 *     private CustomerInitials $initials; // Unnecessary VO
 *     private CreatedAt $createdAt;       // Unnecessary VO
 * }
 *
 * ✅ CORRECT (Pragmatic - actual src/Core/Customer/Domain/Entity/Customer.php):
 * class Customer {
 *     private string $email;              // Primitive - validated in YAML
 *     private string $phone;              // Primitive - validated in YAML
 *     private string $initials;           // Primitive - validated in YAML
 *     private DateTimeImmutable $createdAt; // Built-in immutable type
 *     private UlidInterface $ulid;        // VO - special domain concept
 * }
 *
 * KEY PRINCIPLES:
 *
 * ✅ Default to primitives
 * ✅ Add Value Objects only when you need behavior/operations
 * ✅ Use YAML validation for format/length checks
 * ✅ Follow the actual codebase patterns
 * ✅ Keep it simple (YAGNI - You Aren't Gonna Need It)
 *
 * ❌ Don't wrap every field in a Value Object
 * ❌ Don't create Value Objects just for validation
 * ❌ Don't add complexity without clear benefit
 * ❌ Don't ignore existing codebase patterns
 *
 * REMEMBER: The goal is maintainable, understandable code, not "pure" DDD.
 *
 * See: ../REFERENCE.md - "When to Use Value Objects (Pragmatic Approach)"
 */
