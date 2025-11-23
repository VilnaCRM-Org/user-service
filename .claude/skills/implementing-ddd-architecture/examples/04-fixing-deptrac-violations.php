<?php

declare(strict_types=1);

/**
 * Example: Fixing Common Deptrac Violations (PRAGMATIC APPROACH)
 *
 * This file shows BEFORE and AFTER code for common architectural violations
 * using the ACTUAL patterns from this codebase.
 *
 * IMPORTANT:
 * - We use YAML validation, NOT annotations
 * - We use primitives, NOT Value Objects (unless justified)
 * - We validate at Application boundary, NOT in domain
 * - We use factories, NOT static methods or direct 'new'
 *
 * REMEMBER: NEVER change deptrac.yaml to bypass violations!
 * Always fix the code to respect architectural boundaries.
 */

// ============================================================================
// VIOLATION 1: Domain Entity Depending on Symfony Validator
// ============================================================================

/*
 * VIOLATION MESSAGE:
 * Domain must not depend on Symfony
 * src/Customer/Domain/Entity/Customer.php:15
 *   uses Symfony\Component\Validator\Constraints as Assert
 */

// ❌ WRONG - Domain depending on Symfony framework
namespace App\Customer\Domain\Entity;

use Symfony\Component\Validator\Constraints as Assert; // Framework in Domain!

class CustomerWrong
{
    #[Assert\Email] // Symfony validation in domain entity
    private string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    private string $name;
}

// ✅ CORRECT - Pragmatic approach (ACTUAL CODEBASE PATTERN)
/**
 * Domain entity uses PRIMITIVES, no validation
 *
 * Location: src/Core/Customer/Domain/Entity/Customer.php
 */
namespace App\Core\Customer\Domain\Entity;

use App\Shared\Domain\ValueObject\UlidInterface;
use DateTimeImmutable;

final class Customer
{
    public function __construct(
        private string $initials,           // ✅ Primitive - validated in YAML
        private string $email,              // ✅ Primitive - validated in YAML
        private string $phone,              // ✅ Primitive - validated in YAML
        private string $leadSource,         // ✅ Primitive - just a label
        private CustomerType $type,         // Entity reference
        private CustomerStatus $status,     // Entity reference
        private ?bool $confirmed,           // ✅ Primitive - simple boolean
        private UlidInterface $ulid,        // ✅ VO - special domain concept
        private DateTimeImmutable $createdAt = new DateTimeImmutable(),
        private DateTimeImmutable $updatedAt = new DateTimeImmutable(),
    ) {
        // NO validation here - trust input was validated in DTO layer
    }

    public function update(CustomerUpdate $updateData): void
    {
        // Simple assignment - no validation
        $this->email = $updateData->newEmail;
        $this->phone = $updateData->newPhone;
        $this->updatedAt = new DateTimeImmutable();
    }
}

/**
 * Validation happens in Application layer using YAML
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

/**
 * DTO has simple properties, NO annotations
 *
 * Location: src/Core/Customer/Application/DTO/CustomerCreate.php
 */
namespace App\Core\Customer\Application\DTO;

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

// ============================================================================
// VIOLATION 2: Domain Entity with Doctrine Annotations
// ============================================================================

/*
 * VIOLATION MESSAGE:
 * Domain must not depend on Doctrine
 * src/Product/Domain/Entity/Product.php:10
 *   uses Doctrine\ODM\MongoDB\Mapping\Annotations as ODM
 */

// ❌ WRONG - Doctrine annotations in Domain entity
namespace App\Product\Domain\Entity;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM; // Doctrine in Domain!

#[ODM\Document(collection: 'products')] // Persistence concern in domain
class ProductWrong
{
    #[ODM\Id(type: 'ulid', strategy: 'NONE')]
    private Ulid $id;

    #[ODM\Field(type: 'string')]
    private string $name;

    #[ODM\Field(type: 'int')]
    private int $priceInCents;
}

// ✅ CORRECT - Pure domain entity with XML mappings
namespace App\Product\Domain\Entity;

// NO Doctrine imports - pure PHP!
class Product extends AggregateRoot
{
    private Ulid $id;
    private string $name;
    private int $priceInCents;

    // Pure business logic, no persistence concerns
    public function changePrice(int $newPriceInCents): void
    {
        if ($newPriceInCents < 0) {
            throw new InvalidPriceException("Price cannot be negative");
        }
        $this->priceInCents = $newPriceInCents;
        $this->record(new ProductPriceChanged($this->id, $newPriceInCents));
    }
}

/*
 * Doctrine mapping in config/doctrine/Product.mongodb.xml:
 *
 * <?xml version="1.0" encoding="UTF-8"?>
 * <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
 *                          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
 *                          xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
 *                          http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">
 *     <document name="App\Product\Domain\Entity\Product" collection="products">
 *         <field name="id" type="ulid" id="true" strategy="NONE"/>
 *         <field name="name" type="string"/>
 *         <field name="priceInCents" type="int"/>
 *     </document>
 * </doctrine-mongo-mapping>
 */

// ============================================================================
// VIOLATION 3: Domain Entity with API Platform Attributes
// ============================================================================

/*
 * VIOLATION MESSAGE:
 * Domain must not depend on ApiPlatform
 * src/Customer/Domain/Entity/Customer.php:8
 *   uses ApiPlatform\Metadata\ApiResource
 */

// ❌ WRONG - API Platform in Domain entity
namespace App\Customer\Domain\Entity;

use ApiPlatform\Metadata\ApiResource; // API concern in Domain!
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;

#[ApiResource( // HTTP/API concern in domain
    operations: [
        new Get(),
        new Post()
    ]
)]
class CustomerWrongApi
{
    private Ulid $id;
    private string $name;
}

// ✅ CORRECT - Configure API Platform in YAML (ACTUAL PATTERN)

// Pure domain entity - NO API Platform imports
namespace App\Customer\Domain\Entity;

class Customer extends AggregateRoot
{
    private Ulid $id;
    private string $name;

    public function changeName(string $newName): void
    {
        // Business invariants only - no format validation
        if (empty(trim($newName))) {
            throw new InvalidCustomerNameException();
        }
        $this->name = $newName;
    }
}

// API Platform config in config/api_platform/resources/Customer.yaml
/*
resources:
  App\Core\Customer\Domain\Entity\Customer:
    shortName: Customer
    description: Customer resource

    operations:
      ApiPlatform\Metadata\Get:
        uriTemplate: /customers/{id}
        requirements:
          id: .+

      ApiPlatform\Metadata\GetCollection:
        uriTemplate: /customers
        paginationItemsPerPage: 30

      ApiPlatform\Metadata\Post:
        uriTemplate: /customers
        input: App\Core\Customer\Application\DTO\CustomerCreate
        processor: App\Core\Customer\Application\Processor\CreateCustomerProcessor

      ApiPlatform\Metadata\Put:
        uriTemplate: /customers/{id}
        input: App\Core\Customer\Application\DTO\CustomerPut
        processor: App\Core\Customer\Application\Processor\CustomerPutProcessor

      ApiPlatform\Metadata\Delete:
        uriTemplate: /customers/{id}

    normalizationContext:
      groups: ['customer:read']

    denormalizationContext:
      groups: ['customer:write']
*/

// ============================================================================
// VIOLATION 4: Infrastructure Calling Application Handler Directly
// ============================================================================

/*
 * VIOLATION MESSAGE:
 * Infrastructure must not depend on Application (Command Handler)
 * src/Customer/Infrastructure/EventListener/CustomerListener.php:25
 */

// ❌ WRONG - Infrastructure calling handler directly
namespace App\Customer\Infrastructure\EventListener;

use App\Customer\Application\CommandHandler\SendWelcomeEmailHandler; // Wrong!
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;

class CustomerListenerWrong
{
    public function __construct(
        private SendWelcomeEmailHandler $handler // Direct dependency on handler
    ) {}

    public function postPersist(LifecycleEventArgs $args): void
    {
        $customer = $args->getObject();
        if ($customer instanceof Customer) {
            // Calling handler directly - wrong layer dependency!
            ($this->handler)(new SendWelcomeEmailCommand($customer->id()));
        }
    }
}

// ✅ CORRECT - Use Command Bus or Domain Events

// Option 1: Use Command Bus
namespace App\Customer\Infrastructure\EventListener;

use App\Shared\Domain\Bus\Command\CommandBusInterface; // Use bus
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;

class CustomerListener
{
    public function __construct(
        private CommandBusInterface $commandBus // Depend on bus, not handler
    ) {}

    public function postPersist(LifecycleEventArgs $args): void
    {
        $customer = $args->getObject();
        if ($customer instanceof Customer) {
            // Dispatch command via bus - bus finds the handler
            $this->commandBus->dispatch(
                new SendWelcomeEmailCommand($customer->id())
            );
        }
    }
}

// ✅ BETTER - Use Domain Events (RECOMMENDED PATTERN)
namespace App\Customer\Domain\Entity;

class Customer extends AggregateRoot
{
    public function __construct(
        UlidInterface $ulid,
        string $email,
        string $initials,
        // ...
    ) {
        $this->ulid = $ulid;
        $this->email = $email;
        $this->initials = $initials;

        // Record domain event - will be dispatched by infrastructure
        $this->record(new CustomerCreated($this->ulid, $this->email));
    }
}

// Event subscriber in Application layer handles it
namespace App\Customer\Application\EventSubscriber;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;

class SendWelcomeEmailOnCustomerCreated implements DomainEventSubscriberInterface
{
    public function __construct(
        private EmailServiceInterface $emailService
    ) {}

    public static function subscribedTo(): array
    {
        return [CustomerCreated::class];
    }

    public function __invoke(CustomerCreated $event): void
    {
        // Send email using event data
        $this->emailService->sendWelcome(
            $event->customerId(),
            $event->email()
        );
    }
}

// ============================================================================
// VIOLATION 5: Using 'new' Instead of Factory in Production Code
// ============================================================================

// ❌ WRONG - Direct instantiation with 'new' in handler
namespace App\Customer\Application\CommandHandler;

class CreateCustomerHandlerWrong implements CommandHandlerInterface
{
    public function __construct(
        private CustomerRepositoryInterface $repository
    ) {}

    public function __invoke(CreateCustomerCommand $command): void
    {
        // ❌ Direct use of 'new' - tightly coupled, harder to test
        $customer = new Customer(
            new Ulid($command->id),
            $command->email,
            $command->initials,
            // ...
        );

        $this->repository->save($customer);
    }
}

// ✅ CORRECT - Use Factory Pattern (ACTUAL CODEBASE PATTERN)
namespace App\Customer\Application\CommandHandler;

use App\Customer\Domain\Factory\CustomerFactoryInterface;

class CreateCustomerHandler implements CommandHandlerInterface
{
    public function __construct(
        private CustomerRepositoryInterface $repository,
        private CustomerFactoryInterface $customerFactory  // ✅ Inject factory
    ) {}

    public function __invoke(CreateCustomerCommand $command): void
    {
        // ✅ Use factory - decoupled, easy to test and modify
        $customer = $this->customerFactory->create(
            new Ulid($command->id),
            $command->email,
            $command->initials,
            // ...
        );

        $this->repository->save($customer);
    }
}

// Factory implementation
namespace App\Customer\Domain\Factory;

use App\Customer\Domain\Entity\Customer;
use App\Shared\Domain\ValueObject\UlidInterface;

interface CustomerFactoryInterface
{
    public function create(
        UlidInterface $ulid,
        string $email,
        string $initials,
        string $phone,
        string $leadSource,
        CustomerType $type,
        CustomerStatus $status,
        bool $confirmed
    ): Customer;
}

final readonly class CustomerFactory implements CustomerFactoryInterface
{
    public function create(
        UlidInterface $ulid,
        string $email,
        string $initials,
        string $phone,
        string $leadSource,
        CustomerType $type,
        CustomerStatus $status,
        bool $confirmed
    ): Customer {
        // Factory encapsulates the 'new' keyword
        return new Customer(
            $initials,
            $email,
            $phone,
            $leadSource,
            $type,
            $status,
            $confirmed,
            $ulid
        );
    }
}

// ============================================================================
// VIOLATION 6: Anemic Domain Model (Not Deptrac, but architectural problem)
// ============================================================================

// ❌ WRONG - Business logic in Application layer
namespace App\Customer\Application\CommandHandler;

class UpdateCustomerStatusHandlerWrong implements CommandHandlerInterface
{
    public function __invoke(UpdateCustomerStatusCommand $command): void
    {
        $customer = $this->repository->findById($command->customerId);

        // Business rules in handler - WRONG!
        if ($customer->getStatus() === 'active' && $command->newStatus === 'active') {
            throw new CustomerAlreadyActiveException();
        }

        if ($command->newStatus === 'inactive') {
            // More business logic in handler
            $customer->setStatus('inactive');
            $customer->setDeactivatedAt(new \DateTimeImmutable());
        }

        $this->repository->save($customer);
    }
}

// ✅ CORRECT - Business logic in Domain entity
namespace App\Customer\Domain\Entity;

class Customer extends AggregateRoot
{
    private CustomerStatus $status;
    private ?\DateTimeImmutable $deactivatedAt = null;

    // ✅ Business logic in domain methods
    public function activate(): void
    {
        // Business rules enforced here
        if ($this->status->isActive()) {
            throw new CustomerAlreadyActiveException(
                "Customer {$this->ulid} is already active"
            );
        }

        $this->status = CustomerStatus::active();
        $this->deactivatedAt = null;
        $this->record(new CustomerActivated($this->ulid));
    }

    public function deactivate(): void
    {
        if ($this->status->isInactive()) {
            throw new CustomerAlreadyInactiveException(
                "Customer {$this->ulid} is already inactive"
            );
        }

        $this->status = CustomerStatus::inactive();
        $this->deactivatedAt = new \DateTimeImmutable();
        $this->record(new CustomerDeactivated($this->ulid));
    }
}

// Handler only orchestrates - delegates to domain
namespace App\Customer\Application\CommandHandler;

class UpdateCustomerStatusHandler implements CommandHandlerInterface
{
    public function __invoke(UpdateCustomerStatusCommand $command): void
    {
        $customer = $this->repository->findById($command->customerId);

        // ✅ Delegate to domain - business logic is there
        if ($command->newStatus === 'active') {
            $customer->activate();
        } else {
            $customer->deactivate();
        }

        $this->repository->save($customer);
    }
}

// ============================================================================
// COMPLETE WORKFLOW: Fixing a Violation
// ============================================================================

/*
 * STEP 1: Run Deptrac
 * $ make deptrac
 *
 * STEP 2: Read the violation carefully
 * Example output:
 * ---------------------------------------------------------------
 * Violation: Domain must not depend on Symfony
 * File: src/Customer/Domain/Entity/Customer.php:15
 * Violating code: uses Symfony\Component\Validator\Constraints as Assert
 * ---------------------------------------------------------------
 *
 * STEP 3: Understand the problem
 * - Customer entity is in Domain layer
 * - It's importing Symfony (framework)
 * - Domain must have NO external dependencies
 *
 * STEP 4: Plan the refactor (THIS CODEBASE PATTERN)
 * - Remove Symfony imports from domain
 * - Use primitives in domain entities
 * - Move validation to YAML config in Application layer
 * - Create DTO if needed (simple properties, no annotations)
 *
 * STEP 5: Refactor the code
 * - Update Customer entity to use primitives
 * - Create config/validator/Customer.yaml
 * - Create CustomerCreate DTO (simple class)
 * - Remove all Symfony imports from Domain
 *
 * STEP 6: Verify the fix
 * $ make deptrac
 *
 * STEP 7: Ensure tests still pass
 * $ make unit-tests
 */

// ============================================================================
// KEY PRINCIPLES RECAP (THIS CODEBASE)
// ============================================================================

/*
 * 1. NEVER MODIFY DEPTRAC.YAML TO FIX VIOLATIONS
 *    - deptrac.yaml defines the architecture
 *    - Violations mean code is in wrong layer
 *    - Fix the code, not the rules
 *
 * 2. LAYER DEPENDENCY RULES
 *    Domain → NOTHING (pure PHP)
 *    Application → Domain, Infrastructure, Symfony, ApiPlatform
 *    Infrastructure → Domain, Application, Symfony, Doctrine
 *
 * 3. DOMAIN LAYER IS SACRED
 *    - No framework imports
 *    - No persistence annotations (use XML)
 *    - No HTTP/API concerns (use YAML config)
 *    - Only business logic
 *
 * 4. VALIDATION STRATEGY (THIS CODEBASE)
 *    - YAML configuration for ALL validation (config/validator/)
 *    - Validate at Application boundary (DTOs)
 *    - Custom validators for business rules (UniqueEmail, Initials)
 *    - Simple primitives in domain entities
 *    - Only business invariants in domain methods
 *
 * 5. USE PRIMITIVES, NOT VALUE OBJECTS (UNLESS JUSTIFIED)
 *    - string $email (not Email VO) - validated in YAML
 *    - string $phone (not Phone VO) - validated in YAML
 *    - UlidInterface $ulid (VO justified - special domain concept)
 *    - See: 02-value-object-examples.php for decision criteria
 *
 * 6. USE FACTORIES, NOT 'NEW' (IN PRODUCTION CODE)
 *    - Inject CustomerFactoryInterface
 *    - Call $this->customerFactory->create()
 *    - Direct 'new' only acceptable in tests
 *    - See: ../REFERENCE.md - "Factory Pattern for Entity Creation"
 *
 * 7. BUSINESS LOGIC BELONGS IN DOMAIN
 *    - Not in handlers (orchestration only)
 *    - Not in repositories (persistence only)
 *    - Not in controllers/processors (delegation only)
 *    - In entities and their methods
 *
 * 8. USE BUSES FOR CROSS-LAYER COMMUNICATION
 *    - Command Bus for commands
 *    - Event Bus for events
 *    - Don't call handlers directly
 *
 * 9. YAML OVER ANNOTATIONS
 *    - API Platform config in YAML
 *    - Doctrine mapping in XML
 *    - Validation rules in YAML
 *    - Keep DTOs simple (properties only)
 *
 * 10. PRAGMATIC OVER PURE
 *     - Default to primitives, not Value Objects
 *     - Validate in Application layer, not Domain
 *     - Follow actual codebase patterns
 *     - Keep it simple (YAGNI principle)
 */
