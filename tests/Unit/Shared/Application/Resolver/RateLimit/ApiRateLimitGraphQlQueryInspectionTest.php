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

    public function testCollectsMultipleFragmentDefinitions(): void
    {
        $inspection = $this->createInspection($this->multiFragmentSignInQuery(), 'Real');

        self::assertNotNull($inspection);
        self::assertSame(2, $inspection->countMutationFields([
            'signInUser',
            'passkeySignInOptionsUser',
        ]));
        self::assertSame(
            ['first@example.test', 'second@example.test'],
            $inspection->findStringValuesForMutationFields(
                [],
                ['signInUser', 'passkeySignInOptionsUser'],
                ['email']
            )
        );
    }

    public function testReturnsNullForAnonymousOperationWhenSpecificNameRequested(): void
    {
        $inspection = $this->createInspection(
            'mutation { signInUser(input: { email: "user@example.test" }) { user { id } } }',
            'Real'
        );

        self::assertNull($inspection);
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
        self::assertNull($inspection->findArgumentStringValue(['email']));
        self::assertSame([], $inspection->inputVariableNames());
    }

    public function testReturnsNullForEmptyInlineArgumentString(): void
    {
        $inspection = $this->createInspection(
            'mutation Real { signInUser(input: { email: "" }) { user { id } } }',
            'Real'
        );

        self::assertNotNull($inspection);
        self::assertNull($inspection->findArgumentStringValue(['email']));
    }

    public function testReturnsNullForMissingDirectArgumentVariable(): void
    {
        $inspection = $this->createInspection(
            'mutation Real { signInUser(input: { email: "user@example.test" }) { user { id } } }',
            'Real'
        );

        self::assertNotNull($inspection);
        self::assertNull($inspection->findArgumentVariableValue(
            ['' => 'leak@example.test'],
            ['email']
        ));
    }

    public function testRejectsEmptyAndNonStringVariableValues(): void
    {
        $inspection = $this->createInspection(
            'mutation Real($email: String!) { signInUser(email: $email) { user { id } } }',
            'Real'
        );

        self::assertNotNull($inspection);
        self::assertNull($inspection->findArgumentVariableValue(['email' => ''], ['email']));
        self::assertNull($inspection->findArgumentVariableValue(['email' => 42], ['email']));
    }

    public function testFieldStringValuesRejectEmptyAndNonStringVariableValues(): void
    {
        $inspection = $this->createInspection(
            <<<'GRAPHQL'
mutation Real($email: String!) {
  passkeySignInOptionsUser(input: { email: $email }) { user { id } }
}
GRAPHQL,
            'Real'
        );

        self::assertNotNull($inspection);
        self::assertSame([], $inspection->findStringValuesForMutationFields(
            ['email' => ''],
            ['passkeySignInOptionsUser'],
            ['email']
        ));
        self::assertSame([], $inspection->findStringValuesForMutationFields(
            ['email' => 42],
            ['passkeySignInOptionsUser'],
            ['email']
        ));
    }

    public function testKeepsAllStringValuesResolvedForOneField(): void
    {
        $inspection = $this->createInspection(
            <<<'GRAPHQL'
mutation Real($input: signInUserInput!) {
  signInUser(email: "direct@example.test", input: $input) { user { id } }
}
GRAPHQL,
            'Real'
        );

        self::assertNotNull($inspection);
        self::assertSame(
            ['direct@example.test', 'variable@example.test'],
            $inspection->findStringValuesForMutationFields(
                ['input' => ['email' => 'variable@example.test']],
                ['signInUser'],
                ['email']
            )
        );
    }

    public function testInputVariableNamesAreUniqueAndStable(): void
    {
        $inspection = $this->createInspection($this->duplicatedInputVariableQuery(), 'Real');

        self::assertNotNull($inspection);
        self::assertSame(['first', 'second'], $inspection->inputVariableNames());
    }

    public function testInputVariableNamesKeepMultipleInputArgumentsOnOneField(): void
    {
        $inspection = $this->createInspection(
            <<<'GRAPHQL'
mutation Real($first: signInUserInput!, $second: signInUserInput!) {
  signInUser(input: $first, input: $second) { user { id } }
}
GRAPHQL,
            'Real'
        );

        self::assertNotNull($inspection);
        self::assertSame(['first', 'second'], $inspection->inputVariableNames());
    }

    public function testInputVariableNamesContinuePastOtherArguments(): void
    {
        $inspection = $this->createInspection(
            <<<'GRAPHQL'
mutation Real($input: signInUserInput!) {
  signInUser(clientMutationId: "client-id", input: $input) { user { id } }
}
GRAPHQL,
            'Real'
        );

        self::assertNotNull($inspection);
        self::assertSame(['input'], $inspection->inputVariableNames());
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

    private function multiFragmentSignInQuery(): string
    {
        return <<<'GRAPHQL'
fragment FirstSignIn on Mutation {
  signInUser(input: { email: "first@example.test" }) { user { id } }
}
fragment SecondSignIn on Mutation {
  passkeySignInOptionsUser(input: { email: "second@example.test" }) { user { id } }
}
mutation Real {
  ...FirstSignIn
  ...SecondSignIn
}
GRAPHQL;
    }

    private function duplicatedInputVariableQuery(): string
    {
        return <<<'GRAPHQL'
mutation Real($first: signInUserInput!, $second: signInUserInput!) {
  first: signInUser(input: $first) { user { id } }
  second: signInUser(input: $first) { user { id } }
  third: signInUser(input: $second) { user { id } }
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
