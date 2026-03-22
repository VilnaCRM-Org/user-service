<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\Source;

use App\Shared\Application\Resolver\Source\ObjectMethodEmailSource;
use App\Shared\Application\Resolver\Source\ObjectPropertyEmailSource;
use App\Tests\Unit\UnitTestCase;
use stdClass;

final class EmailObjectSourceTest extends UnitTestCase
{
    public function testObjectPropertySourceReturnsNullForNonObject(): void
    {
        $source = new ObjectPropertyEmailSource('email');

        self::assertNull($source->extract(['email' => 'value']));
    }

    public function testObjectPropertySourceExtractsPropertyValue(): void
    {
        $email = $this->faker->email();
        $entry = new class($email) {
            public function __construct(public string $email)
            {
            }
        };

        $source = new ObjectPropertyEmailSource('email');

        self::assertSame($email, $source->extract($entry));
    }

    public function testObjectPropertySourceReturnsNullForNonStringProperty(): void
    {
        $email = $this->faker->numberBetween(1, 1000);
        $entry = new class($email) {
            public function __construct(public int $email)
            {
            }
        };

        $source = new ObjectPropertyEmailSource('email');

        self::assertNull($source->extract($entry));
    }

    public function testObjectMethodSourceReturnsNullWhenMethodMissing(): void
    {
        $source = new ObjectMethodEmailSource('getEmail');

        self::assertNull($source->extract(new stdClass()));
    }

    public function testObjectMethodSourceExtractsStringValue(): void
    {
        $email = $this->faker->email();
        $entry = new class($email) {
            public function __construct(private string $email)
            {
            }

            public function getEmail(): ?string
            {
                return $this->email;
            }
        };

        $source = new ObjectMethodEmailSource('getEmail');

        self::assertSame($email, $source->extract($entry));
    }

    public function testObjectMethodSourceReturnsNullForNonStringValues(): void
    {
        $email = $this->faker->numberBetween(1, 1000);
        $entry = new class($email) {
            public function __construct(private int $email)
            {
            }

            public function getEmail(): int
            {
                return $this->email;
            }
        };

        $source = new ObjectMethodEmailSource('getEmail');

        self::assertNull($source->extract($entry));
    }
}
