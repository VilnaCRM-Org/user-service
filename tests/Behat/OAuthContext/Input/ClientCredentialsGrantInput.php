<?php

declare(strict_types=1);

namespace App\Tests\Behat\OAuthContext\Input;

final class ClientCredentialsGrantInput extends ObtainAccessTokenInput
{
    /**
     * @return array<string|null>
     *
     * @psalm-return array{grant_type: string|null}
     */
    #[\Override]
    public function toArray(): array
    {
        return ['grant_type' => $this->getGrantType()];
    }
}
