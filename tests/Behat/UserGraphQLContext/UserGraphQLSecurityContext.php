<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserGraphQLContext;

use Behat\Behat\Context\Context;

final class UserGraphQLSecurityContext implements Context
{
    private const DEPTH_LIMIT = 20;
    private const COMPLEXITY_LIMIT = 500;

    public function __construct(
        private readonly UserGraphQLState $state,
        private readonly UserGraphQLRequestExecutor $requestExecutor,
    ) {
    }

    /**
     * @When I send a GraphQL introspection query
     */
    public function sendGraphQlIntrospectionQuery(): void
    {
        $this->state->setQuery(
            'query { __schema { queryType { name } } }'
        );
        $this->requestExecutor->sendCurrentQuery();
    }

    /**
     * @When I send a GraphQL query with depth greater than 20
     */
    public function sendGraphQlQueryWithDepthGreaterThanTwenty(): void
    {
        $this->state->setQuery(
            $this->buildDepthQuery(self::DEPTH_LIMIT + 5)
        );
        $this->requestExecutor->sendCurrentQuery();
    }

    /**
     * @When I send a GraphQL query with complexity greater than 500
     */
    public function sendGraphQlQueryWithComplexityGreaterThanFiveHundred(): void
    {
        $this->state->setQuery(
            $this->buildComplexityQuery(self::COMPLEXITY_LIMIT + 20)
        );
        $this->requestExecutor->sendCurrentQuery();
    }

    /**
     * @When I send a GraphQL query for user collection
     */
    public function iSendGraphQlQueryForUserCollection(): void
    {
        $this->state->setQueryName('users');
        $this->state->setQuery(
            '{ users(first: 1) { edges { node { id email } } } }'
        );
        $this->requestExecutor->sendCurrentQuery();
    }

    /**
     * @When I send a GraphQL query with depth exactly 20
     */
    public function iSendGraphQlQueryWithDepthExactlyTwenty(): void
    {
        $this->state->setQuery(
            $this->buildDepthQuery(self::DEPTH_LIMIT)
        );
        $this->requestExecutor->sendCurrentQuery();
    }

    /**
     * @When I send a GraphQL query with complexity exactly 500
     */
    public function iSendGraphQlQueryWithComplexityExactly500(): void
    {
        $this->state->setQuery(
            $this->buildComplexityQuery(self::COMPLEXITY_LIMIT)
        );
        $this->requestExecutor->sendCurrentQuery();
    }

    /**
     * @When I send a GraphQL query with depth :depth and complexity :complexity
     */
    public function iSendGraphQlQueryWithDepthAndComplexity(
        int $depth,
        int $complexity
    ): void {
        $this->state->setQuery(sprintf(
            'query { %s %s }',
            $this->extractQueryBody($this->buildDepthQuery($depth)),
            $this->extractQueryBody($this->buildComplexityQuery($complexity))
        ));
        $this->requestExecutor->sendCurrentQuery();
    }

    /**
     * @When I send a GraphQL batch request as empty JSON array
     */
    public function iSendGraphQlBatchRequestAsEmptyJsonArray(): void
    {
        $this->requestExecutor->sendRawPayload('[]');
    }

    /**
     * @When I send a single GraphQL query as JSON object
     */
    public function iSendSingleGraphQlQueryAsJsonObject(): void
    {
        $this->requestExecutor->sendRawPayload(
            \Safe\json_encode([
                'query' => '{ users(first: 1) { edges { node { id } } } }',
            ])
        );
    }

    private function buildDepthQuery(int $nestedDepth): string
    {
        $selection = 'name kind';
        for ($index = 0; $index < $nestedDepth; $index++) {
            $selection = sprintf('name kind ofType { %s }', $selection);
        }

        return sprintf(
            'query { __type(name: "User") { fields { type { %s } } } }',
            $selection
        );
    }

    private function buildComplexityQuery(int $queryCount): string
    {
        $queries = [];
        for ($index = 1; $index <= $queryCount; $index++) {
            $queries[] = sprintf(
                'userQuery%d: users(first: 1) { edges { node { id } } }',
                $index
            );
        }

        return sprintf('query { %s }', implode(' ', $queries));
    }

    private function extractQueryBody(string $fullQuery): string
    {
        $trimmed = trim($fullQuery);
        if (str_starts_with($trimmed, 'query')) {
            $trimmed = substr($trimmed, 5);
        }

        $trimmed = trim($trimmed);
        if (
            str_starts_with($trimmed, '{')
            && str_ends_with($trimmed, '}')
        ) {
            return substr($trimmed, 1, -1);
        }

        return $trimmed;
    }
}
