<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator\Strategy;

use App\Shared\Application\Validator\Strategy\InitialsValidationChecks;
use PHPUnit\Framework\TestCase;

final class InitialsValidationChecksTest extends TestCase
{
    private InitialsValidationChecks $validationChecks;

    protected function setUp(): void
    {
        $this->validationChecks = new InitialsValidationChecks();
    }

    public function testIsOnlySpacesWithOnlySpaces(): void
    {
        $result = $this->validationChecks->isOnlySpaces('   ');

        $this->assertTrue($result);
    }

    public function testIsOnlySpacesWithSingleSpace(): void
    {
        $result = $this->validationChecks->isOnlySpaces(' ');

        $this->assertTrue($result);
    }

    public function testIsOnlySpacesWithValidInitials(): void
    {
        $result = $this->validationChecks->isOnlySpaces('J.D.');

        $this->assertFalse($result);
    }

    public function testIsOnlySpacesWithEmptyString(): void
    {
        $result = $this->validationChecks->isOnlySpaces('');

        $this->assertFalse($result);
    }

    public function testIsOnlySpacesWithSpacesAroundText(): void
    {
        $result = $this->validationChecks->isOnlySpaces(' A.B. ');

        $this->assertFalse($result);
    }

    public function testIsOnlySpacesWithTabsAndSpaces(): void
    {
        $result = $this->validationChecks->isOnlySpaces(" \t \n ");

        $this->assertTrue($result);
    }

    public function testIsOnlySpacesWithNonStringValue(): void
    {
        $result = $this->validationChecks->isOnlySpaces(123);

        $this->assertFalse($result);
    }

    public function testIsOnlySpacesWithStringableObject(): void
    {
        $stringable = new class {
            public function __toString(): string
            {
                return '   ';
            }
        };

        $result = $this->validationChecks->isOnlySpaces($stringable);

        $this->assertTrue($result);
    }

    public function testIsOnlySpacesWithStringableObjectContainingText(): void
    {
        $stringable = new class {
            public function __toString(): string
            {
                return 'A.B.';
            }
        };

        $result = $this->validationChecks->isOnlySpaces($stringable);

        $this->assertFalse($result);
    }
}
