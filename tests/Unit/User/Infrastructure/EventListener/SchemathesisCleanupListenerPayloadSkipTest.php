<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\EventListener;

use Symfony\Component\HttpFoundation\Response;

final class SchemathesisCleanupListenerPayloadSkipTest extends SchemathesisCleanupListenerTestCase
{
    public function testListenerSkipsWhenBodyIsEmpty(): void
    {
        $request = $this->schemathesisRequest('/api/users', null);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectations->expectNoRepositoryCalls();

        ($this->listener)($event);
    }

    public function testListenerSkipsWhenJsonIsMalformed(): void
    {
        $request = $this->schemathesisRequest('/api/users', '{invalid');
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectations->expectNoRepositoryCalls();

        ($this->listener)($event);
    }

    public function testListenerSkipsWhenPayloadIsNotArray(): void
    {
        $payload = json_encode('string', JSON_THROW_ON_ERROR);
        $request = $this->schemathesisRequest('/api/users', $payload);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectations->expectNoRepositoryCalls();

        ($this->listener)($event);
    }

    public function testListenerSkipsWhenBatchUsersNotArray(): void
    {
        $request = $this->schemathesisRequest('/api/users/batch', ['users' => 'string']);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectations->expectNoRepositoryCalls();

        ($this->listener)($event);
    }

    public function testListenerSkipsWhenBatchEntriesKeyMissing(): void
    {
        $request = $this->schemathesisRequest('/api/users/batch', ['something' => 'else']);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectations->expectNoRepositoryCalls();

        ($this->listener)($event);
    }

    public function testListenerSkipsWhenBatchEntriesNotArray(): void
    {
        $request = $this->schemathesisRequest('/api/users/batch', ['users' => ['string']]);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectations->expectNoRepositoryCalls();

        ($this->listener)($event);
    }

    public function testListenerSkipsWhenEmailIsMissing(): void
    {
        $payload = ['initials' => $this->faker->lexify('????????')];
        $request = $this->schemathesisRequest('/api/users', $payload);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectations->expectNoRepositoryCalls();

        ($this->listener)($event);
    }

    public function testListenerSkipsWhenEmailIsNotString(): void
    {
        $request = $this->schemathesisRequest('/api/users', ['email' => ['value']]);
        $event = $this->terminateEvent($request, Response::HTTP_CREATED);

        $this->expectations->expectNoRepositoryCalls();

        ($this->listener)($event);
    }
}
