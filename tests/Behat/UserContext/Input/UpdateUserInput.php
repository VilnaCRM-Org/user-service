<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext\Input;

final class UpdateUserInput extends RequestInput
{
    public function __construct(
        public readonly ?string $email = null,
        public readonly ?string $initials = null,
        public readonly ?string $oldPassword = null,
        public readonly ?string $newPassword = null
    ) {
    }

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
