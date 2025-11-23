<?php

declare(strict_types=1);

/**
 * Example: Rich Domain Entity following DDD principles
 *
 * Location: src/Catalog/Domain/Entity/Product.php
 * Layer: Domain
 * Dependencies: NONE (pure PHP, only domain value objects)
 */

namespace App\Catalog\Domain\Entity;

use App\Catalog\Domain\Event\ProductCreated;
use App\Catalog\Domain\Event\ProductPriceChanged;
use App\Catalog\Domain\Event\ProductPublished;
use App\Catalog\Domain\Event\ProductUnpublished;
use App\Catalog\Domain\Exception\InvalidProductNameException;
use App\Catalog\Domain\Exception\InvalidProductPriceException;
use App\Catalog\Domain\Exception\ProductAlreadyPublishedException;
use App\Catalog\Domain\Exception\ProductAlreadyUnpublishedException;
use App\Catalog\Domain\ValueObject\Money;
use App\Catalog\Domain\ValueObject\ProductName;
use App\Catalog\Domain\ValueObject\ProductStatus;
use App\Shared\Domain\Aggregate\AggregateRoot;
use App\Shared\Domain\ValueObject\Ulid;

/**
 * Product Aggregate Root
 *
 * This is a RICH domain model with:
 * - Business logic encapsulated in methods
 * - Invariants enforced through method calls (not setters)
 * - Domain events recorded for state changes
 * - NO external dependencies (pure PHP)
 */
final class Product extends AggregateRoot
{
    private Ulid $id;
    private ProductName $name;
    private Money $price;
    private ProductStatus $status;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $publishedAt = null;

    /**
     * Constructor - public to allow Factory classes to instantiate
     *
     * NOTE: In production code, this should be called via ProductFactory,
     * not directly. Direct instantiation with 'new' is only acceptable in tests.
     *
     * @see ProductFactoryInterface
     */
    public function __construct(
        Ulid $id,
        ProductName $name,
        Money $price,
        ProductStatus $status,
        \DateTimeImmutable $createdAt
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->price = $price;
        $this->status = $status;
        $this->createdAt = $createdAt;

        // Record domain event when product is created
        $this->record(new ProductCreated(
            $this->id,
            $this->name,
            $this->price
        ));
    }

    /**
     * Business method - not a setter!
     * Encapsulates business rules and records events
     */
    public function changePrice(Money $newPrice): void
    {
        // Business rule: price cannot be negative
        if ($newPrice->isNegative()) {
            throw new InvalidProductPriceException(
                "Product price cannot be negative"
            );
        }

        // Business rule: don't record event if price hasn't changed
        if ($this->price->equals($newPrice)) {
            return;
        }

        $oldPrice = $this->price;
        $this->price = $newPrice;

        // Record domain event for auditing
        $this->record(new ProductPriceChanged(
            $this->id,
            $oldPrice,
            $newPrice
        ));
    }

    /**
     * Business method with state transition
     */
    public function publish(): void
    {
        // Business invariant: can't publish already published product
        if ($this->status->isPublished()) {
            throw new ProductAlreadyPublishedException(
                "Product {$this->id} is already published"
            );
        }

        $this->status = ProductStatus::published();
        $this->publishedAt = new \DateTimeImmutable();

        // Record domain event
        $this->record(new ProductPublished($this->id, $this->publishedAt));
    }

    /**
     * Business method with state transition
     */
    public function unpublish(): void
    {
        // Business invariant: can't unpublish draft product
        if ($this->status->isDraft()) {
            throw new ProductAlreadyUnpublishedException(
                "Product {$this->id} is not published"
            );
        }

        $this->status = ProductStatus::draft();
        $this->publishedAt = null;

        // Record domain event
        $this->record(new ProductUnpublished($this->id));
    }

    /**
     * Business method to rename product
     */
    public function rename(ProductName $newName): void
    {
        // Business rule validation is in ProductName value object
        // No need to duplicate here - this is the power of VOs!
        $this->name = $newName;
    }

    /**
     * Query method - reveals state
     */
    public function isPublished(): bool
    {
        return $this->status->isPublished();
    }

    /**
     * Getters - only for reading state, NOT for modification
     */
    public function id(): Ulid
    {
        return $this->id;
    }

    public function name(): ProductName
    {
        return $this->name;
    }

    public function price(): Money
    {
        return $this->price;
    }

    public function status(): ProductStatus
    {
        return $this->status;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function publishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }
}
