<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use App\Tests\Integration\IntegrationTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @covers GraphQL batch request rejection
 */
final class GraphQLBatchRejectTest extends IntegrationTestCase
{
    /**
     * @test
     * AC: NFR-59 - GraphQL batch requests (JSON arrays) must be rejected with 400
     */
    public function graphql_batch_request_returns_400(): void
    {
        $kernel = self::getContainer()->get('kernel');
        $this->assertInstanceOf(HttpKernelInterface::class, $kernel);

        // Batch request: JSON array of queries (OWASP API2:2023 rate limit bypass)
        $batchRequest = [
            ['query' => '{ __typename }'],
            ['query' => '{ __typename }'],
            ['query' => '{ __typename }'],
        ];

        $request = Request::create(
            '/api/graphql',
            'POST',
            [],
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode($batchRequest)
        );

        $response = $kernel->handle($request);

        $this->assertSame(
            400,
            $response->getStatusCode(),
            'GraphQL batch requests (JSON arrays) must be rejected with 400 Bad Request (AC: NFR-59, RC-01)'
        );

        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('detail', $data);
        $this->assertStringContainsString(
            'batch',
            strtolower($data['detail'] ?? ''),
            'Error message should mention batch requests'
        );
    }

    /**
     * @test
     * AC: NFR-59 - Single GraphQL requests should still work
     */
    public function single_graphql_request_is_allowed(): void
    {
        $kernel = self::getContainer()->get('kernel');
        $this->assertInstanceOf(HttpKernelInterface::class, $kernel);

        // Single request: JSON object (allowed)
        $singleRequest = ['query' => '{ __typename }'];

        $request = Request::create(
            '/api/graphql',
            'POST',
            [],
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode($singleRequest)
        );

        $response = $kernel->handle($request);

        $this->assertNotSame(
            400,
            $response->getStatusCode(),
            'Single GraphQL requests must NOT be blocked (only batch requests)'
        );
    }

    /**
     * @test
     * AC: NFR-59 - Batch rejection should happen before rate limiting
     */
    public function batch_rejection_happens_before_authentication(): void
    {
        $kernel = self::getContainer()->get('kernel');
        $this->assertInstanceOf(HttpKernelInterface::class, $kernel);

        // Batch request without auth should return 400, NOT 401
        $batchRequest = [
            ['query' => '{ __typename }'],
            ['query' => '{ __typename }'],
        ];

        $request = Request::create(
            '/api/graphql',
            'POST',
            [],
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode($batchRequest)
        );

        $response = $kernel->handle($request);

        $this->assertSame(
            400,
            $response->getStatusCode(),
            'Batch rejection (400) must happen before authentication (401) - priority 130 > 120'
        );
    }
}
