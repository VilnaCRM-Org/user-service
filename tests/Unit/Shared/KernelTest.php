<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared;

use App\Shared\Infrastructure\DependencyInjection\KmsJwtSigningCompilerPass;
use App\Shared\Kernel;
use App\Tests\Unit\UnitTestCase;
use ReflectionMethod;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class KernelTest extends UnitTestCase
{
    public function testRegistersTheKmsJwtSigningCompilerPass(): void
    {
        $container = new ContainerBuilder();

        (new ReflectionMethod(Kernel::class, 'build'))
            ->invoke(new Kernel('test', false), $container);

        $passClasses = array_map(
            'get_class',
            $container->getCompilerPassConfig()->getBeforeOptimizationPasses()
        );
        self::assertCount(1, array_keys($passClasses, KmsJwtSigningCompilerPass::class, true));
    }
}
