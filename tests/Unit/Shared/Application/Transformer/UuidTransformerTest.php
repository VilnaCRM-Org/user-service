<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Transformer;

use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\AbstractUid;

final class UuidTransformerTest extends UnitTestCase
{
    public function testTransformFromSymfonyUuid(): void
    {
        $uuid = $this->faker->uuid();
        $symfonyUuid = $this->createMock(AbstractUid::class);
        $symfonyUuid->method('__toString')
            ->willReturn($uuid);

        $uuidTransformer = new UuidTransformer(new UuidFactory());

        $result = $uuidTransformer->transformFromSymfonyUuid($symfonyUuid);

        $expectedUuid = new Uuid($uuid);
        $this->assertEquals($expectedUuid, $result);
    }

    public function testTransformFromString(): void
    {
        $uuid = $this->faker->uuid();

        $uuidTransformer = new UuidTransformer(new UuidFactory());

        $result = $uuidTransformer->transformFromString($uuid);

        $expectedUuid = new Uuid($uuid);
        $this->assertEquals($expectedUuid, $result);
    }
}