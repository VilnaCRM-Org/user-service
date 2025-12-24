<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use App\User\Application\DTO\UserPatchDto;
use App\User\Application\DTO\UserPatchPayload;
use App\User\Application\Sanitizer\UserPatchEmailSanitizer;
use App\User\Application\Sanitizer\UserPatchNonEmptySanitizer;
use App\User\Application\Sanitizer\UserPatchPasswordSanitizer;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\ValueObject\UserUpdate;

final readonly class UserPatchUpdateResolver
{
    public function __construct(
        private UserPatchEmailSanitizer $emailSanitizer,
        private UserPatchNonEmptySanitizer $nonEmptySanitizer,
        private UserPatchPasswordSanitizer $passwordSanitizer
    ) {
    }

    public function resolve(
        UserPatchDto $data,
        UserInterface $user,
        ?array $payload
    ): UserUpdate {
        $payloadWrapper = new UserPatchPayload($payload);
        $payloadWrapper->ensureNoExplicitNulls();

        return $this->buildUpdate($data, $user, $payloadWrapper);
    }

    private function buildUpdate(
        UserPatchDto $data,
        UserInterface $user,
        UserPatchPayload $payload
    ): UserUpdate {
        return new UserUpdate(
            $this->emailSanitizer->sanitize(
                $data->email,
                $user->getEmail(),
                $payload->provided('email')
            ),
            $this->nonEmptySanitizer->sanitize(
                $data->initials,
                $user->getInitials(),
                $payload->provided('initials'),
                'initials'
            ),
            $this->passwordSanitizer->sanitize(
                $data->newPassword,
                $data->oldPassword,
                $payload->provided('newPassword')
            ),
            $data->oldPassword
        );
    }
}
