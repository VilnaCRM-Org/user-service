<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Command;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\ConfirmTwoFactorCommandResponse;

final class ConfirmTwoFactorCommandResponseTest extends UnitTestCase
{
    public function testGetRecoveryCodes(): void
    {
        $codes = ['ABCD-1234', 'EFGH-5678', 'IJKL-9012'];
        $response = new ConfirmTwoFactorCommandResponse($codes);

        $this->assertSame($codes, $response->getRecoveryCodes());
    }

    public function testEmptyRecoveryCodes(): void
    {
        $response = new ConfirmTwoFactorCommandResponse([]);

        $this->assertSame([], $response->getRecoveryCodes());
    }
}
