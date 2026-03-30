<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\Factory;

use App\OAuth\Infrastructure\Factory\ResilientHttpClientFactory;
use App\Tests\Unit\UnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

final class ResilientHttpClientFactoryTest extends UnitTestCase
{
    public function testCreateReturnsGuzzleClient(): void
    {
        $factory = new ResilientHttpClientFactory(
            1500,
            5000,
            1,
            HandlerStack::create(),
        );

        $client = $factory->create();

        $this->assertInstanceOf(ClientInterface::class, $client);
    }

    public function testClientRetriesOnServerError(): void
    {
        $requestHistory = [];
        $mock = new MockHandler([
            new Response(500, [], 'Server Error'),
            new Response(200, [], 'OK'),
        ]);

        $stack = HandlerStack::create($mock);

        $factory = new ResilientHttpClientFactory(1500, 5000, 1, $stack);
        $factory->create();
        // Push history AFTER retry so it captures all retries
        $stack->push(Middleware::history($requestHistory));

        $client = new Client(['handler' => $stack]);
        $response = $client->request('GET', 'https://example.com');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(2, $requestHistory);
    }

    public function testClientDoesNotRetryOnClientError(): void
    {
        $requestHistory = [];
        $mock = new MockHandler([
            new Response(400, [], 'Bad Request'),
            new Response(200, [], 'Should not reach'),
        ]);

        $stack = HandlerStack::create($mock);

        $factory = new ResilientHttpClientFactory(1500, 5000, 1, $stack);
        $factory->create();
        $stack->push(Middleware::history($requestHistory));

        $client = new Client(['handler' => $stack]);

        $exceptionThrown = false;

        try {
            $client->request('GET', 'https://example.com');
        } catch (\GuzzleHttp\Exception\ClientException) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);
        $this->assertCount(1, $requestHistory);
    }

    public function testClientRespectsMaxRetries(): void
    {
        $requestHistory = [];
        $mock = new MockHandler([
            new Response(500),
            new Response(500),
            new Response(500),
        ]);

        $stack = HandlerStack::create($mock);

        $factory = new ResilientHttpClientFactory(1500, 5000, 1, $stack);
        $factory->create();
        $stack->push(Middleware::history($requestHistory));

        $client = new Client(['handler' => $stack]);

        $exceptionThrown = false;

        try {
            $client->request('GET', 'https://example.com');
        } catch (ServerException) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);
        $this->assertCount(2, $requestHistory);
    }

    public function testClientRetriesOnConnectException(): void
    {
        $requestHistory = [];
        $mock = new MockHandler([
            new ConnectException('Connection refused', new Request('GET', '/')),
            new Response(200, [], 'OK'),
        ]);

        $stack = HandlerStack::create($mock);

        $factory = new ResilientHttpClientFactory(1500, 5000, 1, $stack);
        $factory->create();
        $stack->push(Middleware::history($requestHistory));

        $client = new Client(['handler' => $stack]);
        $response = $client->request('GET', 'https://example.com');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(2, $requestHistory);
    }

    public function testTimeoutConfigurationIsApplied(): void
    {
        $factory = new ResilientHttpClientFactory(
            2000,
            8000,
            2,
            HandlerStack::create(),
        );

        $client = $factory->create();

        $this->assertInstanceOf(Client::class, $client);
        /** @var array{connect_timeout: float|int, timeout: float|int} $config */
        $config = $client->getConfig();
        $this->assertEquals(2.0, $config['connect_timeout']);
        $this->assertEquals(8.0, $config['timeout']);
    }
}
