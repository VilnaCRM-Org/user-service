<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Domain\Entity;

use App\OAuth\Domain\Entity\OAuth;
use App\Tests\Unit\UnitTestCase;

class OAuthTest extends UnitTestCase
{
    public function testCanBeInstantiated(): void
    {
        $oAuth = new OAuth();

        $this->assertInstanceOf(OAuth::class, $oAuth);
    }
}
