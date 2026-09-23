<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserGraphQLContext;

use App\Tests\Behat\Support\EnvironmentKernel;
use App\Tests\Behat\UserContext\UserOperationsState;
use LogicException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

final class UserGraphQLRequestExecutor
{
    private const GRAPHQL_ENDPOINT_URI = '/api/graphql';

    /**
     * @var array<string, bool>
     */
    private array $clearedCacheByEnvironment = [];

    public function __construct(
        private readonly UserGraphQLState $state,
        private readonly KernelInterface $kernel,
        private readonly UserOperationsState $userOperationsState,
    ) {
    }

    public function sendCurrentQuery(): void
    {
        $response = $this->createGraphQlResponse($this->buildHeaders());
        $this->state->setResponse($response);
        $this->userOperationsState->response = $response;
    }

    public function sendRawPayload(string $payload): void
    {
        $request = Request::create(
            self::GRAPHQL_ENDPOINT_URI,
            'POST',
            [],
            [],
            [],
            $this->buildHeaders(),
            $payload
        );
        $response = $this->kernel->handle($request);
        $this->state->setResponse($response);
        $this->userOperationsState->response = $response;
    }

    /**
     * @return array<string, string>
     */
    private function buildHeaders(): array
    {
        $headers = [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT_LANGUAGE' => $this->state->getLanguage(),
        ];

        $accessToken = $this->userOperationsState->accessToken;
        if (is_string($accessToken) && $accessToken !== '') {
            $headers['HTTP_AUTHORIZATION'] = sprintf(
                'Bearer %s',
                $accessToken
            );
        }

        $clientIpAddress = $this->userOperationsState->clientIpAddress;
        if (is_string($clientIpAddress) && $clientIpAddress !== '') {
            $headers['REMOTE_ADDR'] = $clientIpAddress;
        }

        return $headers;
    }

    /**
     * @param array<string, string> $headers
     */
    private function createGraphQlResponse(array $headers): Response
    {
        $environment = $this->resolveApplicationEnvironment();
        $request = $this->createGraphQlRequest($headers);

        if ($environment === 'test') {
            return $this->kernel->handle($request);
        }

        $environmentKernel = new EnvironmentKernel(
            $environment,
            $environment !== 'prod',
            $this->kernel->getProjectDir(),
            $this->testMongoServer()
        );
        $this->clearEnvironmentCacheIfNeeded($environmentKernel);
        $environmentKernel->boot();

        try {
            return $environmentKernel->handle($request);
        } finally {
            $environmentKernel->shutdown();
        }
    }

    /**
     * @param array<string, string> $headers
     */
    private function createGraphQlRequest(array $headers): Request
    {
        return Request::create(
            self::GRAPHQL_ENDPOINT_URI,
            'POST',
            [],
            [],
            [],
            $headers,
            \Safe\json_encode(['query' => $this->state->getQuery()])
        );
    }

    private function resolveApplicationEnvironment(): string
    {
        $environment = $this->state->getApplicationEnvironment();
        if (!is_string($environment) || $environment === '') {
            return 'test';
        }

        return $environment;
    }

    private function testMongoServer(): string
    {
        $server = getenv('MONGODB_URL');
        if (!is_string($server) || $server === '') {
            throw new LogicException('The Behat MongoDB server is unavailable.');
        }

        return $server;
    }

    private function clearEnvironmentCacheIfNeeded(KernelInterface $kernel): void
    {
        $cacheDir = $kernel->getCacheDir();
        if (isset($this->clearedCacheByEnvironment[$cacheDir])) {
            return;
        }

        if (is_dir($cacheDir)) {
            (new Filesystem())->remove($cacheDir);
        }

        $this->clearedCacheByEnvironment[$cacheDir] = true;
    }
}
