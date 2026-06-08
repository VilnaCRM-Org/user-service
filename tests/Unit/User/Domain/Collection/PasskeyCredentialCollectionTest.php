<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Collection;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Collection\PasskeyCredentialCollection;
use App\User\Domain\Entity\PasskeyCredential;
use DateTimeImmutable;

use function iterator_to_array;
use function json_encode;

use const JSON_THROW_ON_ERROR;

final class PasskeyCredentialCollectionTest extends UnitTestCase
{
    public function testCollectionCountsAndIteratesCredentials(): void
    {
        $firstCredential = $this->createCredential();
        $secondCredential = $this->createCredential();
        $collection = new PasskeyCredentialCollection($firstCredential, $secondCredential);

        self::assertCount(2, $collection);
        self::assertSame(
            [$firstCredential, $secondCredential],
            iterator_to_array($collection)
        );
    }

    private function createCredential(): PasskeyCredential
    {
        return new PasskeyCredential(
            $this->faker->uuid(),
            $this->faker->uuid(),
            $this->faker->uuid(),
            json_encode(['record' => $this->faker->boolean()], JSON_THROW_ON_ERROR),
            $this->faker->words(2, true),
            new DateTimeImmutable()
        );
    }
}
