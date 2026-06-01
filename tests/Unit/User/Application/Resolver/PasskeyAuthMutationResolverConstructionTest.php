<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\AuthPayloadFactory;
use App\User\Application\Resolver\CurrentUserIdentityResolver;
use App\User\Application\Resolver\HttpRequestContextResolverInterface;
use App\User\Application\Resolver\PasskeyRegistrationCompleteAuthMutationResolver;
use App\User\Application\Resolver\PasskeyRegistrationOptionsAuthMutationResolver;
use App\User\Application\Resolver\PasskeySignInCompleteAuthMutationResolver;
use App\User\Application\Resolver\PasskeySignInOptionsAuthMutationResolver;
use App\User\Application\Resolver\PasskeySignUpCompleteAuthMutationResolver;
use App\User\Application\Resolver\PasskeySignUpOptionsAuthMutationResolver;
use App\User\Application\Validator\MutationInputValidator;
use function array_merge;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class PasskeyAuthMutationResolverConstructionTest extends UnitTestCase
{
    public function testPasskeyResolversCanBeConstructed(): void
    {
        foreach ($this->createResolvers() as $resolver) {
            self::assertInstanceOf(MutationResolverInterface::class, $resolver);
        }
    }

    /**
     * @return list<MutationResolverInterface>
     */
    private function createResolvers(): array
    {
        return array_merge(
            $this->createSignUpResolvers(),
            $this->createSignInResolvers(),
            $this->createRegistrationResolvers()
        );
    }

    /**
     * @return list<MutationResolverInterface>
     */
    private function createSignUpResolvers(): array
    {
        return [
            new PasskeySignUpOptionsAuthMutationResolver(
                $this->createValidator(),
                $this->createCommandBus(),
                $this->createAuthPayloadFactory()
            ),
            new PasskeySignUpCompleteAuthMutationResolver(
                $this->createValidator(),
                $this->createCommandBus(),
                $this->createAuthPayloadFactory(),
                $this->createRequestContextResolver()
            ),
        ];
    }

    /**
     * @return list<MutationResolverInterface>
     */
    private function createSignInResolvers(): array
    {
        return [
            new PasskeySignInOptionsAuthMutationResolver(
                $this->createValidator(),
                $this->createCommandBus(),
                $this->createAuthPayloadFactory()
            ),
            new PasskeySignInCompleteAuthMutationResolver(
                $this->createValidator(),
                $this->createCommandBus(),
                $this->createAuthPayloadFactory(),
                $this->createRequestContextResolver()
            ),
        ];
    }

    /**
     * @return list<MutationResolverInterface>
     */
    private function createRegistrationResolvers(): array
    {
        return [
            new PasskeyRegistrationOptionsAuthMutationResolver(
                $this->createCommandBus(),
                $this->createAuthPayloadFactory(),
                $this->createCurrentUserIdentityResolver()
            ),
            new PasskeyRegistrationCompleteAuthMutationResolver(
                $this->createValidator(),
                $this->createCommandBus(),
                $this->createAuthPayloadFactory(),
                $this->createCurrentUserIdentityResolver()
            ),
        ];
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

    private function createRequestContextResolver(): HttpRequestContextResolverInterface
    {
        return $this->createMock(HttpRequestContextResolverInterface::class);
    }

    private function createCurrentUserIdentityResolver(): CurrentUserIdentityResolver
    {
        return new CurrentUserIdentityResolver(
            $this->createMock(Security::class)
        );
    }
}
