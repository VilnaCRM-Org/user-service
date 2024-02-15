<?php

namespace App\Tests\Unit\User\Application\DTO;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\RetryDto;

class RetryDtoTest extends UnitTestCase
{
    public function testCanBeInstantiated(): void
    {
        $oAuth = new RetryDto();

        $this->assertInstanceOf(RetryDto::class, $oAuth);
    }
}
