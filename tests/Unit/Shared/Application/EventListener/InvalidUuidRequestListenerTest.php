<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\EventListener;

use App\Shared\Application\EventListener\InvalidUuidRequestListener;
use App\Tests\Unit\UnitTestCase;
use Ramsey\Uuid\Validator\GenericValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class InvalidUuidRequestListenerTest extends UnitTestCase
{
    public function testValidUuidLeavesRequestUntouched(): void
    {
        $listener = $this->createListener();
        $request = Request::create('/api/users/01998bc4-6b06-79a9-8fce-9f99d28af98a', 'GET');
        $request->attributes->set('id', '01998bc4-6b06-79a9-8fce-9f99d28af98a');

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testInvalidUuidSetsNotFoundResponse(): void
    {
        $listener = $this->createListener('Не знайдено');
        $request = Request::create('/api/users/null', 'GET');
        $request->attributes->set('id', 'null');

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener($event);

        $this->assertTrue($event->hasResponse());
        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('application/problem+json', $response->headers->get('Content-Type'));
        $decodedResponse = json_decode((string) $response->getContent(), true);
        $this->assertIsArray($decodedResponse);
        $this->assertSame('Не знайдено', $decodedResponse['detail'] ?? null);
        $this->assertSame('/errors/404', $decodedResponse['type'] ?? null);
        $this->assertSame(404, $decodedResponse['status'] ?? null);
    }

    public function testIgnoresSubRequest(): void
    {
        $listener = $this->createListener();
        $request = Request::create('/api/users/null', 'GET');
        $request->attributes->set('id', 'null');

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testNonStringIdentifierIsIgnored(): void
    {
        $listener = $this->createListener();
        $request = Request::create('/api/users/array', 'GET');
        $request->attributes->set('id', ['array']);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testNonUserPathIsIgnored(): void
    {
        $listener = $this->createListener();
        $request = Request::create('/api/oauth/token', 'GET');
        $request->attributes->set('id', 'not-a-uuid');

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    private function createListener(string $message = 'Not Found'): InvalidUuidRequestListener
    {
        return new InvalidUuidRequestListener(
            $this->createTranslator($message),
            new GenericValidator()
        );
    }

    private function createTranslator(string $message = 'Not Found'): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->with('error.not.found.http')->willReturn($message);

        return $translator;
    }
}
