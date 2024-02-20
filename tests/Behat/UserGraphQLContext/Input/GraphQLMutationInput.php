<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserGraphQLContext\Input;

use GraphQL\RequestBuilder\Argument;

readonly class GraphQLMutationInput
{
    public function toGraphQLArguments(): array
    {
        $fields = get_object_vars($this);
        $arguments = [];

        foreach ($fields as $fieldName => $fieldValue) {
            $arguments[] = new Argument($fieldName, $fieldValue);
        }

        return $arguments;
    }
}
