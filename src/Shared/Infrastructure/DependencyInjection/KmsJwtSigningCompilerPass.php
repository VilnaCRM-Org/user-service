<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\DependencyInjection;

use App\OAuth\Infrastructure\Security\KmsBearerTokenValidator;
use App\OAuth\Infrastructure\Security\KmsCryptKey;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * The league bundle builds its AuthorizationServer and ResourceServer with a
 * PEM CryptKey from the private_key and public_key settings. With KMS JWT
 * signing (S5.11) no PEM exists: the authorization server gets a key-less
 * KmsCryptKey (access tokens are signed by KmsSignedAccessToken), and the
 * resource server verifies through the kid-aware KMS public keys.
 */
final class KmsJwtSigningCompilerPass implements CompilerPassInterface
{
    private const AUTHORIZATION_SERVER = 'league.oauth2_server.authorization_server';
    private const RESOURCE_SERVER = 'league.oauth2_server.resource_server';
    private const AUTHORIZATION_SERVER_PRIVATE_KEY_ARGUMENT = 3;
    private const RESOURCE_SERVER_PUBLIC_KEY_ARGUMENT = 1;
    private const RESOURCE_SERVER_VALIDATOR_ARGUMENT = 2;

    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        $kmsKey = new Reference(KmsCryptKey::class);

        $container->getDefinition(self::AUTHORIZATION_SERVER)
            ->replaceArgument(self::AUTHORIZATION_SERVER_PRIVATE_KEY_ARGUMENT, $kmsKey);

        $container->getDefinition(self::RESOURCE_SERVER)
            ->replaceArgument(self::RESOURCE_SERVER_PUBLIC_KEY_ARGUMENT, $kmsKey)
            ->replaceArgument(
                self::RESOURCE_SERVER_VALIDATOR_ARGUMENT,
                new Reference(KmsBearerTokenValidator::class)
            );
    }
}
