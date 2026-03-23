<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext\Input;

/**
 * @psalm-api
 */
final class TwoFactorCodeInput extends RequestInput
{
    public function __construct(
        public string $twoFactorCode,
    ) {
    }
}
