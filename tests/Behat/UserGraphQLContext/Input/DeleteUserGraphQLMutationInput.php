<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserGraphQLContext\Input;

/**
 */
final readonly class DeleteUserGraphQLMutationInput extends GraphQLMutationInput
{
    public function __construct(private string $id)
    {
    }

    /**
     * @return array{id: string}
     */
    #[\Override]
    public function toArray(): array
    {
        return ['id' => $this->id];
    }
}
