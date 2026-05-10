<?php

declare(strict_types=1);

namespace App\Tests\Behat\OAuthContext\Input;

final class RefreshTokenGrantInput extends ObtainAccessTokenInput
{
    public function __construct(
        private string $refresh_token,
        ?string $grant_type = null
    ) {
        parent::__construct($grant_type);
    }

    /**
     * @return array<string|null>
     *
     * @psalm-return array{grant_type: string|null, refresh_token: string}
     */
    #[\Override]
    public function toArray(): array
    {
        return [
            'grant_type' => $this->getGrantType(),
            'refresh_token' => $this->refresh_token,
        ];
    }
}
