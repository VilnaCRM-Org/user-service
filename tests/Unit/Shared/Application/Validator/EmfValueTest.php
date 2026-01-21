<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Shared\Application\Validator\EmfValue;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Validation;

final class EmfValueTest extends UnitTestCase
{
    public function testEmptyValueIsRejected(): void
    {
        $violations = Validation::createValidator()->validate('   ', new EmfValue());

        self::assertSame(EmfValue::EMPTY_MESSAGE, $violations[0]->getMessage());
    }

    public function testTooLongValueIsRejected(): void
    {
        $violations = Validation::createValidator()->validate(
            str_repeat('a', EmfValue::MAX_LENGTH + 1),
            new EmfValue()
        );

        self::assertSame(EmfValue::TOO_LONG_MESSAGE, $violations[0]->getMessage());
    }

    public function testNonAsciiValueIsRejected(): void
    {
        $violations = Validation::createValidator()->validate('é', new EmfValue());

        self::assertSame(EmfValue::NON_ASCII_MESSAGE, $violations[0]->getMessage());
    }

    public function testControlCharsInValueAreRejected(): void
    {
        $violations = Validation::createValidator()->validate("a\x07b", new EmfValue());

        self::assertSame(EmfValue::CONTROL_CHARS_MESSAGE, $violations[0]->getMessage());
    }
}
