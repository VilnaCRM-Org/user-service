<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Application\CommandHandler;

use App\Tests\Integration\IntegrationTestCase;
use App\User\Application\Command\ConfirmPasswordResetCommand;
use App\User\Application\CommandHandler\ConfirmPasswordResetCommandHandler;
use App\User\Domain\Entity\PasswordResetToken;
use App\User\Domain\Exception\TokenExpiredException;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final class ConfirmPasswordResetCommandHandlerTest extends IntegrationTestCase
{
    private ConfirmPasswordResetCommandHandler $commandHandler;
    private UserRepositoryInterface $userRepository;
    private PasswordResetTokenRepositoryInterface $passwordResetTokenRepository;
    private UserFactoryInterface $userFactory;
    private UuidFactory $uuidFactory;
    private PasswordHasherFactoryInterface $hasherFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandHandler = $this->container->get(ConfirmPasswordResetCommandHandler::class);
        $this->userRepository = $this->container->get(UserRepositoryInterface::class);
        $this->passwordResetTokenRepository = $this->container->get(PasswordResetTokenRepositoryInterface::class);
        $this->userFactory = $this->container->get(UserFactoryInterface::class);
        $this->uuidFactory = $this->container->get(UuidFactory::class);
        $this->hasherFactory = $this->container->get(PasswordHasherFactoryInterface::class);
    }

    public function testInvokeWithValidToken(): void
    {
        // Create a user
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $oldPassword = $this->faker->password();
        $newPassword = $this->faker->password();
        $userId = (string) $this->uuidFactory->create();

        $user = $this->userFactory->create($email, $initials, $oldPassword, $userId);
        $this->userRepository->save($user);

        // Create password reset token
        $tokenValue = $this->faker->uuid();
        $token = new PasswordResetToken($tokenValue, $userId);
        $this->passwordResetTokenRepository->save($token);

        // Get the original password hash for comparison
        $originalPasswordHash = $user->getPassword();

        // Execute the command
        $command = new ConfirmPasswordResetCommand($token, $newPassword);
        $this->commandHandler->__invoke($command);

        // Verify password was changed
        $updatedUser = $this->userRepository->find($userId);
        $this->assertNotNull($updatedUser);
        $this->assertNotEquals($originalPasswordHash, $updatedUser->getPassword());

        // Verify new password hash is correct
        $hasher = $this->hasherFactory->getPasswordHasher($updatedUser::class);
        $this->assertTrue($hasher->verify($updatedUser->getPassword(), $newPassword));

        // Verify token was deleted
        $deletedToken = $this->passwordResetTokenRepository->find($tokenValue);
        $this->assertNull($deletedToken);
    }

    public function testInvokeWithExpiredToken(): void
    {
        // Create a user
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $userId = (string) $this->uuidFactory->create();

        $user = $this->userFactory->create($email, $initials, $password, $userId);
        $this->userRepository->save($user);

        // Create expired password reset token (expires in 1 second)
        $tokenValue = $this->faker->uuid();
        $token = new PasswordResetToken($tokenValue, $userId, 1);
        $this->passwordResetTokenRepository->save($token);

        // Wait for token to expire
        sleep(2);

        // Execute the command and expect exception
        $this->expectException(TokenExpiredException::class);

        $newPassword = $this->faker->password();
        $command = new ConfirmPasswordResetCommand($token, $newPassword);
        $this->commandHandler->__invoke($command);
    }

    public function testInvokeUpdatesUserPassword(): void
    {
        // Create a user
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $oldPassword = 'oldPassword123';
        $newPassword = 'newPassword456';
        $userId = (string) $this->uuidFactory->create();

        $user = $this->userFactory->create($email, $initials, $oldPassword, $userId);
        
        // Set a hashed password
        $hasher = $this->hasherFactory->getPasswordHasher($user::class);
        $hashedOldPassword = $hasher->hash($oldPassword);
        $user->setPassword($hashedOldPassword);
        $this->userRepository->save($user);

        // Create password reset token
        $tokenValue = $this->faker->uuid();
        $token = new PasswordResetToken($tokenValue, $userId);
        $this->passwordResetTokenRepository->save($token);

        // Execute the command
        $command = new ConfirmPasswordResetCommand($token, $newPassword);
        $this->commandHandler->__invoke($command);

        // Verify password was changed
        $updatedUser = $this->userRepository->find($userId);
        $this->assertNotNull($updatedUser);
        
        // Verify old password no longer works
        $this->assertFalse($hasher->verify($updatedUser->getPassword(), $oldPassword));
        
        // Verify new password works
        $this->assertTrue($hasher->verify($updatedUser->getPassword(), $newPassword));
    }
}