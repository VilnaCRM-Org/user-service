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
        $validationMap = [
            'initials' => UpdateUserMutationInput::INITIALS_NOT_NULL,
            'email' => UpdateUserMutationInput::EMAIL_NOT_NULL,
            'newPassword' => UpdateUserMutationInput::NEW_PASSWORD_NOT_NULL,
        ];

        return $this->processValidationGroups($args, $validationMap);
    }

    /**
     * @param array<string, string> $args
     * @param array<string, string> $validationMap
     *
     * @return array<string>
     */
    private function processValidationGroups(
        array $args,
        array $validationMap
    ): array {
        $validationGroups = [];

        foreach ($validationMap as $key => $validation) {
            if (isset($args[$key])) {
                $validationGroups[] = $validation;
            }
        }

        return $validationGroups;
    }
}
