<?php

declare(strict_types=1);

namespace App\Tests\Behat\OAuthContext\Input;

final class PasswordGrantInput extends ObtainAccessTokenInput
{
    public function __construct(
        private string $username,
        private string $password,
        ?string $grant_type = null
    ) {
        parent::__construct($grant_type);
    }

    /**
     * @return array<string|null>
     *
     * @psalm-return array{grant_type: string|null, username: string, password: string}
     */
    #[\Override]
    public function toArray(): array
    {
        return [
            'grant_type' => $this->getGrantType(),
            'username' => $this->username,
            'password' => $this->password,
        ];
    }
}
