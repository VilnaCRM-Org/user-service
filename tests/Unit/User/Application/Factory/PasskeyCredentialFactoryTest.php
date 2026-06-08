<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Factory;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\VerifiedPasskeyCredential;
use App\User\Application\Factory\IdFactoryInterface;
use App\User\Application\Factory\PasskeyCredentialFactory;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;

final class PasskeyCredentialFactoryTest extends UnitTestCase
{
    private IdFactoryInterface&MockObject $idFactory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->idFactory = $this->createMock(IdFactoryInterface::class);
    }

    public function testCreateTrimsLabel(): void
    {
        $passkeyId = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $label = $this->faker->words(2, true);
        $verified = new VerifiedPasskeyCredential($this->faker->uuid(), $this->faker->sha256());
        $createdAt = new DateTimeImmutable();

        $this->idFactory->expects($this->once())->method('create')->willReturn($passkeyId);

        $credential = $this->createFactory()->create(
            $userId,
            $verified,
            sprintf(' %s ', $label),
            $createdAt
        );

        self::assertSame($passkeyId, $credential->getId());
        self::assertSame($userId, $credential->getUserId());
        self::assertSame($verified->getCredentialId(), $credential->getCredentialId());
        self::assertSame($verified->getCredentialRecord(), $credential->getCredentialRecord());
        self::assertSame($label, $credential->getLabel());
        self::assertSame($createdAt, $credential->getCreatedAt());
    }

    public function testCreateUsesDefaultLabelForBlankLabel(): void
    {
        $this->idFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->faker->uuid());

        $credential = $this->createFactory()->create(
            $this->faker->uuid(),
            new VerifiedPasskeyCredential($this->faker->uuid(), $this->faker->sha256()),
            '   ',
            new DateTimeImmutable()
        );

        self::assertSame('Passkey', $credential->getLabel());
    }

    private function createFactory(): PasskeyCredentialFactory
    {
        return new PasskeyCredentialFactory($this->idFactory);
    }
}
