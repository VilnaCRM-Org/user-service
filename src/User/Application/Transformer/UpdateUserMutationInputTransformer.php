<?php

declare(strict_types=1);

namespace App\User\Application\Transformer;

use App\User\Application\MutationInput\UpdateUserMutationInput;

final class UpdateUserMutationInputTransformer
{
    /**
     * @param array<string, string> $args
     */
    public function transform(array $args): UpdateUserMutationInput
    {
        $validationGroups = $this->getValidationGroups($args);
        return new UpdateUserMutationInput(
            $validationGroups,
            $args['password'] ?? null,
            $args['initials'] ?? null,
            $args['email'] ?? null,
            $args['newPassword'] ?? null
        );
    }

    /**
     * @param array<string, string> $args
     *
     * @return array<string>
     */
    private function getValidationGroups(array $args): array
    {
        return array_merge(
            $this->processInitials($args),
            $this->processEmail($args),
            $this->processNewPassword($args)
        );
    }

    /**
     * @param array<string, string> $args
     *
     * @return array<string>
     */
    private function processInitials(array $args): array
    {
        return isset($args['initials']) ? [
            UpdateUserMutationInput::INITIALS_NOT_NULL,
        ] : [];
    }

    /**
     * @param array<string, string> $args
     *
     * @return array<string>
     */
    private function processEmail(array $args): array
    {
        return isset($args['email']) ? [
            UpdateUserMutationInput::EMAIL_NOT_NULL,
        ] : [];
    }

    /**
     * @param array<string, string> $args
     *
     * @return array<string>
     */
    private function processNewPassword(array $args): array
    {
        return isset($args['newPassword']) ? [
            UpdateUserMutationInput::NEW_PASSWORD_NOT_NULL,
        ] : [];
    }
}
