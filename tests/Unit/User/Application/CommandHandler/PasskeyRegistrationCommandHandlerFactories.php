<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\User\Application\Factory\PasskeyAuthenticationResultFactory;
use App\User\Application\Factory\PasskeyUserFactory;

final readonly class PasskeyRegistrationCommandHandlerFactories
{
    public function __construct(
        public PasskeyAuthenticationResultFactory $authenticationResultFactory,
        public PasskeyUserFactory $userFactory
    ) {
    }
}
