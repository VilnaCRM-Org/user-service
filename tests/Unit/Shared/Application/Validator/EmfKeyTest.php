<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Shared\Application\Validator\EmfKey;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Validation;

final class EmfKeyTest extends UnitTestCase
{
    public function testEmptyKeyIsRejected(): void
    {
        $violations = Validation::createValidator()->validate('   ', new EmfKey());

        self::assertSame(EmfKey::EMPTY_MESSAGE, $violations[0]->getMessage());
    }

    public function testTooLongKeyIsRejected(): void
    {
        $violations = Validation::createValidator()->validate(
            str_repeat('a', EmfKey::MAX_LENGTH + 1),
            new EmfKey()
        );

        self::assertSame(EmfKey::TOO_LONG_MESSAGE, $violations[0]->getMessage());
    }

    public function testNonAsciiKeyIsRejected(): void
    {
        $violations = Validation::createValidator()->validate('é', new EmfKey());

        self::assertSame(EmfKey::NON_ASCII_MESSAGE, $violations[0]->getMessage());
    }

    public function testControlCharsInKeyAreRejected(): void
    {
        $violations = Validation::createValidator()->validate("a\x07b", new EmfKey());

        self::assertSame(EmfKey::CONTROL_CHARS_MESSAGE, $violations[0]->getMessage());
    }

    public function testKeyStartingWithColonIsRejected(): void
    {
        $violations = Validation::createValidator()->validate(':invalid', new EmfKey());

        self::assertSame(EmfKey::STARTS_WITH_COLON_MESSAGE, $violations[0]->getMessage());
    }
}
