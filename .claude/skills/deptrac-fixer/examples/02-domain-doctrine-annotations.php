<?php

declare(strict_types=1);

/**
 * Example 2: Fixing Domain → Doctrine ODM Annotation Violations
 *
 * VIOLATION:
 * Domain must not depend on Doctrine
 *   src/Product/Domain/Entity/Product.php:10
 *     uses Doctrine\ODM\MongoDB\Mapping\Annotations as ODM
 */

// ============================================================================
// BEFORE (WRONG) - Domain entity with Doctrine ODM annotations
// ============================================================================

namespace App\Product\Domain\Entity;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;  // VIOLATION!
use Doctrine\Common\Collections\ArrayCollection;      // VIOLATION!
use Doctrine\Common\Collections\Collection;           // VIOLATION!
use App\Shared\Domain\ValueObject\Ulid;

#[ODM\Document(collection: 'products')]  // VIOLATION!
class ProductBefore
{
    #[ODM\Id(type: 'ulid', strategy: 'NONE')]  // VIOLATION!
    private Ulid $id;

    #[ODM\Field(type: 'string')]  // VIOLATION!
    private string $name;

    #[ODM\Field(type: 'string', nullable: true)]
    private ?string $description;

    #[ODM\EmbedOne(targetDocument: Money::class)]  // VIOLATION!
    private Money $price;

    #[ODM\Field(type: 'boolean')]
    private bool $active;

    #[ODM\Field(type: 'date_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ODM\ReferenceMany(targetDocument: Tag::class)]  // VIOLATION!
    private Collection $tags;

    public function __construct()
    {
        $this->tags = new ArrayCollection();  // VIOLATION!
    }
}

// ============================================================================
// AFTER (CORRECT) - Pure domain entity without Doctrine imports (PRAGMATIC)
// ============================================================================

/**
 * PRAGMATIC APPROACH:
 * - Use primitives for simple fields (string $name, string $description)
 * - Use Value Objects ONLY when needed (Money has operations, TagCollection)
 * - No static factory methods - use injectable Factory instead
 * - Entity constructor is public to allow Factory to instantiate
 */
namespace App\Product\Domain\Entity;

use App\Product\Domain\ValueObject\Money;  // ✅ VO - has operations (add, subtract)
use App\Product\Domain\Collection\TagCollection;  // ✅ Domain collection
use App\Product\Domain\Event\ProductCreated;
use App\Shared\Domain\Aggregate\AggregateRoot;
use App\Shared\Domain\ValueObject\Ulid;

final class Product extends AggregateRoot
{
    private Ulid $id;
    private string $name;                      // ✅ Primitive - validated in YAML
    private ?string $description;              // ✅ Primitive - just text
    private Money $price;                      // ✅ VO - has operations (add, subtract)
    private bool $active;                      // ✅ Primitive - simple boolean
    private \DateTimeImmutable $createdAt;
    private TagCollection $tags;

    /**
     * Constructor is public to allow Factory classes to instantiate
     *
     * NOTE: In production code, this should be called via ProductFactory,
     * not directly. Direct instantiation with 'new' is only acceptable in tests.
     *
     * @see ProductFactoryInterface
     */
    public function __construct(
        Ulid $id,
        string $name,
        ?string $description,
        Money $price,
        bool $active,
        \DateTimeImmutable $createdAt
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->price = $price;
        $this->active = $active;
        $this->createdAt = $createdAt;
        $this->tags = new TagCollection();

        // Record domain event
        $this->record(new ProductCreated($id, $name, $price));
    }

    // Business methods
    public function updatePrice(Money $newPrice): void
    {
        $this->price = $newPrice;
        $this->record(new ProductPriceUpdated($this->id, $newPrice));
    }

    public function deactivate(): void
    {
        if (!$this->active) {
            throw new ProductAlreadyDeactivatedException();
        }
        $this->active = false;
        $this->record(new ProductDeactivated($this->id));
    }

    public function addTag(Tag $tag): void
    {
        $this->tags->add($tag);
    }

    // Getters
    public function id(): Ulid
    {
        return $this->id;
    }

    public function name(): string  // ✅ Returns primitive
    {
        return $this->name;
    }

    public function description(): ?string  // ✅ Returns primitive
    {
        return $this->description;
    }

    public function price(): Money  // ✅ Returns VO (has operations)
    {
        return $this->price;
    }

    public function tags(): TagCollection
    {
        return $this->tags;
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}

// ============================================================================
// FACTORY PATTERN (RECOMMENDED)
// ============================================================================

namespace App\Product\Domain\Factory;

use App\Product\Domain\Entity\Product;
use App\Product\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Ulid;

interface ProductFactoryInterface
{
    public function create(
        Ulid $id,
        string $name,
        ?string $description,
        Money $price
    ): Product;
}

final readonly class ProductFactory implements ProductFactoryInterface
{
    public function create(
        Ulid $id,
        string $name,
        ?string $description,
        Money $price
    ): Product {
        // Factory encapsulates the 'new' keyword
        return new Product(
            $id,
            $name,
            $description,
            $price,
            true, // Active by default
            new \DateTimeImmutable()
        );
    }
}

// Usage in Command Handler
namespace App\Product\Application\CommandHandler;

use App\Product\Domain\Factory\ProductFactoryInterface;

final readonly class CreateProductHandler implements CommandHandlerInterface
{
    public function __construct(
        private ProductRepositoryInterface $repository,
        private ProductFactoryInterface $productFactory  // ✅ Inject factory
    ) {}

    public function __invoke(CreateProductCommand $command): void
    {
        // ✅ Use factory instead of static method or 'new'
        $product = $this->productFactory->create(
            $command->id,
            $command->name,
            $command->description,
            Money::fromCents($command->priceInCents, $command->currency)
        );

        $this->repository->save($product);
    }
}

// ============================================================================
// DOMAIN COLLECTION - Replace Doctrine Collection
// ============================================================================

namespace App\Product\Domain\Collection;

use App\Product\Domain\Entity\Tag;

final class TagCollection
{
    /** @var Tag[] */
    private array $items = [];

    public function add(Tag $tag): void
    {
        if (!$this->contains($tag)) {
            $this->items[] = $tag;
        }
    }

    public function remove(Tag $tag): void
    {
        $this->items = array_filter(
            $this->items,
            fn(Tag $item) => !$item->equals($tag)
        );
    }

    public function contains(Tag $tag): bool
    {
        foreach ($this->items as $item) {
            if ($item->equals($tag)) {
                return true;
            }
        }
        return false;
    }

    /** @return Tag[] */
    public function all(): array
    {
        return $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }
}

// ============================================================================
// VALUE OBJECT - Money (embedded document)
// ============================================================================

namespace App\Product\Domain\ValueObject;

use App\Product\Domain\Exception\InvalidMoneyException;

final readonly class Money
{
    private function __construct(
        private int $amountInCents,
        private string $currency
    ) {
    }

    public static function fromCents(int $cents, string $currency = 'USD'): self
    {
        if ($cents < 0) {
            throw new InvalidMoneyException('Amount cannot be negative');
        }

        $validCurrencies = ['USD', 'EUR', 'GBP', 'UAH'];
        if (!in_array($currency, $validCurrencies, true)) {
            throw new InvalidMoneyException("Invalid currency: {$currency}");
        }

        return new self($cents, $currency);
    }

    public function amountInCents(): int
    {
        return $this->amountInCents;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function add(self $other): self
    {
        $this->ensureSameCurrency($other);
        return new self($this->amountInCents + $other->amountInCents, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->ensureSameCurrency($other);
        $newAmount = $this->amountInCents - $other->amountInCents;

        if ($newAmount < 0) {
            throw new InvalidMoneyException('Result cannot be negative');
        }

        return new self($newAmount, $this->currency);
    }

    public function isLessThan(self $other): bool
    {
        $this->ensureSameCurrency($other);
        return $this->amountInCents < $other->amountInCents;
    }

    public function isGreaterThan(self $other): bool
    {
        $this->ensureSameCurrency($other);
        return $this->amountInCents > $other->amountInCents;
    }

    public function equals(self $other): bool
    {
        return $this->amountInCents === $other->amountInCents
            && $this->currency === $other->currency;
    }

    private function ensureSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidMoneyException(
                "Cannot operate on different currencies: {$this->currency} vs {$other->currency}"
            );
        }
    }

    public function format(): string
    {
        $amount = $this->amountInCents / 100;
        return sprintf('%s %.2f', $this->currency, $amount);
    }
}

// ============================================================================
// XML MAPPING - Doctrine configuration in config/doctrine/
// ============================================================================

/*
File: config/doctrine/Product.mongodb.xml

<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                        xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                        http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">

    <document name="App\Product\Domain\Entity\Product" collection="products">
        <field name="id" type="ulid" id="true" strategy="NONE"/>
        <field name="name" type="string"/>
        <field name="description" type="string" nullable="true"/>
        <field name="active" type="boolean"/>
        <field name="createdAt" type="date_immutable"/>

        <!-- Embedded Value Object -->
        <embed-one field="price" target-document="App\Product\Domain\ValueObject\Money"/>

        <!-- Reference to other documents -->
        <reference-many field="tags" target-document="App\Product\Domain\Entity\Tag"/>
    </document>
</doctrine-mongo-mapping>
*/

// ============================================================================
// XML MAPPING FOR VALUE OBJECT (Embedded)
// ============================================================================

/*
File: config/doctrine/Money.mongodb.xml

<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping">

    <embedded-document name="App\Product\Domain\ValueObject\Money">
        <field name="amountInCents" type="int"/>
        <field name="currency" type="string"/>
    </embedded-document>
</doctrine-mongo-mapping>
*/

// ============================================================================
// INFRASTRUCTURE REPOSITORY - Uses Doctrine internally
// ============================================================================

namespace App\Product\Infrastructure\Repository;

use App\Product\Domain\Entity\Product;
use App\Product\Domain\Repository\ProductRepositoryInterface;
use App\Shared\Domain\ValueObject\Ulid;
use Doctrine\ODM\MongoDB\DocumentManager;

final class MongoDBProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private DocumentManager $documentManager
    ) {
    }

    public function save(Product $product): void
    {
        $this->documentManager->persist($product);
        $this->documentManager->flush();
    }

    public function findById(Ulid $id): ?Product
    {
        return $this->documentManager->find(Product::class, $id);
    }

    public function findByName(string $name): ?Product
    {
        return $this->documentManager->getRepository(Product::class)
            ->findOneBy(['name' => $name]);
    }

    public function findActive(): array
    {
        return $this->documentManager->getRepository(Product::class)
            ->findBy(['active' => true]);
    }
}

// ============================================================================
// KEY POINTS (PRAGMATIC APPROACH):
// 1. Domain entity has NO Doctrine imports (pure PHP)
// 2. All persistence concerns are in XML mappings (config/doctrine/)
// 3. Use PRIMITIVES for simple fields (string $name, string $description)
// 4. Use VALUE OBJECTS only when needed (Money has operations, TagCollection)
// 5. Use FACTORY PATTERN (ProductFactoryInterface) not static methods
// 6. Constructor is PUBLIC to allow factory instantiation
// 7. Custom domain collections replace Doctrine Collections
// 8. Value Objects with operations are mapped as embedded documents
// 9. Repository implementation (Infrastructure) uses Doctrine internally
// 10. Follow actual codebase patterns (see src/Core/Customer/Domain/Entity/)
// ============================================================================
