<?php

declare(strict_types=1);

/**
 * Example: CQRS Pattern (Command Query Responsibility Segregation)
 *
 * This example shows the complete flow:
 * 1. Command (intent to change state)
 * 2. Command Handler (orchestrates the use case)
 * 3. Domain Entity (contains business logic)
 * 4. Repository (persistence)
 */

// ============================================================================
// COMMAND (Application Layer)
// ============================================================================

namespace App\Catalog\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;
use App\Shared\Domain\ValueObject\Ulid;

/**
 * CreateProductCommand - Represents the INTENT to create a product
 *
 * Location: src/Catalog/Application/Command/CreateProductCommand.php
 * Layer: Application
 *
 * Characteristics:
 * - Immutable (readonly)
 * - Data-only (no business logic)
 * - Represents user's intent
 */
final readonly class CreateProductCommand implements CommandInterface
{
    public function __construct(
        public Ulid $id,
        public string $name,
        public int $priceInCents,
        public string $currency
    ) {}
}

/**
 * UpdateProductPriceCommand - Intent to change product price
 */
final readonly class UpdateProductPriceCommand implements CommandInterface
{
    public function __construct(
        public Ulid $productId,
        public int $newPriceInCents,
        public string $currency
    ) {}
}

/**
 * PublishProductCommand - Intent to publish a product
 */
final readonly class PublishProductCommand implements CommandInterface
{
    public function __construct(
        public Ulid $productId
    ) {}
}

// ============================================================================
// COMMAND HANDLERS (Application Layer)
// ============================================================================

namespace App\Catalog\Application\CommandHandler;

use App\Catalog\Application\Command\CreateProductCommand;
use App\Catalog\Application\Command\UpdateProductPriceCommand;
use App\Catalog\Application\Command\PublishProductCommand;
use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Factory\ProductFactoryInterface;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Catalog\Domain\ValueObject\Money;
use App\Catalog\Domain\ValueObject\ProductName;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Catalog\Domain\Exception\ProductNotFoundException;

/**
 * CreateProductHandler - Orchestrates product creation
 *
 * Location: src/Catalog/Application/CommandHandler/CreateProductHandler.php
 * Layer: Application
 *
 * Responsibilities:
 * - Orchestrate the use case
 * - Transform command data to domain objects
 * - Call domain factory
 * - Persist via repository
 *
 * NOT responsible for:
 * - Business logic (that's in the domain)
 * - Validation of business rules (domain handles that)
 */
final readonly class CreateProductHandler implements CommandHandlerInterface
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private ProductFactoryInterface $productFactory  // ✅ Inject factory
    ) {}

    public function __invoke(CreateProductCommand $command): void
    {
        // Transform primitive data to domain value objects
        $name = new ProductName($command->name);
        $price = new Money($command->priceInCents, $command->currency);

        // ✅ Use factory instead of static method or 'new'
        // Factory encapsulates creation logic and improves testability
        $product = $this->productFactory->create(
            $command->id,
            $name,
            $price
        );

        // Persist - infrastructure concern
        $this->productRepository->save($product);

        // Domain events (like ProductCreated) are automatically dispatched
        // by the infrastructure layer after successful persistence
    }
}

/**
 * UpdateProductPriceHandler - Orchestrates price update
 *
 * Shows how to retrieve, modify, and persist an aggregate
 */
final readonly class UpdateProductPriceHandler implements CommandHandlerInterface
{
    public function __construct(
        private ProductRepositoryInterface $productRepository
    ) {}

    public function __invoke(UpdateProductPriceCommand $command): void
    {
        // Retrieve aggregate from repository
        $product = $this->productRepository->findById($command->productId);

        if ($product === null) {
            throw new ProductNotFoundException(
                "Product not found: {$command->productId}"
            );
        }

        // Transform to domain value object
        $newPrice = new Money($command->newPriceInCents, $command->currency);

        // Call domain method - business logic and validation in domain
        // This will:
        // 1. Validate the price (can't be negative)
        // 2. Record ProductPriceChanged event
        $product->changePrice($newPrice);

        // Persist changes
        $this->productRepository->save($product);

        // ProductPriceChanged event will be dispatched automatically
    }
}

/**
 * PublishProductHandler - Orchestrates product publishing
 *
 * Shows state transition through domain method
 */
final readonly class PublishProductHandler implements CommandHandlerInterface
{
    public function __construct(
        private ProductRepositoryInterface $productRepository
    ) {}

    public function __invoke(PublishProductCommand $command): void
    {
        $product = $this->productRepository->findById($command->productId);

        if ($product === null) {
            throw new ProductNotFoundException(
                "Product not found: {$command->productId}"
            );
        }

        // Domain method handles:
        // 1. Business rule: can't publish already published product
        // 2. State transition: draft -> published
        // 3. Recording ProductPublished event
        $product->publish();

        $this->productRepository->save($product);
    }
}

// ============================================================================
// HOW HANDLERS ARE REGISTERED
// ============================================================================

/*
 * In config/services.yaml:
 *
 * _instanceof:
 *     App\Shared\Domain\Bus\Command\CommandHandlerInterface:
 *         tags: ['app.command_handler']
 *
 * This automatically registers all command handlers with the command bus.
 * No manual registration needed!
 */

// ============================================================================
// HOW TO USE COMMANDS IN YOUR CODE
// ============================================================================

namespace App\Catalog\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Catalog\Application\Command\CreateProductCommand;
use App\Catalog\Application\DTO\CreateProductDTO;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Domain\ValueObject\Ulid;

/**
 * Example API Platform Processor using Command Bus
 *
 * Location: src/Catalog/Application/Processor/CreateProductProcessor.php
 * Layer: Application
 */
final readonly class CreateProductProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus
    ) {}

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): mixed {
        assert($data instanceof CreateProductDTO);

        // Create command from DTO
        $command = new CreateProductCommand(
            id: Ulid::random(),
            name: $data->name,
            priceInCents: $data->priceInCents,
            currency: $data->currency
        );

        // Dispatch to command bus
        // Bus will find the handler and execute it
        $this->commandBus->dispatch($command);

        // Return the DTO or entity for API response
        return $data;
    }
}

// ============================================================================
// REPOSITORY INTERFACE (Domain Layer)
// ============================================================================

namespace App\Catalog\Domain\Repository;

use App\Catalog\Domain\Entity\Product;
use App\Shared\Domain\ValueObject\Ulid;

/**
 * ProductRepositoryInterface - Port (Hexagonal Architecture)
 *
 * Location: src/Catalog/Domain/Repository/ProductRepositoryInterface.php
 * Layer: Domain
 *
 * This is the PORT - defined in domain, implemented in infrastructure
 */
interface ProductRepositoryInterface
{
    /**
     * Persist a product (create or update)
     */
    public function save(Product $product): void;

    /**
     * Find product by ID
     */
    public function findById(Ulid $id): ?Product;

    /**
     * Find product by name
     */
    public function findByName(ProductName $name): ?Product;

    /**
     * Find all published products
     */
    public function findAllPublished(): array;
}

// ============================================================================
// REPOSITORY IMPLEMENTATION (Infrastructure Layer)
// ============================================================================

namespace App\Catalog\Infrastructure\Repository;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use App\Catalog\Domain\ValueObject\ProductName;
use App\Shared\Domain\ValueObject\Ulid;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * ProductRepository - Adapter (Hexagonal Architecture)
 *
 * Location: src/Catalog/Infrastructure/Repository/ProductRepository.php
 * Layer: Infrastructure
 *
 * This is the ADAPTER - implements the domain interface using Doctrine
 */
final class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private DocumentManager $documentManager
    ) {}

    public function save(Product $product): void
    {
        $this->documentManager->persist($product);
        $this->documentManager->flush();

        // After flush, domain events are dispatched
        // This is handled by Doctrine event listeners
    }

    public function findById(Ulid $id): ?Product
    {
        return $this->documentManager->find(Product::class, $id);
    }

    public function findByName(ProductName $name): ?Product
    {
        return $this->documentManager
            ->getRepository(Product::class)
            ->findOneBy(['name' => $name->value()]);
    }

    public function findAllPublished(): array
    {
        return $this->documentManager
            ->getRepository(Product::class)
            ->findBy(['status' => 'published']);
    }
}

// ============================================================================
// KEY TAKEAWAYS
// ============================================================================

/*
 * CQRS PATTERN BENEFITS:
 *
 * 1. SEPARATION OF CONCERNS
 *    - Commands: Write operations
 *    - Queries: Read operations (not shown here, but would use repositories directly)
 *
 * 2. SINGLE RESPONSIBILITY
 *    - Each handler does ONE thing
 *    - Easy to test, maintain, extend
 *
 * 3. COMMAND BUS BENEFITS
 *    - Decouples sender from handler
 *    - Easy to add middleware (logging, validation, transactions)
 *    - Handler location is transparent
 *
 * 4. DOMAIN-CENTRIC
 *    - Handlers orchestrate
 *    - Domain contains business logic
 *    - Infrastructure is a detail
 *
 * 5. TESTABILITY
 *    - Mock repository for unit tests
 *    - Test handlers in isolation
 *    - Test domain without infrastructure
 *
 * ANTI-PATTERNS TO AVOID:
 *
 * ❌ Business logic in handler
 * ❌ Handler calling other handlers directly
 * ❌ Handler containing complex queries
 * ❌ Skipping domain methods, calling setters directly
 * ❌ Not using value objects for complex validation
 *
 * CORRECT PATTERN:
 *
 * ✅ Handler orchestrates only
 * ✅ Business logic in domain entities
 * ✅ Value objects for validation
 * ✅ Repository for persistence abstraction
 * ✅ Domain events for side effects
 */
