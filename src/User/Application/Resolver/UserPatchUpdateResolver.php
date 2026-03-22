<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use App\User\Application\DTO\UserPatchDto;
use App\User\Application\DTO\UserPatchPayload;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\ValueObject\UserUpdate;

final readonly class UserPatchUpdateResolver
{
    public function __construct(
        private UserPatchEmailResolver $emailResolver,
        private UserPatchFieldResolver $fieldResolver,
        private UserPatchPasswordResolver $passwordResolver
    ) {
    }

    public function resolve(
        UserPatchDto $data,
        UserInterface $user,
        ?array $payload
    ): UserUpdate {
        $payloadWrapper = new UserPatchPayload($payload);

        return $this->buildUpdate($data, $user, $payloadWrapper);
    }

    private function buildUpdate(
        UserPatchDto $data,
        UserInterface $user,
        UserPatchPayload $payload
    ): UserUpdate {
        return new UserUpdate(
            $this->emailResolver->resolve(
                $data->email,
                $user->getEmail(),
                $payload->provided('email')
            ),
            $this->fieldResolver->resolve(
                $data->initials,
                $user->getInitials(),
                $payload->provided('initials'),
                'initials'
            ),
            $this->passwordResolver->resolve(
                $data->newPassword,
                $data->oldPassword,
                $payload->provided('newPassword')
            ),
            $data->oldPassword
        );
    }
}
