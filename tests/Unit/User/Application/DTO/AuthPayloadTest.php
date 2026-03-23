<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\DTO;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\AuthPayload;

final class AuthPayloadTest extends UnitTestCase
{
    public function testCreateSuccessPayloadReturnsSuccessfulPayload(): void
    {
        $payload = AuthPayload::createSuccessPayload();

        $this->assertSame('auth-success', $payload->getId());
        $this->assertTrue($payload->isSuccess());
    }
}
