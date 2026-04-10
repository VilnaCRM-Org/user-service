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
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

final class ResilientHttpClientFactoryTest extends UnitTestCase
{
    public function testConstructorRejectsNonPositiveConnectTimeout(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('connectTimeoutMs must be greater than 0.');

        new ResilientHttpClientFactory(0, 5000, 1, HandlerStack::create());
    }

    public function testConstructorRejectsNonPositiveTimeout(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('timeoutMs must be greater than 0.');

        new ResilientHttpClientFactory(1500, 0, 1, HandlerStack::create());
    }

    public function testConstructorRejectsNegativeMaxRetries(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('maxRetries must be greater than or equal to 0.');

        new ResilientHttpClientFactory(1500, 5000, -1, HandlerStack::create());
    }

    public function testConstructorAllowsZeroMaxRetries(): void
    {
        $factory = new ResilientHttpClientFactory(1500, 5000, 0, HandlerStack::create());

        $this->assertInstanceOf(ResilientHttpClientFactory::class, $factory);
    }

    public function testCreateBuildsIndependentHandlerPerClient(): void
    {
        $baseStack = HandlerStack::create();
        $factory = new ResilientHttpClientFactory(1500, 5000, 1, $baseStack);

        $first = $this->assertClient($factory->create());
        $second = $this->assertClient($factory->create());

        $this->assertNotSame(
            $first->getConfig('handler'),
            $second->getConfig('handler'),
        );
    }

    public function testRetryDelayUsesExponentialBackoffInMilliseconds(): void
    {
        $factory = new ResilientHttpClientFactory(
            1500,
            5000,
            1,
            HandlerStack::create(),
        );

        $method = new \ReflectionMethod(ResilientHttpClientFactory::class, 'createRetryDelay');
        $this->makeAccessible($method);

        $delay = $method->invoke($factory);

        $this->assertIsCallable($delay);
        $this->assertSame(1000, $delay(0));
        $this->assertSame(2000, $delay(1));
        $this->assertSame(4000, $delay(2));
        $this->assertSame(8000, $delay(4));
        $this->assertSame(8000, $delay(8));
    }

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
        $mock = new MockHandler([
            new Response(500, [], 'Server Error'),
            new Response(200, [], 'OK'),
        ]);

        $client = $this->createResilientClient($mock, 1);
        $response = $client->request('GET', 'https://example.com');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(0, $mock->count());
    }

    public function testClientDoesNotRetryOnClientError(): void
    {
        $mock = new MockHandler([
            new Response(400, [], 'Bad Request'),
            new Response(200, [], 'Should not reach'),
        ]);

        $client = $this->createResilientClient($mock, 1);

        $exceptionThrown = false;

        try {
            $client->request('GET', 'https://example.com');
        } catch (\GuzzleHttp\Exception\ClientException) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);
        $this->assertSame(1, $mock->count());
    }

    public function testClientRespectsMaxRetries(): void
    {
        $mock = new MockHandler([
            new Response(500),
            new Response(500),
            new Response(500),
        ]);

        $client = $this->createResilientClient($mock, 1);

        $exceptionThrown = false;

        try {
            $client->request('GET', 'https://example.com');
        } catch (ServerException) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);
        $this->assertSame(1, $mock->count());
    }

    public function testClientRetriesOnConnectException(): void
    {
        $mock = new MockHandler([
            new ConnectException(
                'Connection refused',
                new Request('GET', '/'),
            ),
            new Response(200, [], 'OK'),
        ]);

        $client = $this->createResilientClient($mock, 1);
        $response = $client->request('GET', 'https://example.com');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(0, $mock->count());
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

    private function createResilientClient(
        MockHandler $mock,
        int $maxRetries,
    ): ClientInterface {
        $stack = HandlerStack::create($mock);
        $factory = new ResilientHttpClientFactory(
            1500,
            5000,
            $maxRetries,
            $stack,
        );

        return $factory->create();
    }

    private function assertClient(ClientInterface $client): Client
    {
        $this->assertInstanceOf(Client::class, $client);

        return $client;
    }
}
