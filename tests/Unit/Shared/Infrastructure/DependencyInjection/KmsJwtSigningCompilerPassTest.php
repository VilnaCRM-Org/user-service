<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\DependencyInjection;

use App\OAuth\Infrastructure\Security\KmsBearerTokenValidator;
use App\OAuth\Infrastructure\Security\KmsCryptKey;
use App\Shared\Infrastructure\DependencyInjection\KmsJwtSigningCompilerPass;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class KmsJwtSigningCompilerPassTest extends UnitTestCase
{
    public function testAuthorizationServerUsesTheKmsKeyInsteadOfALocalPrivateKey(): void
    {
        $container = $this->container();

        (new KmsJwtSigningCompilerPass())->process($container);

        $arguments = $container->getDefinition('league.oauth2_server.authorization_server')
            ->getArguments();
        self::assertEquals(new Reference(KmsCryptKey::class), $arguments[3]);
        self::assertSame('client-repository', $arguments[0]);
        self::assertSame('encryption-key', $arguments[4]);
    }

    public function testResourceServerVerifiesThroughKmsPublicKeys(): void
    {
        $container = $this->container();

        (new KmsJwtSigningCompilerPass())->process($container);

        $arguments = $container->getDefinition('league.oauth2_server.resource_server')
            ->getArguments();
        self::assertSame('access-token-repository', $arguments[0]);
        self::assertEquals(new Reference(KmsCryptKey::class), $arguments[1]);
        self::assertEquals(new Reference(KmsBearerTokenValidator::class), $arguments[2]);
    }

    private function container(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setDefinition(
            'league.oauth2_server.authorization_server',
            new Definition(null, [
                'client-repository',
                'access-token-repository',
                'scope-repository',
                'local-private-key',
                'encryption-key',
                null,
            ])
        );
        $container->setDefinition(
            'league.oauth2_server.resource_server',
            new Definition(null, ['access-token-repository', 'local-public-key', 'bearer'])
        );

        return $container;
    }
}
