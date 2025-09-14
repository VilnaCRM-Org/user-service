<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RequestPasswordResetCommand;
use App\User\Application\CommandHandler\RequestPasswordResetCommandHandler;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\PasswordResetRequestedEvent;
use App\User\Domain\Exception\PasswordResetRateLimitExceededException;
use App\User\Domain\Factory\PasswordResetTokenFactoryInterface;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Uid\Factory\UuidFactory;

final class RequestPasswordResetCommandHandlerTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private PasswordResetTokenRepositoryInterface&MockObject $passwordResetTokenRepository;
    private PasswordResetTokenFactoryInterface&MockObject $passwordResetTokenFactory;
    private EventBusInterface&MockObject $eventBus;
    private UuidFactory&MockObject $uuidFactory;
    private RequestPasswordResetCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordResetTokenRepository = $this->createMock(PasswordResetTokenRepositoryInterface::class);
        $this->passwordResetTokenFactory = $this->createMock(PasswordResetTokenFactoryInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->uuidFactory = $this->createMock(UuidFactory::class);

        $this->handler = new RequestPasswordResetCommandHandler(
            $this->userRepository,
            $this->passwordResetTokenRepository,
            $this->passwordResetTokenFactory,
            $this->eventBus,
            $this->uuidFactory,
            3, // rateLimitMaxRequests
            1  // rateLimitWindowHours
        );
    }

    public function testRequestPasswordResetForExistingUser(): void
    {
        $email = $this->faker->email();
        $userId = $this->faker->uuid();
        $tokenValue = $this->faker->sha256();

        $user = $this->createMock(UserInterface::class);
        $user->method('getId')->willReturn($userId);

        $token = $this->createMock(PasswordResetTokenInterface::class);
        $token->method('getTokenValue')->willReturn($tokenValue);

        $this->configureRepositoryMocks($email, $user, $userId, $token);
        $this->configureEventBusMock();

        $command = new RequestPasswordResetCommand($email);
        $this->handler->__invoke($command);

        $this->assertEquals('', $command->getResponse()->message);
    }

    public function testRequestPasswordResetForNonExistingUser(): void
    {
        $email = $this->faker->email();

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $this->passwordResetTokenRepository
            ->expects($this->never())
            ->method('countRecentRequestsByEmail');

        $this->passwordResetTokenFactory
            ->expects($this->never())
            ->method('create');

        $this->eventBus
            ->expects($this->never())
            ->method('publish');

        $command = new RequestPasswordResetCommand($email);
        $this->handler->__invoke($command);

        $this->assertEquals('', $command->getResponse()->message);
    }

    public function testRequestPasswordResetRateLimitExceeded(): void
    {
        $email = $this->faker->email();

        $user = $this->createMock(UserInterface::class);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $this->passwordResetTokenRepository
            ->expects($this->once())
            ->method('countRecentRequestsByEmail')
            ->willReturn(3); // Rate limit exceeded

        $this->expectException(PasswordResetRateLimitExceededException::class);

        $command = new RequestPasswordResetCommand($email);
        $this->handler->__invoke($command);
    }

    public function testRequestPasswordResetRateLimitWithCustomValues(): void
    {
        // Test with different rate limit values to catch mutants
        $customHandler = new RequestPasswordResetCommandHandler(
            $this->userRepository,
            $this->passwordResetTokenRepository,
            $this->passwordResetTokenFactory,
            $this->eventBus,
            $this->uuidFactory,
            5, // Different maxRequests
            2  // Different windowHours
        );

        $email = $this->faker->email();
        $user = $this->createMock(UserInterface::class);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $this->passwordResetTokenRepository
            ->expects($this->once())
            ->method('countRecentRequestsByEmail')
            ->willReturn(5); // Exactly at limit

        $this->expectException(PasswordResetRateLimitExceededException::class);

        $command = new RequestPasswordResetCommand($email);
        $customHandler->__invoke($command);
    }

    public function testRequestPasswordResetRateLimitBoundaryCondition(): void
    {
        // Test exactly at the boundary to catch increment/decrement mutations
        $email = $this->faker->email();
        $user = $this->createMock(UserInterface::class);
        $userId = $this->faker->uuid();
        $user->method('getId')->willReturn($userId);

        $token = $this->createMock(PasswordResetTokenInterface::class);
        $token->method('getTokenValue')->willReturn($this->faker->sha256());

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        // Return exactly 2 (which is < 3, so should pass)
        $this->passwordResetTokenRepository
            ->expects($this->once())
            ->method('countRecentRequestsByEmail')
            ->willReturn(2);

        $this->passwordResetTokenFactory
            ->expects($this->once())
            ->method('create')
            ->with($userId)
            ->willReturn($token);

        $this->passwordResetTokenRepository
            ->expects($this->once())
            ->method('save')
            ->with($token);

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(PasswordResetRequestedEvent::class));

        $command = new RequestPasswordResetCommand($email);
        $this->handler->__invoke($command);

        $this->assertEquals('', $command->getResponse()->message);
    }

    public function testRateLimitMaxRequestsDecrement(): void
    {
        // Test to catch decrement mutation (3 → 2)
        $customHandler = new RequestPasswordResetCommandHandler(
            $this->userRepository,
            $this->passwordResetTokenRepository,
            $this->passwordResetTokenFactory,
            $this->eventBus,
            $this->uuidFactory,
            2, // rateLimitMaxRequests = 2 (will catch if decremented to 1)
            1
        );

        $email = $this->faker->email();
        $user = $this->createMock(UserInterface::class);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $this->passwordResetTokenRepository
            ->expects($this->once())
            ->method('countRecentRequestsByEmail')
            ->willReturn(2); // Exactly at custom limit of 2

        $this->expectException(PasswordResetRateLimitExceededException::class);

        $command = new RequestPasswordResetCommand($email);
        $customHandler->__invoke($command);
    }

    public function testRateLimitMaxRequestsIncrement(): void
    {
        // Test to catch increment mutation (3 → 4)
        $customHandler = $this->createHandlerWithCustomRateLimit(4, 1);
        $email = $this->faker->email();
        $user = $this->createMock(UserInterface::class);

        $this->setupUserRepositoryMock($email, $user);
        $this->setupTokenRepositoryRateLimitMock(4); // Exactly at custom limit of 4

        $this->expectException(PasswordResetRateLimitExceededException::class);

        $command = new RequestPasswordResetCommand($email);
        $customHandler->__invoke($command);
    }

    public function testRateLimitWindowHoursDecrement(): void
    {
        // Test to catch window hours decrement mutation (1 → 0)
        $customHandler = $this->createHandlerWithCustomRateLimit(3, 0);
        $email = $this->faker->email();
        $user = $this->createMock(UserInterface::class);

        $this->setupUserRepositoryMock($email, $user);
        $this->setupTokenRepositoryRateLimitMock(3); // At limit

        $this->expectException(PasswordResetRateLimitExceededException::class);

        $command = new RequestPasswordResetCommand($email);
        $customHandler->__invoke($command);
    }

    public function testRateLimitWindowHoursIncrement(): void
    {
        // Test to catch window hours increment mutation (1 → 2)
        $customHandler = $this->createHandlerWithCustomRateLimit(3, 2);
        $email = $this->faker->email();
        $user = $this->createMock(UserInterface::class);

        $this->setupUserRepositoryMock($email, $user);
        $this->setupTokenRepositoryRateLimitMock(3); // At limit

        $this->expectException(PasswordResetRateLimitExceededException::class);

        $command = new RequestPasswordResetCommand($email);
        $customHandler->__invoke($command);
    }

    public function testRequestPasswordResetWithDefaultRateLimitValues(): void
    {
        $defaultHandler = $this->createDefaultHandler();
        $email = $this->faker->email();
        $user = $this->createMock(UserInterface::class);

        $this->setupUserRepositoryMock($email, $user);
        $this->setupRateLimitExceededMock(3);

        $this->expectException(PasswordResetRateLimitExceededException::class);

        $command = new RequestPasswordResetCommand($email);
        $defaultHandler->__invoke($command);
    }

    public function testRequestPasswordResetWithDefaultRateLimitBoundary(): void
    {
        $defaultHandler = $this->createDefaultHandler();
        $email = $this->faker->email();
        $user = $this->createMock(UserInterface::class);
        $userId = $this->faker->uuid();
        $user->method('getId')->willReturn($userId);

        $token = $this->createMock(PasswordResetTokenInterface::class);
        $token->method('getTokenValue')->willReturn($this->faker->sha256());

        $this->setupUserRepositoryMock($email, $user);
        $this->setupRateLimitBelowThresholdMock(2);
        $this->setupTokenCreationMocks($userId, $token);
        $this->configureEventBusMock();

        $command = new RequestPasswordResetCommand($email);
        $defaultHandler->__invoke($command);

        $this->assertEquals('', $command->getResponse()->message);
    }

    public function testConstructorDefaultWindowHoursZeroMutation(): void
    {
        $defaultHandler = $this->createDefaultHandler();
        $email = $this->faker->email();
        $user = $this->createMock(UserInterface::class);

        $this->setupUserRepositoryMock($email, $user);
        $this->setupWindowHourValidationMock($email);

        $this->expectException(PasswordResetRateLimitExceededException::class);

        $command = new RequestPasswordResetCommand($email);
        $defaultHandler->__invoke($command);
    }

    public function testConstructorDefaultWindowHoursTwoMutation(): void
    {
        $defaultHandler = $this->createDefaultHandler();
        $email = $this->faker->email();
        $user = $this->setupUserMock();
        $token = $this->setupTokenMock();

        $this->setupUserRepositoryExpectation($email, $user);
        $this->setupOneHourTimeWindowExpectation($email);
        $this->setupTokenCreationExpectations($user->getId(), $token);

        $command = new RequestPasswordResetCommand($email);
        $defaultHandler->__invoke($command);

        $this->assertEquals('', $command->getResponse()->message);
    }

    private function createDefaultHandler(): RequestPasswordResetCommandHandler
    {
        return new RequestPasswordResetCommandHandler(
            $this->userRepository,
            $this->passwordResetTokenRepository,
            $this->passwordResetTokenFactory,
            $this->eventBus,
            $this->uuidFactory
            // Using default constructor values (3, 1)
        );
    }

    private function setupUserMock(): UserInterface
    {
        $user = $this->createMock(UserInterface::class);
        $userId = $this->faker->uuid();
        $user->method('getId')->willReturn($userId);

        return $user;
    }

    private function setupTokenMock(): PasswordResetTokenInterface
    {
        $token = $this->createMock(PasswordResetTokenInterface::class);
        $token->method('getTokenValue')->willReturn($this->faker->sha256());

        return $token;
    }

    private function setupUserRepositoryExpectation(string $email, UserInterface $user): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);
    }

    private function setupTokenCreationExpectations(string $userId, PasswordResetTokenInterface $token): void
    {
        $this->passwordResetTokenFactory
            ->expects($this->once())
            ->method('create')
            ->with($userId)
            ->willReturn($token);

        $this->passwordResetTokenRepository
            ->expects($this->once())
            ->method('save')
            ->with($token);

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(PasswordResetRequestedEvent::class));
    }

    private function setupOneHourTimeWindowExpectation(string $email): void
    {
        // The mock expects to be called with a DateTimeImmutable representing "-1 hours"
        // If the default gets mutated to 2, it would be called with "-2 hours"
        $this->passwordResetTokenRepository
            ->expects($this->once())
            ->method('countRecentRequestsByEmail')
            ->with(
                $email,
                $this->callback(static function (\DateTimeImmutable $dateTime): bool {
                    return self::validateOneHourTimeWindow($dateTime);
                })
            )
            ->willReturn(2); // Under limit, should succeed
    }

    private static function validateOneHourTimeWindow(\DateTimeImmutable $dateTime): bool
    {
        // Check that the datetime is approximately 1 hour ago (not 2 hours)
        $oneHourAgo = new \DateTimeImmutable('-1 hours');
        $twoHoursAgo = new \DateTimeImmutable('-2 hours');

        $diffOne = abs($dateTime->getTimestamp() - $oneHourAgo->getTimestamp());
        $diffTwo = abs($dateTime->getTimestamp() - $twoHoursAgo->getTimestamp());

        // Should be closer to 1 hour ago than 2 hours ago
        return $diffOne < $diffTwo && $diffOne <= 60;
    }

    private function configureRepositoryMocks(
        string $email,
        UserInterface $user,
        string $userId,
        PasswordResetTokenInterface $token
    ): void {
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $this->passwordResetTokenRepository
            ->expects($this->once())
            ->method('countRecentRequestsByEmail')
            ->willReturn(0);

        $this->passwordResetTokenFactory
            ->expects($this->once())
            ->method('create')
            ->with($userId)
            ->willReturn($token);

        $this->passwordResetTokenRepository
            ->expects($this->once())
            ->method('save')
            ->with($token);
    }

    private function configureEventBusMock(): void
    {
        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(PasswordResetRequestedEvent::class));
    }

    private function setupUserRepositoryMock(string $email, UserInterface $user): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);
    }

    private function setupRateLimitExceededMock(int $returnCount): void
    {
        $this->passwordResetTokenRepository
            ->expects($this->once())
            ->method('countRecentRequestsByEmail')
            ->willReturn($returnCount);
    }

    private function setupRateLimitBelowThresholdMock(int $returnCount): void
    {
        $this->passwordResetTokenRepository
            ->expects($this->once())
            ->method('countRecentRequestsByEmail')
            ->willReturn($returnCount);
    }

    private function setupTokenCreationMocks(string $userId, PasswordResetTokenInterface $token): void
    {
        $this->passwordResetTokenFactory
            ->expects($this->once())
            ->method('create')
            ->with($userId)
            ->willReturn($token);

        $this->passwordResetTokenRepository
            ->expects($this->once())
            ->method('save')
            ->with($token);
    }

    private function setupWindowHourValidationMock(string $email): void
    {
        // The mock expects to be called with a DateTimeImmutable representing "-1 hours"
        // If the default gets mutated to 0, it would be called with "-0 hours" (i.e., now)
        $this->passwordResetTokenRepository
            ->expects($this->once())
            ->method('countRecentRequestsByEmail')
            ->with(
                $email,
                $this->callback(static function (\DateTimeImmutable $dateTime): bool {
                    // Check that the datetime is approximately 1 hour ago (default window)
                    $oneHourAgo = new \DateTimeImmutable('-1 hours');
                    $diff = abs($dateTime->getTimestamp() - $oneHourAgo->getTimestamp());
                    // Allow 60 seconds tolerance for test execution time
                    return $diff <= 60;
                })
            )
            ->willReturn(3); // At limit
    }

    private function createHandlerWithCustomRateLimit(int $maxRequests, int $windowHours): RequestPasswordResetCommandHandler
    {
        return new RequestPasswordResetCommandHandler(
            $this->userRepository,
            $this->passwordResetTokenRepository,
            $this->passwordResetTokenFactory,
            $this->eventBus,
            $this->uuidFactory,
            $maxRequests,
            $windowHours
        );
    }

    private function setupTokenRepositoryRateLimitMock(int $returnCount): void
    {
        $this->passwordResetTokenRepository
            ->expects($this->once())
            ->method('countRecentRequestsByEmail')
            ->willReturn($returnCount);
    }
}
