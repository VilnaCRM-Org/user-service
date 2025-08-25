<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @OA\Schema(
 *     description="Request password reset DTO",
 *     required={"email"}
 * )
 */
final readonly class RequestPasswordResetDto
{
    public function __construct(
        /**
         * @OA\Property(
         *     description="User email address",
         *     example="user@example.com"
         * )
         */
        #[Groups(['request_password_reset:write'])]
        public ?string $email = null
    ) {
    }
}
