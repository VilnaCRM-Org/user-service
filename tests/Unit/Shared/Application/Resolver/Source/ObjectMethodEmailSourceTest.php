<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\Source;

use App\Shared\Application\Resolver\Source\ObjectMethodEmailSource;
use App\Tests\Unit\UnitTestCase;
use stdClass;

final class ObjectMethodEmailSourceTest extends UnitTestCase
{
    public function testReturnsNullWhenEntryIsNotObject(): void
    {
        $source = new ObjectMethodEmailSource('getEmail');

        $this->assertNull($source->extract($this->faker->email()));
    }

    public function testReturnsNullWhenMethodMissing(): void
    {
        $source = new ObjectMethodEmailSource('getEmail');
        $entry = new stdClass();

        $this->assertNull($source->extract($entry));
    }

    public function testReturnsNullWhenMethodDoesNotReturnString(): void
    {
        $source = new ObjectMethodEmailSource('getEmail');
        $entry = new class() {
            public function getEmail(): int
            {
                return 123;
            }
        };

        $this->assertNull($source->extract($entry));
    }

    public function testReturnsEmailWhenMethodReturnsString(): void
    {
        $email = $this->faker->email();
        $source = new ObjectMethodEmailSource('getEmail');
        $entry = new class($email) {
            public function __construct(private readonly string $email)
            {
            }

            public function getEmail(): string
            {
                return $this->email;
            }
        };

        $this->assertSame($email, $source->extract($entry));
    }
}
