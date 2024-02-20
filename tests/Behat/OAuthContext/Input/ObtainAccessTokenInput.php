<?php

declare(strict_types=1);

namespace App\Tests\Behat\OAuthContext\Input;

class ObtainAccessTokenInput
{
    public function __construct(public ?string $grant_type = null)
    {
    }
}
