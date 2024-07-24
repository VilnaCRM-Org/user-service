<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\DTO;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\RequestPasswordResetDto;

final class RequestPasswordResetDtoTest extends UnitTestCase
{
    public function testConstruct(): void
    {
        $email = $this->faker->email();

        $requestPasswordResetDto = new RequestPasswordResetDto($email);

        $this->assertEquals($email, $requestPasswordResetDto->email);
    }
}
