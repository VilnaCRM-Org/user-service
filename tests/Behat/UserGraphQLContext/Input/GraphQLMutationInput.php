<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserGraphQLContext\Input;

use GraphQL\RequestBuilder\Argument;

abstract readonly class GraphQLMutationInput
{
    /**
     * @return array<Argument>
     */
    public function toGraphQLArguments(): array
    {
        $fields = $this->toArray();
        $arguments = [];

        foreach ($fields as $fieldName => $fieldValue) {
            $arguments[] = new Argument($fieldName, $fieldValue);
        }

        return $arguments;
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        $reflection = new \ReflectionObject($this);
        $values = [];

        foreach ($reflection->getProperties() as $property) {
            $values[$property->getName()] = $property->getValue($this);
        }

        return $values;
    }
}
