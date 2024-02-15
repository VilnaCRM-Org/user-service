<?php

namespace App\Tests\Unit\OAuth\Domain\Entity;

use App\OAuth\Domain\Entity\OAuth;
use PHPUnit\Framework\TestCase;

class OAuthTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $oAuth = new OAuth();

        $this->assertInstanceOf(OAuth::class, $oAuth);
    }
}
