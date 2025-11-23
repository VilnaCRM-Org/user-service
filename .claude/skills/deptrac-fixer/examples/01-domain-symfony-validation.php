<?php

declare(strict_types=1);

/**
 * Example 1: Fixing Domain → Symfony Validator Constraint Violations
 *
 * VIOLATION:
 * Domain must not depend on Symfony
 *   src/Customer/Domain/Entity/Customer.php:8
 *     uses Symfony\Component\Validator\Constraints as Assert
 */

// ============================================================================
// BEFORE (WRONG) - Domain entity with Symfony validation attributes
// ============================================================================

namespace App\Customer\Domain\Entity;

use Symfony\Component\Validator\Constraints as Assert;  // VIOLATION!
use Symfony\Component\Uid\Ulid;

class CustomerBefore
{
    #[Assert\NotNull]
    private Ulid $id;

    #[Assert\Email(message: 'Invalid email format')]  // VIOLATION!
    #[Assert\NotBlank]
    private string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]  // VIOLATION!
    private string $name;

    #[Assert\PositiveOrZero]  // VIOLATION!
    private int $loyaltyPoints;
}

// ============================================================================
// AFTER (CORRECT) - Pure domain entity with Value Objects
//
// NOTE: This example uses Value Objects for educational purposes to show
// how to move validation from Symfony to the domain layer.
//
// In REAL CODE, be pragmatic! Not every field needs a Value Object.
// See: .claude/skills/implementing-ddd-architecture/REFERENCE.md
//      Section: "When to Use Value Objects (Pragmatic Approach)"
//
// The actual Customer entity in src/Core/Customer/Domain/Entity/Customer.php
// uses primitives (string $email, string $phone) because they don't need
// complex domain logic. Use Value Objects only when you have:
// - Complex validation rules
// - Domain-specific behavior (methods)
// - Shared concepts across entities
// ============================================================================

namespace App\Customer\Domain\Entity;

use App\Customer\Domain\ValueObject\CustomerId;
use App\Customer\Domain\ValueObject\Email;
use App\Customer\Domain\ValueObject\CustomerName;
use App\Customer\Domain\ValueObject\LoyaltyPoints;
use App\Shared\Domain\Aggregate\AggregateRoot;

final class Customer extends AggregateRoot
{
    private CustomerId $id;
    private Email $email;
    private CustomerName $name;
    private LoyaltyPoints $loyaltyPoints;

    public function __construct(
        CustomerId $id,
        Email $email,
        CustomerName $name,
        LoyaltyPoints $loyaltyPoints
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->name = $name;
        $this->loyaltyPoints = $loyaltyPoints;
    }

    public function id(): CustomerId
    {
        return $this->id;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function name(): CustomerName
    {
        return $this->name;
    }

    public function loyaltyPoints(): LoyaltyPoints
    {
        return $this->loyaltyPoints;
    }

    // Business methods (not setters!)
    public function changeEmail(Email $newEmail): void
    {
        $this->email = $newEmail;
        $this->record(new CustomerEmailChanged($this->id, $newEmail));
    }

    public function addLoyaltyPoints(int $points): void
    {
        $this->loyaltyPoints = $this->loyaltyPoints->add($points);
    }
}

// ============================================================================
// VALUE OBJECTS - Self-validating, immutable
// ============================================================================

namespace App\Customer\Domain\ValueObject;

use App\Customer\Domain\Exception\InvalidEmailException;

/**
 * Email Value Object - validates itself
 */
final readonly class Email
{
    public function __construct(private string $value)
    {
        $this->ensureIsValid($value);
    }

    private function ensureIsValid(string $value): void
    {
        if ($value === '') {
            throw new InvalidEmailException('Email cannot be empty');
        }

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException("Invalid email format: {$value}");
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function domain(): string
    {
        return explode('@', $this->value)[1];
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

namespace App\Customer\Domain\ValueObject;

use App\Customer\Domain\Exception\InvalidCustomerNameException;

/**
 * CustomerName Value Object - validates length and format
 */
final readonly class CustomerName
{
    private const MIN_LENGTH = 2;
    private const MAX_LENGTH = 100;

    public function __construct(private string $value)
    {
        $this->ensureIsValid($value);
    }

    private function ensureIsValid(string $value): void
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new InvalidCustomerNameException('Customer name cannot be empty');
        }

        $length = mb_strlen($trimmed);

        if ($length < self::MIN_LENGTH) {
            throw new InvalidCustomerNameException(
                sprintf('Customer name must be at least %d characters, got %d', self::MIN_LENGTH, $length)
            );
        }

        if ($length > self::MAX_LENGTH) {
            throw new InvalidCustomerNameException(
                sprintf('Customer name cannot exceed %d characters, got %d', self::MAX_LENGTH, $length)
            );
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}

namespace App\Customer\Domain\ValueObject;

use App\Customer\Domain\Exception\InvalidLoyaltyPointsException;

/**
 * LoyaltyPoints Value Object - ensures non-negative value
 */
final readonly class LoyaltyPoints
{
    public function __construct(private int $value)
    {
        $this->ensureIsValid($value);
    }

    private function ensureIsValid(int $value): void
    {
        if ($value < 0) {
            throw new InvalidLoyaltyPointsException(
                "Loyalty points cannot be negative, got: {$value}"
            );
        }
    }

    public function value(): int
    {
        return $this->value;
    }

    public function add(int $points): self
    {
        if ($points < 0) {
            throw new InvalidLoyaltyPointsException('Cannot add negative points');
        }

        return new self($this->value + $points);
    }

    public function subtract(int $points): self
    {
        $newValue = $this->value - $points;

        if ($newValue < 0) {
            throw new InvalidLoyaltyPointsException('Insufficient loyalty points');
        }

        return new self($newValue);
    }

    public function isGreaterThan(int $threshold): bool
    {
        return $this->value > $threshold;
    }
}

// ============================================================================
// DOMAIN EXCEPTIONS - Specific, meaningful errors
// ============================================================================

namespace App\Customer\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

final class InvalidEmailException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}

final class InvalidCustomerNameException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}

final class InvalidLoyaltyPointsException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}

// ============================================================================
// DOMAIN FACTORY - Encapsulates entity creation (RECOMMENDED)
// ============================================================================

namespace App\Customer\Domain\Factory;

use App\Customer\Domain\Entity\Customer;
use App\Customer\Domain\ValueObject\CustomerId;
use App\Customer\Domain\ValueObject\Email;
use App\Customer\Domain\ValueObject\CustomerName;
use App\Customer\Domain\ValueObject\LoyaltyPoints;

interface CustomerFactoryInterface
{
    public function create(
        CustomerId $id,
        Email $email,
        CustomerName $name,
        LoyaltyPoints $loyaltyPoints
    ): Customer;
}

final readonly class CustomerFactory implements CustomerFactoryInterface
{
    public function create(
        CustomerId $id,
        Email $email,
        CustomerName $name,
        LoyaltyPoints $loyaltyPoints
    ): Customer {
        // Encapsulates the 'new' keyword - single point of change
        return new Customer($id, $email, $name, $loyaltyPoints);
    }
}

// ============================================================================
// USAGE IN COMMAND HANDLER (WITH FACTORY PATTERN)
// ============================================================================

namespace App\Customer\Application\CommandHandler;

use App\Customer\Application\Command\CreateCustomerCommand;
use App\Customer\Domain\Factory\CustomerFactoryInterface;
use App\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Customer\Domain\ValueObject\CustomerId;
use App\Customer\Domain\ValueObject\Email;
use App\Customer\Domain\ValueObject\CustomerName;
use App\Customer\Domain\ValueObject\LoyaltyPoints;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;

final readonly class CreateCustomerHandler implements CommandHandlerInterface
{
    public function __construct(
        private CustomerRepositoryInterface $repository,
        private CustomerFactoryInterface $customerFactory  // ✅ Inject factory
    ) {
    }

    public function __invoke(CreateCustomerCommand $command): void
    {
        // ✅ Use factory instead of 'new' - better encapsulation and testability
        $customer = $this->customerFactory->create(
            new CustomerId($command->id),
            new Email($command->email),             // Validates email format
            new CustomerName($command->name),       // Validates length
            new LoyaltyPoints($command->loyaltyPoints) // Validates non-negative
        );

        $this->repository->save($customer);
    }
}

/**
 * WHY USE FACTORIES?
 *
 * ✅ Single Responsibility: Factory encapsulates object creation logic
 * ✅ Testability: Easy to mock factories in tests
 * ✅ Flexibility: Can change construction logic without changing handlers
 * ✅ DDD Pattern: Separates creation concerns from business logic
 *
 * NOTE: Using 'new' directly in tests is acceptable for simplicity.
 * In production code, prefer factories for complex domain objects.
 */

// ============================================================================
// ACTUAL CODEBASE PATTERN: YAML Validation on Application DTOs
// ============================================================================

namespace App\Customer\Application\DTO;

/**
 * Application layer DTO - simple properties, NO validation annotations
 * Validation is configured in YAML: config/validator/Customer.yaml
 *
 * This is the REAL pattern used in this codebase!
 */
final class CustomerCreate
{
    public string $initials;
    public string $email;
    public string $phone;
    public string $leadSource;
    public string $type;      // IRI reference
    public string $status;    // IRI reference
    public bool $confirmed;
}

/**
 * Validation configuration (ACTUAL CODEBASE PATTERN)
 * File: config/validator/Customer.yaml
 *
 * App\Core\Customer\Application\DTO\CustomerCreate:
 *   properties:
 *     initials:
 *       - NotBlank: { message: 'not.blank' }
 *       - Length:
 *           max: 255
 *       - App\Shared\Application\Validator\Initials: ~
 *     email:
 *       - NotBlank: { message: 'not.blank' }
 *       - Email: { message: 'email.invalid' }
 *       - Length:
 *           max: 255
 *       - App\Shared\Application\Validator\UniqueEmail: ~
 *     phone:
 *       - NotBlank: { message: 'not.blank' }
 *       - Length:
 *           max: 255
 *     leadSource:
 *       - NotBlank: { message: 'not.blank' }
 *       - Length:
 *           max: 255
 *     type:
 *       - NotBlank: { message: 'not.blank' }
 *     status:
 *       - NotBlank: { message: 'not.blank' }
 *     confirmed:
 *       - Type: { type: 'bool', message: 'This value should be a boolean.' }
 */

/**
 * Custom Validator (when you need business logic validation)
 */
namespace App\Shared\Application\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
final class UniqueEmail extends Constraint
{
    public string $message = 'email.already.exists';
}

/**
 * Custom Validator Implementation
 */
namespace App\Shared\Application\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;

final class UniqueEmailValidator extends ConstraintValidator
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository
    ) {}

    public function validate($value, Constraint $constraint): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        if ($this->customerRepository->emailExists($value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}

// ============================================================================
// PRAGMATIC VALUE OBJECT DECISION GUIDE
// ============================================================================

/**
 * VALIDATION STRATEGY IN THIS CODEBASE
 *
 * ✅ WHAT WE DO:
 * 1. Use YAML configuration for ALL validation (config/validator/)
 * 2. Validate at Application boundary (DTOs)
 * 3. Use custom validators for business rules (UniqueEmail, Initials)
 * 4. Keep domain entities simple with primitives
 * 5. Only enforce business invariants in domain methods
 *
 * ❌ WHAT WE DON'T DO:
 * 1. Use annotations on DTOs (#[Assert\Email])
 * 2. Validate in Value Object constructors
 * 3. Validate format/structure in domain entities
 * 4. Create Value Objects for every field
 *
 * WHEN TO USE VALUE OBJECTS:
 *
 * ✅ USE VALUE OBJECTS WHEN:
 * 1. Special domain concept (ULID, Money with operations)
 * 2. Domain-specific behavior needed (Money::add(), Address::isSameCountry())
 * 3. Complex immutable concept shared across entities
 * 4. Need operations/methods on the value
 *
 * ✅ USE PRIMITIVES WHEN:
 * 1. Simple string fields (string $leadSource, string $email)
 * 2. Boolean flags (bool $confirmed)
 * 3. Numeric fields without operations (int $quantity)
 * 4. Validation happens in DTO layer (YAML config)
 * 5. No domain behavior needed
 *
 * REAL CODEBASE EXAMPLE:
 * src/Core/Customer/Domain/Entity/Customer.php uses:
 * - string $email (primitive - validated via YAML in DTO)
 * - string $phone (primitive - validated via YAML in DTO)
 * - string $leadSource (primitive - just a label)
 * - string $initials (primitive - custom validator in YAML)
 * - UlidInterface $ulid (Value Object - special domain concept)
 *
 * VALIDATION FLOW:
 * 1. API receives request → DTO (CustomerCreate)
 * 2. Symfony Validator validates using YAML config
 * 3. Custom validators run (UniqueEmail, Initials)
 * 4. If valid → Transform to domain entity (primitives)
 * 5. Domain entity only enforces business invariants
 *
 * REMEMBER: Be pragmatic!
 * - Default to primitives + YAML validation
 * - Add Value Objects only when you need behavior/operations
 * - Don't wrap every field in a Value Object
 * - Follow the actual codebase patterns
 *
 * See: .claude/skills/implementing-ddd-architecture/REFERENCE.md
 *      Section: "When to Use Value Objects (Pragmatic Approach)"
 *      Section: "Validation Strategy"
 */
