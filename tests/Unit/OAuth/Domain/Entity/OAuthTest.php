<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Domain\Entity;

use App\OAuth\Domain\Entity\OAuth;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\TestCase;

class OAuthTest extends UnitTestCase
{
    public function testCanBeInstantiated(): void
    {
        $oAuth = new OAuth();

        $this->assertInstanceOf(OAuth::class, $oAuth);
    }
}
