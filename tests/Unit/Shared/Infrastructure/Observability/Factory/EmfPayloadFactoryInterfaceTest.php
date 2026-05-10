<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability\Factory;

use App\Shared\Infrastructure\Observability\Factory\EmfPayloadFactory;
use App\Shared\Infrastructure\Observability\Factory\EmfPayloadFactoryInterface;
use App\Tests\Unit\UnitTestCase;

final class EmfPayloadFactoryInterfaceTest extends UnitTestCase
{
    public function testFactoryImplementsInterface(): void
    {
        $interfaces = class_implements(EmfPayloadFactory::class);

        self::assertContains(
            EmfPayloadFactoryInterface::class,
            $interfaces,
            'EmfPayloadFactory must implement EmfPayloadFactoryInterface'
        );
    }
}
