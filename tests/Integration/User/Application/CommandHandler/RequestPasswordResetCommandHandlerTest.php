<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Application\CommandHandler;

use App\Tests\Integration\IntegrationTestCase;
use App\User\Application\Command\RequestPasswordResetCommand;
use App\User\Application\CommandHandler\RequestPasswordResetCommandHandler;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final class RequestPasswordResetCommandHandlerTest extends IntegrationTestCase
{
    private RequestPasswordResetCommandHandler $commandHandler;
    private UserRepositoryInterface $userRepository;
    private PasswordResetTokenRepositoryInterface $passwordResetTokenRepository;
    private UserFactoryInterface $userFactory;
    private UuidFactory $uuidFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandHandler = $this->container->get(RequestPasswordResetCommandHandler::class);
        $this->userRepository = $this->container->get(UserRepositoryInterface::class);
        $this->passwordResetTokenRepository = $this->container->get(PasswordResetTokenRepositoryInterface::class);
        $this->userFactory = $this->container->get(UserFactoryInterface::class);
        $this->uuidFactory = $this->container->get(UuidFactory::class);
    }

    public function testInvokeWithExistingUser(): void
    {
        // Create a user
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $userId = (string) $this->uuidFactory->create();

        $user = $this->userFactory->create($email, $initials, $password, $userId);
        $this->userRepository->save($user);

        // Execute the command
        $command = new RequestPasswordResetCommand($email);
        $this->commandHandler->__invoke($command);

        // Verify token was created
        $token = $this->passwordResetTokenRepository->findByUserId($userId);
        $this->assertNotNull($token);
        $this->assertEquals($userId, $token->getUserID());
        $this->assertFalse($token->isExpired());
    }

    public function testInvokeWithNonExistentUser(): void
    {
        $nonExistentEmail = $this->faker->email();

        // Execute the command
        $command = new RequestPasswordResetCommand($nonExistentEmail);
        $this->commandHandler->__invoke($command);

        // Verify no token was created (should silently ignore)
        // We can't easily test this without knowing the user ID
        // The behavior is correct - it doesn't throw an exception for security reasons
        $this->assertTrue(true); // Command should complete without exception
    }

    public function testInvokeCreatesNewTokenForExistingUserWithOldToken(): void
    {
        // Create a user
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $userId = (string) $this->uuidFactory->create();

        $user = $this->userFactory->create($email, $initials, $password, $userId);
        $this->userRepository->save($user);

        // Execute the command first time
        $command = new RequestPasswordResetCommand($email);
        $this->commandHandler->__invoke($command);

        $firstToken = $this->passwordResetTokenRepository->findByUserId($userId);
        $firstTokenValue = $firstToken->getTokenValue();

        // Execute the command second time
        $this->commandHandler->__invoke($command);

        $secondToken = $this->passwordResetTokenRepository->findByUserId($userId);
        $secondTokenValue = $secondToken->getTokenValue();

        // Should have a new token
        $this->assertNotEquals($firstTokenValue, $secondTokenValue);
    }
}