<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SchemathesisCleanupListenerHeaderStatusSkipTest extends
    SchemathesisCleanupListenerTestCase
{
    public function testListenerSkipsWhenHeaderMissing(): void
    {
        $payload = ['email' => $this->faker->email()];
        $request = Request::create(
            '/api/users',
            Request::METHOD_POST,
            content: json_encode($payload, JSON_THROW_ON_ERROR)
        );
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectations->expectNoRepositoryCalls();

        ($this->listener)($event);
    }

    public function testListenerSkipsWhenResponseStatusIsNotSuccessful(): void
    {
        $request = $this->schemathesisRequest('/api/users', ['email' => $this->faker->email()]);
        $event = $this->terminateEvent($request, Response::HTTP_BAD_REQUEST);

        $this->expectations->expectNoRepositoryCalls();

        ($this->listener)($event);
    }

    public function testListenerSkipsWhenHeaderValueIsNotCleanupUsers(): void
    {
        $request = $this->schemathesisRequest(
            '/api/users',
            ['email' => $this->faker->email()],
            $this->faker->word()
        );
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectations->expectNoRepositoryCalls();

        ($this->listener)($event);
    }

    public function testListenerSkipsWhenPathIsNotHandled(): void
    {
        $request = $this->schemathesisRequest('/api/health', ['email' => $this->faker->email()]);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectations->expectNoRepositoryCalls();

        ($this->listener)($event);
    }
}
