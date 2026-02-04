<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\ConfirmationToken;

final class ConfirmationTokenConstantsTest extends UnitTestCase
{
    public function testSendEmailAttemptsConfigurationConstant(): void
    {
        $reflection = new \ReflectionClass(ConfirmationToken::class);
        $constant = $reflection->getConstant('SEND_EMAIL_ATTEMPTS_TIME_IN_MINUTES');

        $expected = [0 => 1, 1 => 1, 2 => 3, 3 => 4, 4 => 1440];

        $this->assertIsArray($constant);
        $this->assertCount(5, $constant);
        $this->assertSame($expected, $constant);

        foreach (array_keys($expected) as $key) {
            $this->assertArrayHasKey($key, $constant);
        }
    }
}
