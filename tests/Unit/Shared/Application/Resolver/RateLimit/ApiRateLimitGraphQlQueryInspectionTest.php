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

    public function testReadsOperationAfterFragmentsAndFollowsRootFragmentSelections(): void
    {
        $inspection = $this->createInspection($this->fragmentSignInQuery(), 'Real');

        self::assertNotNull($inspection);
        self::assertTrue($inspection->containsMutationField(['signInUser']));
        self::assertSame(['input'], $inspection->inputVariableNames());
        self::assertSame('fragment@example.test', $inspection->findArgumentStringValue(['email']));
        self::assertSame(
            'selected@example.test',
            $inspection->findInputObjectVariableValue(
                ['input' => ['email' => 'selected@example.test']],
                ['email']
            )
        );
    }

    public function testReturnsScopedMutationFieldStringValuesFromFragments(): void
    {
        $inspection = $this->createInspection($this->scopedSignInQuery(), 'Real');

        self::assertNotNull($inspection);
        self::assertSame(
            ['fragment@example.test', 'target@example.test'],
            $inspection->findStringValuesForMutationFields(
                $this->scopedSignInVariables(),
                ['signInUser', 'passkeySignInOptionsUser'],
                ['email']
            )
        );
    }

    public function testFollowsInlineFragmentsAndSkipsInvalidFragmentSpreads(): void
    {
        $inspection = $this->createInspection($this->inlineAndSkippedFragmentsQuery(), 'Real');

        self::assertNotNull($inspection);
        self::assertTrue($inspection->containsMutationField(['passkeySignInOptionsUser']));
        self::assertSame('inline@example.test', $inspection->findArgumentStringValue(['email']));
    }

    public function testReturnsNullWhenInputVariableDoesNotContainKey(): void
    {
        $inspection = $this->createInspection(
            'mutation Real($input: signInUserInput!) { signInUser(input: $input) { user { id } } }',
            'Real'
        );

        self::assertNotNull($inspection);
        self::assertNull($inspection->findInputObjectVariableValue(
            ['input' => ['name' => 'value']],
            ['email']
        ));
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

    private function scopedSignInQuery(): string
    {
        return <<<'GRAPHQL'
mutation Real($target: String!, $input: signInUserInput!) {
  updateProject(input: { email: "decoy@example.test" }) { project { id } }
  ...SignInFields
  passkeySignInOptionsUser(input: { email: $target }) { user { challengeId } }
}
fragment SignInFields on Mutation {
  signInUser(input: $input) { user { id } }
}
GRAPHQL;
    }

    private function inlineAndSkippedFragmentsQuery(): string
    {
        return <<<'GRAPHQL'
fragment Recursive on Mutation {
  ...Recursive
}
mutation Real {
  ...MissingFragment
  ...Recursive
  ... on Mutation {
    passkeySignInOptionsUser(input: { email: "inline@example.test" }) { user { challengeId } }
  }
}
GRAPHQL;
    }

    private function fragmentSignInQuery(): string
    {
        return <<<'GRAPHQL'
fragment IgnoredFields on Mutation {
  signInUser(input: { email: "fragment@example.test" }) { user { id } }
}
mutation Real($input: signInUserInput!) {
  ...IgnoredFields
  signInUser(input: $input) { user { id } }
}
GRAPHQL;
    }

    /**
     * @return array<string, array<string, string>|string>
     */
    private function scopedSignInVariables(): array
    {
        return [
            'target' => 'target@example.test',
            'input' => ['credentials' => ['email' => 'fragment@example.test']],
        ];
    }
}
