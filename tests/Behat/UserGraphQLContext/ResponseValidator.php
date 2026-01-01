<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserGraphQLContext;

use App\Tests\Behat\UserGraphQLContext\Input\GraphQLMutationInput;
use PHPUnit\Framework\Assert;

final class ResponseValidator
{
    /**
     * @param array<string> $responseContent
     * @param array<string, string|bool|int|null> $userData
     */
    public function validateFields(
        array $responseContent,
        array $userData,
        ?GraphQLMutationInput $graphQLInput = null
    ): void {
        foreach ($responseContent as $fieldName) {
            Assert::assertArrayHasKey($fieldName, $userData);

            if ($graphQLInput !== null) {
                $this->validateFieldValue($fieldName, $userData, $graphQLInput);
            }
        }
    }

    /**
     * @param array<string, string|bool|int|null> $userData
     */
    private function validateFieldValue(
        string $fieldName,
        array $userData,
        GraphQLMutationInput $graphQLInput
    ): void {
        // Only public properties are accessible from here.
        $inputProps = get_object_vars($graphQLInput);
        if (array_key_exists($fieldName, $inputProps)) {
            Assert::assertSame(
                $inputProps[$fieldName],
                $userData[$fieldName],
            );
        }
    }
}
