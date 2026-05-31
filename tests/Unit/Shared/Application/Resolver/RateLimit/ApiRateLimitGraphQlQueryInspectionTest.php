<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use App\Shared\Application\Resolver\RateLimit\ApiRateLimitGraphQlQueryInspection;
use App\Shared\Application\Resolver\RateLimit\ApiRateLimitGraphQlQueryInspector;
use PHPUnit\Framework\TestCase;

final class ApiRateLimitGraphQlQueryInspectionTest extends TestCase
{
    public function testFromQueryReturnsNullForUnknownOperationName(): void
    {
        $inspection = $this->createInspection(
            'mutation Real { updateProject(input: { id: "project-id" }) { project { id } } }',
            'Missing'
        );

        self::assertNull($inspection);
    }

    public function testContainsMutationFieldRequiresMutationOperation(): void
    {
        $inspection = $this->createInspection(
            <<<'GRAPHQL'
query Real {
  passkeySignInOptionsUser(input: { email: "user@example.test" }) { user { id } }
}
GRAPHQL,
            'Real'
        );

        self::assertNotNull($inspection);
        self::assertFalse($inspection->containsMutationField(['passkeySignInOptionsUser']));
    }

    public function testReadsOperationAfterFragmentsAndSkipsFragmentSelections(): void
    {
        $inspection = $this->createInspection(
            <<<'GRAPHQL'
fragment IgnoredFields on Mutation {
  signInUser(input: { email: "fragment@example.test" }) { user { id } }
}
mutation Real($input: signInUserInput!) {
  ...IgnoredFields
  signInUser(input: $input) { user { id } }
}
GRAPHQL,
            'Real'
        );

        self::assertNotNull($inspection);
        self::assertTrue($inspection->containsMutationField(['signInUser']));
        self::assertSame(['input'], $inspection->inputVariableNames());
        self::assertNull($inspection->findArgumentStringValue(['email']));
        self::assertNull(
            $inspection->findInputObjectVariableValue(['input' => ['name' => 'value']], ['email'])
        );
    }

    public function testReturnsDirectArgumentVariableName(): void
    {
        $inspection = $this->createInspection(
            'mutation Real($email: String!) { signInUser(email: $email) { user { id } } }',
            'Real'
        );

        self::assertNotNull($inspection);
        self::assertSame('email', $inspection->findArgumentVariableName(['email']));
        self::assertSame([], $inspection->inputVariableNames());
    }

    public function testReturnsNestedObjectStringValue(): void
    {
        $inspection = $this->createInspection(
            <<<'GRAPHQL'
mutation Real {
  signInUser(input: { credentials: { email: "user@example.test" } }) { user { id } }
}
GRAPHQL,
            'Real'
        );

        self::assertNotNull($inspection);
        self::assertSame('user@example.test', $inspection->findArgumentStringValue(['email']));
    }

    private function createInspection(
        string $query,
        ?string $operationName
    ): ?ApiRateLimitGraphQlQueryInspection {
        return (new ApiRateLimitGraphQlQueryInspector())->inspect($query, $operationName);
    }
}
