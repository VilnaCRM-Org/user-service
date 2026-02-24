<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\UpdateUserCommandFactory;
use App\User\Application\Factory\UpdateUserCommandFactoryInterface;
use App\User\Application\Resolver\UserUpdateMutationResolver;
use App\User\Application\Transformer\UpdateUserMutationInputTransformer;
use App\User\Application\Validator\MutationInputValidator;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\ValueObject\UserUpdate;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class UserUpdateMutationResolverTest extends UnitTestCase
{
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;
    private UpdateUserCommandFactoryInterface $updateUserCommandFactory;
    private CommandBusInterface $commandBus;
    private MutationInputValidator $validator;
    private UpdateUserMutationInputTransformer $transformer;
    private UpdateUserCommandFactoryInterface $mockUpdateUserCommandFactory;
    private UserUpdateMutationResolver $resolver;
    private Security $security;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new UuidFactory());
        $this->updateUserCommandFactory = new UpdateUserCommandFactory();
        $this->initializeMocks();
        $this->resolver = new UserUpdateMutationResolver(
            $this->commandBus,
            $this->validator,
            $this->transformer,
            $this->mockUpdateUserCommandFactory,
            $this->security
        );
    }

    public function testInvoke(): void
    {
        [$user, $email, $initials, $password] = $this->createTestUserData();
        $this->prepareExpectations($user, $email, $initials, $password);
        $input = $this->createFullInput($email, $initials, $password);

        $this->assertSame(
            $user,
            $this->resolver->__invoke($user, ['args' => ['input' => $input]]),
        );
    }

    public function testInvokeWithoutEmailUsesExistingEmail(): void
    {
        [$user, $email, $initials, $password] = $this->createTestUserData();
        $this->prepareExpectations($user, $email, $initials, $password);

        $input = [
            'initials' => $initials,
            'newPassword' => $password,
            'password' => $password,
        ];

        $this->assertSame(
            $user,
            $this->resolver->__invoke($user, ['args' => ['input' => $input]]),
        );
    }

    public function testInvokeWithoutInitialsUsesExistingInitials(): void
    {
        [$user, $email, $initials, $password] = $this->createTestUserData();
        $this->prepareExpectations($user, $email, $initials, $password);

        $input = [
            'email' => $email,
            'newPassword' => $password,
            'password' => $password,
        ];

        $this->assertSame(
            $user,
            $this->resolver->__invoke($user, ['args' => ['input' => $input]]),
        );
    }

    public function testInvokeWithoutNewPasswordFallsBackToPassword(): void
    {
        [$user, $email, $initials, $password] = $this->createTestUserData();
        $this->prepareExpectations($user, $email, $initials, $password);

        $input = [
            'email' => $email,
            'initials' => $initials,
            'password' => $password,
        ];

        $this->assertSame(
            $user,
            $this->resolver->__invoke($user, ['args' => ['input' => $input]]),
        );
    }

    public function testInvokeWithNullSecurityToken(): void
    {
        [$user, $email, $initials, $password] = $this->createTestUserData();
        $this->security->expects($this->once())->method('getToken')->willReturn(null);
        $this->prepareEmptySessionExpectations($user, $email, $initials, $password);
        $input = $this->createFullInput($email, $initials, $password);

        $this->assertSame(
            $user,
            $this->resolver->__invoke($user, ['args' => ['input' => $input]]),
        );
    }

    public function testInvokeWithNonStringSessionId(): void
    {
        [$user, $email, $initials, $password] = $this->createTestUserData();
        $token = $this->createMock(TokenInterface::class);
        $token->method('getAttribute')->with('sid')->willReturn(null);
        $this->security->expects($this->once())->method('getToken')->willReturn($token);
        $this->prepareEmptySessionExpectations($user, $email, $initials, $password);
        $input = $this->createFullInput($email, $initials, $password);

        $this->assertSame(
            $user,
            $this->resolver->__invoke($user, ['args' => ['input' => $input]]),
        );
    }

    private function initializeMocks(): void
    {
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->validator = $this->createMock(MutationInputValidator::class);
        $this->transformer = $this->createMock(UpdateUserMutationInputTransformer::class);
        $this->mockUpdateUserCommandFactory = $this->createMock(
            UpdateUserCommandFactoryInterface::class
        );
        $this->security = $this->createMock(Security::class);
    }

    /**
     * @return array{UserInterface, string, string, string}
     */
    private function createTestUserData(): array
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $user = $this->userFactory->create(
            $email,
            $initials,
            $password,
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );

        return [$user, $email, $initials, $password];
    }

    /**
     * @return array<string, string>
     */
    private function createFullInput(
        string $email,
        string $initials,
        string $password
    ): array {
        return [
            'email' => $email,
            'initials' => $initials,
            'newPassword' => $password,
            'password' => $password,
        ];
    }

    private function prepareExpectations(
        UserInterface $user,
        string $email,
        string $initials,
        string $password
    ): void {
        $currentSessionId = $this->faker->uuid();
        $this->prepareSecurityTokenExpectation($currentSessionId);
        $updateData = new UserUpdate($email, $initials, $password, $password);
        $this->transformer->expects($this->once())->method('transform');
        $this->validator->expects($this->once())->method('validate');
        $this->prepareCommandExpectations($user, $updateData, $currentSessionId);
    }

    private function prepareSecurityTokenExpectation(string $sessionId): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getAttribute')
            ->with('sid')
            ->willReturn($sessionId);
        $this->security->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
    }

    private function prepareCommandExpectations(
        UserInterface $user,
        UserUpdate $updateData,
        string $sessionId
    ): void {
        $command = $this->updateUserCommandFactory->create($user, $updateData, $sessionId);
        $this->mockUpdateUserCommandFactory->expects($this->once())
            ->method('create')
            ->with($user, $updateData, $sessionId)
            ->willReturn($command);
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);
    }

    private function prepareEmptySessionExpectations(
        UserInterface $user,
        string $email,
        string $initials,
        string $password
    ): void {
        $updateData = new UserUpdate($email, $initials, $password, $password);
        $this->transformer->expects($this->once())->method('transform');
        $this->validator->expects($this->once())->method('validate');
        $this->prepareCommandExpectations($user, $updateData, '');
    }
}
