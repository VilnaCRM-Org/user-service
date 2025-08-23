<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Factory;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Factory\PasswordResetTokenFactory;

final class PasswordResetTokenFactoryTest extends UnitTestCase
{
    private PasswordResetTokenFactory $factory;
    private int $tokenLength;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenLength = $this->faker->numberBetween(16, 64);
        $this->factory = new PasswordResetTokenFactory($this->tokenLength);
    }

    public function testCreate(): void
    {
        $userID = $this->faker->uuid();
        
        $token = $this->factory->create($userID);
        
        $this->assertInstanceOf(PasswordResetTokenInterface::class, $token);
        $this->assertEquals($userID, $token->getUserID());
        $this->assertNotEmpty($token->getTokenValue());
    }

    public function testCreateGeneratesUniqueTokens(): void
    {
        $userID = $this->faker->uuid();
        
        $token1 = $this->factory->create($userID);
        $token2 = $this->factory->create($userID);
        
        $this->assertNotEquals($token1->getTokenValue(), $token2->getTokenValue());
    }

    public function testCreateGeneratesHexToken(): void
    {
        $userID = $this->faker->uuid();
        
        $token = $this->factory->create($userID);
        
        $this->assertTrue(ctype_xdigit($token->getTokenValue()));
    }

    public function testCreateTokenLengthCorrespondsToHexLength(): void
    {
        $userID = $this->faker->uuid();
        
        $token = $this->factory->create($userID);
        
        // bin2hex doubles the length because each byte becomes 2 hex characters
        $expectedHexLength = $this->tokenLength * 2;
        $this->assertEquals($expectedHexLength, strlen($token->getTokenValue()));
    }

    public function testCreateWithDifferentTokenLengths(): void
    {
        $userID = $this->faker->uuid();
        
        $factory8 = new PasswordResetTokenFactory(8);
        $factory16 = new PasswordResetTokenFactory(16);
        $factory32 = new PasswordResetTokenFactory(32);
        
        $token8 = $factory8->create($userID);
        $token16 = $factory16->create($userID);
        $token32 = $factory32->create($userID);
        
        $this->assertEquals(16, strlen($token8->getTokenValue())); // 8 * 2
        $this->assertEquals(32, strlen($token16->getTokenValue())); // 16 * 2
        $this->assertEquals(64, strlen($token32->getTokenValue())); // 32 * 2
    }

    public function testCreateSetsCorrectDefaults(): void
    {
        $userID = $this->faker->uuid();
        
        $token = $this->factory->create($userID);
        
        $this->assertInstanceOf(\DateTimeImmutable::class, $token->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $token->getExpiresAt());
        $this->assertFalse($token->isExpired());
    }
}