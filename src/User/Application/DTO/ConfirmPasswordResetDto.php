<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @OA\Schema(
 *     description="Confirm password reset DTO",
 *     required={"token", "newPassword"}
 * )
 */
final readonly class ConfirmPasswordResetDto
{
    public function __construct(
        /**
         * @OA\Property(
         *     description="Password reset token",
         *     example="abc123def456"
         * )
         */
        #[Groups(['confirm_password_reset:write'])]
        public ?string $token = null,
        /**
         * @OA\Property(
         *     description="New password",
         *     example="newSecurePassword123"
         * )
         */
        #[Groups(['confirm_password_reset:write'])]
        public ?string $newPassword = null
    ) {
    }
}
