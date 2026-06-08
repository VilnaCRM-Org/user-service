<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Validator;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Validator\PasskeyRpIdValidator;
use InvalidArgumentException;

use function sprintf;
use function str_repeat;

final class PasskeyRpIdValidatorTest extends UnitTestCase
{
    private const PUBLIC_SUFFIX_LIST_PATH =
        __DIR__ . '/../../../../../config/passkey/public_suffix_list.dat';

    public function testProductionRejectsHostNameWhenIdnNormalizationFails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Production passkey relying party ID must be a public host name.'
        );

        (new PasskeyRpIdValidator())->assertProductionHostName(
            sprintf('%s.%s', str_repeat('a', 64), $this->faker->safeEmailDomain()),
            'prod',
            self::PUBLIC_SUFFIX_LIST_PATH
        );
    }
}
