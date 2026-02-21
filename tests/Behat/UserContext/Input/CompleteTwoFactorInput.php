<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext\Input;

final class CompleteTwoFactorInput extends RequestInput
{
    public function __construct(
        public string $pendingSessionId,
        public string $twoFactorCode,
    ) {
    }
}
