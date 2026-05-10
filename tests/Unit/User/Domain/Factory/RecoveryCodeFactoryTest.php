<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Factory;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Factory\RecoveryCodeFactory;

final class RecoveryCodeFactoryTest extends UnitTestCase
{
    private RecoveryCodeFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new RecoveryCodeFactory();
    }

    public function testCreateReturnsRecoveryCode(): void
    {
        $id = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $plainCode = strtoupper($this->faker->bothify('????-????'));

        $result = $this->factory->create($id, $userId, $plainCode);

        $this->assertInstanceOf(RecoveryCode::class, $result);
        $this->assertSame($id, $result->getId());
        $this->assertSame($userId, $result->getUserId());
        $this->assertTrue($result->matchesCode($plainCode));
        $this->assertFalse($result->isUsed());
    }
}
