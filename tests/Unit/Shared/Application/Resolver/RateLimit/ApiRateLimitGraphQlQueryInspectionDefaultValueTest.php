<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use App\Shared\Application\Resolver\RateLimit\ApiRateLimitGraphQlDocumentResolver;
use App\Shared\Application\Resolver\RateLimit\ApiRateLimitGraphQlQueryInspection;
use App\Shared\Application\Resolver\RateLimit\ApiRateLimitGraphQlQueryInspector;
use PHPUnit\Framework\TestCase;

final class ApiRateLimitGraphQlQueryInspectionDefaultValueTest extends TestCase
{
    public function testUsesDirectArgumentVariableDefaultValueWhenVariableIsOmitted(): void
    {
        $inspection = $this->createInspection(
            <<<'GRAPHQL'
mutation Real($email: String = "default@example.test") {
  signInUser(email: $email) { user { id } }
}
GRAPHQL,
            'Real'
        );

        self::assertNotNull($inspection);
        self::assertSame(
            'default@example.test',
            $inspection->findArgumentVariableValue([], ['email'])
        );
        self::assertNull($inspection->findArgumentVariableValue(['email' => null], ['email']));
    }

    public function testUsesInputObjectVariableDefaultValueWhenVariableIsOmitted(): void
    {
        $inspection = $this->createInspection($this->inputObjectDefaultQuery(), 'Real');

        self::assertNotNull($inspection);
        self::assertSame(
            'default@example.test',
            $inspection->findInputObjectVariableValue([], ['email'])
        );
        self::assertSame(
            ['default@example.test'],
            $inspection->findStringValuesForMutationFields([], ['signInUser'], ['email'])
        );
    }

    public function testProvidedInputVariableOverridesDefaultValue(): void
    {
        $inspection = $this->createInspection($this->inputObjectDefaultQuery(), 'Real');

        self::assertNotNull($inspection);
        self::assertSame(
            ['provided@example.test'],
            $inspection->findStringValuesForMutationFields(
                ['input' => ['email' => 'provided@example.test']],
                ['signInUser'],
                ['email']
            )
        );
    }

    public function testExplicitNullInputVariableDoesNotUseDefaultValue(): void
    {
        $inspection = $this->createInspection($this->inputObjectDefaultQuery(), 'Real');

        self::assertNotNull($inspection);
        self::assertSame([], $inspection->findStringValuesForMutationFields(
            ['input' => null],
            ['signInUser'],
            ['email']
        ));
    }

    public function testUsesEveryInputObjectVariableDefaultValue(): void
    {
        $inspection = $this->createInspection($this->multipleInputDefaultsQuery(), 'Real');

        self::assertNotNull($inspection);
        self::assertSame(
            ['first@example.test', 'second@example.test'],
            $inspection->findStringValuesForMutationFields(
                [],
                ['signInUser', 'passkeySignInOptionsUser'],
                ['email']
            )
        );
    }

    public function testReturnsNullForNonObjectInputVariableDefaultValue(): void
    {
        $inspection = $this->createInspection($this->nonObjectInputDefaultQuery(), 'Real');

        self::assertNotNull($inspection);
        self::assertNull($inspection->findInputObjectVariableValue([], ['email']));
        self::assertSame([], $inspection->findStringValuesForMutationFields(
            [],
            ['signInUser'],
            ['email']
        ));
    }

    public function testReturnsNullWhenInputVariableDefaultDoesNotContainKey(): void
    {
        $inspection = $this->createInspection($this->inputObjectDefaultWithoutEmailQuery(), 'Real');

        self::assertNotNull($inspection);
        self::assertNull($inspection->findInputObjectVariableValue([], ['email']));
        self::assertSame([], $inspection->findStringValuesForMutationFields(
            [],
            ['signInUser'],
            ['email']
        ));
    }

    private function createInspection(
        string $query,
        ?string $operationName
    ): ?ApiRateLimitGraphQlQueryInspection {
        return (new ApiRateLimitGraphQlQueryInspector(
            new ApiRateLimitGraphQlDocumentResolver()
        ))->inspect($query, $operationName);
    }

    private function inputObjectDefaultQuery(): string
    {
        return <<<'GRAPHQL'
mutation Real($input: signInUserInput = { credentials: { email: "default@example.test" } }) {
  signInUser(input: $input) { user { id } }
}
GRAPHQL;
    }

    private function multipleInputDefaultsQuery(): string
    {
        return <<<'GRAPHQL'
mutation Real(
  $first: signInUserInput = { email: "first@example.test" }
  $second: passkeySignInOptionsUserInput = { email: "second@example.test" }
) {
  signInUser(input: $first) { user { id } }
  passkeySignInOptionsUser(input: $second) { user { challengeId } }
}
GRAPHQL;
    }

    private function nonObjectInputDefaultQuery(): string
    {
        return <<<'GRAPHQL'
mutation Real($input: signInUserInput = "not-an-input-object") {
  signInUser(input: $input) { user { id } }
}
GRAPHQL;
    }

    private function inputObjectDefaultWithoutEmailQuery(): string
    {
        return <<<'GRAPHQL'
mutation Real($input: signInUserInput = { credentials: { name: "value" } }) {
  signInUser(input: $input) { user { id } }
}
GRAPHQL;
    }
}
