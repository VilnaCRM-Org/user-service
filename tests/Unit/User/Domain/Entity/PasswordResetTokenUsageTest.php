<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\PasswordResetToken;
use App\User\Domain\Factory\PasswordResetTokenFactory;
use App\User\Domain\Factory\PasswordResetTokenFactoryInterface;

final class PasswordResetTokenUsageTest extends UnitTestCase
{
    private PasswordResetToken $passwordResetToken;
    private PasswordResetTokenFactoryInterface $passwordResetTokenFactory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->passwordResetTokenFactory = new PasswordResetTokenFactory(
            32,
            1
        );
        $this->passwordResetToken = $this->passwordResetTokenFactory->create($this->faker->uuid());
    }

    public function testCreatedTokenIsNotUsed(): void
    {
        $this->assertFalse($this->passwordResetToken->isUsed());
    }

    public function testMarkAsUsed(): void
    {
        $this->assertFalse($this->passwordResetToken->isUsed());

        $this->passwordResetToken->markAsUsed();

        $this->assertTrue($this->passwordResetToken->isUsed());
    }

    public function testResetUsage(): void
    {
        $token = new PasswordResetToken(
            'test-token',
            $this->faker->uuid(),
            new \DateTimeImmutable('+1 hour'),
            new \DateTimeImmutable()
        );

        $this->assertFalse($token->isUsed());

        $token->markAsUsed();
        $this->assertTrue($token->isUsed());

        $token->resetUsage();
        $this->assertFalse($token->isUsed());
    }

    public function testManualCreationStartsUnused(): void
    {
        $token = new PasswordResetToken(
            $this->faker->sha256(),
            $this->faker->uuid(),
            new \DateTimeImmutable('+1 hour'),
            new \DateTimeImmutable()
        );

        $this->assertFalse($token->isUsed());
    }

    public function testNewTokenIsNotUsedByDefault(): void
    {
        $token = new PasswordResetToken(
            'new-token',
            $this->faker->uuid(),
            new \DateTimeImmutable('+1 hour'),
            new \DateTimeImmutable()
        );

        $this->assertFalse($token->isUsed());
        $this->assertNotTrue($token->isUsed());

        $reflection = new \ReflectionClass($token);
        $property = $reflection->getProperty('isUsed');
        $this->assertFalse(
            $property->getValue($token)
        );
        $this->assertSame(
            false,
            $property->getValue($token)
        );
    }
}
