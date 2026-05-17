<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\AuthPayloadFactory;
use App\User\Application\Factory\CompleteTwoFactorCommandFactoryInterface;
use App\User\Application\Factory\ConfirmTwoFactorCommandFactoryInterface;
use App\User\Application\Factory\DisableTwoFactorCommandFactoryInterface;
use App\User\Application\Factory\RefreshTokenCommandFactoryInterface;
use App\User\Application\Factory\RegenerateRecoveryCodesCommandFactoryInterface;
use App\User\Application\Factory\SetupTwoFactorCommandFactoryInterface;
use App\User\Application\Factory\SignInCommandFactoryInterface;
use App\User\Application\Factory\SignOutAllCommandFactoryInterface;
use App\User\Application\Factory\SignOutCommandFactoryInterface;
use App\User\Application\Resolver\CompleteTwoFactorAuthMutationResolver;
use App\User\Application\Resolver\ConfirmTwoFactorAuthMutationResolver;
use App\User\Application\Resolver\CurrentUserIdentityResolver;
use App\User\Application\Resolver\DisableTwoFactorAuthMutationResolver;
use App\User\Application\Resolver\HttpRequestContextResolverInterface;
use App\User\Application\Resolver\RefreshTokenAuthMutationResolver;
use App\User\Application\Resolver\RegenerateRecoveryCodesAuthMutationResolver;
use App\User\Application\Resolver\SetupTwoFactorAuthMutationResolver;
use App\User\Application\Resolver\SignInAuthMutationResolver;
use App\User\Application\Resolver\SignOutAllAuthMutationResolver;
use App\User\Application\Resolver\SignOutAuthMutationResolver;
use App\User\Application\Validator\MutationInputValidator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AuthMutationResolverConstructionTest extends UnitTestCase
{
    public function testResolversWithInputValidationCanBeConstructed(): void
    {
        $this->assertResolversCanBeConstructed([
            $this->createSignInResolver(),
            $this->createCompleteTwoFactorResolver(),
            $this->createConfirmTwoFactorResolver(),
            $this->createDisableTwoFactorResolver(),
            $this->createRefreshTokenResolver(),
        ]);
    }

    public function testResolversWithoutInputValidationCanBeConstructed(): void
    {
        $this->assertResolversCanBeConstructed([
            $this->createRegenerateRecoveryCodesResolver(),
            $this->createSetupTwoFactorResolver(),
            $this->createSignOutResolver(),
            $this->createSignOutAllResolver(),
        ]);
    }

    /**
     * @param list<MutationResolverInterface> $resolvers
     */
    private function assertResolversCanBeConstructed(array $resolvers): void
    {
        foreach ($resolvers as $resolver) {
            self::assertInstanceOf(MutationResolverInterface::class, $resolver);
        }
    }

    private function createSignInResolver(): MutationResolverInterface
    {
        return new SignInAuthMutationResolver(
            $this->createValidator(),
            $this->createCommandBus(),
            $this->createAuthPayloadFactory(),
            $this->createMock(SignInCommandFactoryInterface::class),
            $this->createMock(HttpRequestContextResolverInterface::class)
        );
    }

    private function createCompleteTwoFactorResolver(): MutationResolverInterface
    {
        return new CompleteTwoFactorAuthMutationResolver(
            $this->createValidator(),
            $this->createCommandBus(),
            $this->createAuthPayloadFactory(),
            $this->createMock(CompleteTwoFactorCommandFactoryInterface::class),
            $this->createMock(HttpRequestContextResolverInterface::class)
        );
    }

    private function createConfirmTwoFactorResolver(): MutationResolverInterface
    {
        return new ConfirmTwoFactorAuthMutationResolver(
            $this->createValidator(),
            $this->createCommandBus(),
            $this->createAuthPayloadFactory(),
            $this->createCurrentUserIdentityResolver(),
            $this->createMock(ConfirmTwoFactorCommandFactoryInterface::class)
        );
    }

    private function createDisableTwoFactorResolver(): MutationResolverInterface
    {
        return new DisableTwoFactorAuthMutationResolver(
            $this->createValidator(),
            $this->createCommandBus(),
            $this->createAuthPayloadFactory(),
            $this->createCurrentUserIdentityResolver(),
            $this->createMock(DisableTwoFactorCommandFactoryInterface::class)
        );
    }

    private function createRefreshTokenResolver(): MutationResolverInterface
    {
        return new RefreshTokenAuthMutationResolver(
            $this->createValidator(),
            $this->createCommandBus(),
            $this->createAuthPayloadFactory(),
            $this->createMock(RefreshTokenCommandFactoryInterface::class)
        );
    }

    private function createRegenerateRecoveryCodesResolver(): MutationResolverInterface
    {
        return new RegenerateRecoveryCodesAuthMutationResolver(
            $this->createCommandBus(),
            $this->createAuthPayloadFactory(),
            $this->createCurrentUserIdentityResolver(),
            $this->createMock(RegenerateRecoveryCodesCommandFactoryInterface::class)
        );
    }

    private function createSetupTwoFactorResolver(): MutationResolverInterface
    {
        return new SetupTwoFactorAuthMutationResolver(
            $this->createCommandBus(),
            $this->createAuthPayloadFactory(),
            $this->createCurrentUserIdentityResolver(),
            $this->createMock(SetupTwoFactorCommandFactoryInterface::class)
        );
    }

    private function createSignOutResolver(): MutationResolverInterface
    {
        return new SignOutAuthMutationResolver(
            $this->createCommandBus(),
            $this->createAuthPayloadFactory(),
            $this->createCurrentUserIdentityResolver(),
            $this->createMock(SignOutCommandFactoryInterface::class)
        );
    }

    private function createSignOutAllResolver(): MutationResolverInterface
    {
        return new SignOutAllAuthMutationResolver(
            $this->createCommandBus(),
            $this->createAuthPayloadFactory(),
            $this->createCurrentUserIdentityResolver(),
            $this->createMock(SignOutAllCommandFactoryInterface::class)
        );
    }

    private function createCommandBus(): CommandBusInterface
    {
        return $this->createMock(CommandBusInterface::class);
    }

    private function createAuthPayloadFactory(): AuthPayloadFactory
    {
        return new AuthPayloadFactory();
    }

    private function createValidator(): MutationInputValidator
    {
        return new MutationInputValidator(
            $this->createMock(ValidatorInterface::class)
        );
    }

    private function createCurrentUserIdentityResolver(): CurrentUserIdentityResolver
    {
        return new CurrentUserIdentityResolver(
            $this->createMock(Security::class)
        );
    }
}
