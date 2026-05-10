<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class GraphQLBatchRejectTest extends AuthIntegrationTestCase
{
    public function testGraphqlBatchRequestReturns400(): void
    {
        $batchRequest = [
            ['query' => '{ __typename }'],
            ['query' => '{ __typename }'],
            ['query' => '{ __typename }'],
        ];
        $response = $this->sendGraphqlRequest(json_encode($batchRequest));

        $message = implode('', [
            'GraphQL batch requests (JSON arrays) ',
            'must be rejected with 400 Bad Request ',
            '(AC: NFR-59, RC-01)',
        ]);
        $this->assertSame(
            400,
            $response->getStatusCode(),
            $message
        );
        $this->assertBatchErrorInResponse($response);
    }

    public function testSingleGraphqlRequestIsAllowed(): void
    {
        $singleRequest = ['query' => '{ __typename }'];
        $response = $this->sendGraphqlRequest(json_encode($singleRequest));

        $this->assertNotSame(
            400,
            $response->getStatusCode(),
            'Single GraphQL requests must NOT be blocked (only batch requests)'
        );
    }

    public function testBatchRejectionHappensBeforeAuthentication(): void
    {
        $batchRequest = [
            ['query' => '{ __typename }'],
            ['query' => '{ __typename }'],
        ];
        $response = $this->sendGraphqlRequest(json_encode($batchRequest));

        $this->assertSame(
            400,
            $response->getStatusCode(),
            'Batch rejection (400) must happen before authentication (401) - priority 130 > 120'
        );
    }

    private function sendGraphqlRequest(string $body): Response
    {
        $kernel = self::getContainer()->get('kernel');
        $this->assertInstanceOf(HttpKernelInterface::class, $kernel);

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
            $body
        );

        return $kernel->handle($request);
    }

    private function assertBatchErrorInResponse(Response $response): void
    {
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('detail', $data);
        $this->assertStringContainsString(
            'batch',
            strtolower($data['detail'] ?? ''),
            'Error message should mention batch requests'
        );
    }
}
