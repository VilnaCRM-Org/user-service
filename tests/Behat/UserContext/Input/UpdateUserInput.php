<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext\Input;

/**
 * @psalm-suppress UnusedClass
 * @psalm-suppress UnusedProperty - Properties used via reflection in RequestInput::toArray()
 */
final class UpdateUserInput extends RequestInput
{
    public function __construct(
        private readonly ?string $email = null,
        private readonly ?string $initials = null,
        private readonly ?string $oldPassword = null,
        private readonly ?string $newPassword = null
    ) {
    }

    #[\Override]
    public function getJson(): string
    {
        $data = [];
        if ($this->email !== null) {
            $data['email'] = $this->email;
        }
        if ($this->initials !== null) {
            $data['initials'] = $this->initials;
        }
        if ($this->oldPassword !== null) {
            $data['oldPassword'] = $this->oldPassword;
        }
        if ($this->newPassword !== null) {
            $data['newPassword'] = $this->newPassword;
        }

        return json_encode($data);
    }
}
