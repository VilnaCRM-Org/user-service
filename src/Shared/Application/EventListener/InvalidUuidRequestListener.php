<?php

declare(strict_types=1);

namespace App\Shared\Application\EventListener;

use Ramsey\Uuid\Validator\GenericValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

final class InvalidUuidRequestListener
{
    private const USER_PATH_PREFIX = '/api/users/';

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly GenericValidator $uuidValidator
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$this->isSupportedRequest($event)) {
            return;
        }

        $message = $this->translator->trans('error.not.found.http');

        $event->setResponse($this->createNotFoundResponse($message));
    }

    private function isSupportedRequest(RequestEvent $event): bool
    {
        if (!$event->isMainRequest()) {
            return false;
        }

        $request = $event->getRequest();

        return $this->supports(
            $request->getPathInfo(),
            $request->attributes->get('id')
        );
    }

    private function supports(string $path, mixed $id): bool
    {
        return match (true) {
            !str_starts_with($path, self::USER_PATH_PREFIX) => false,
            !is_string($id) => false,
            default => !$this->uuidValidator->validate($id),
        };
    }

    private function createNotFoundResponse(string $message): JsonResponse
    {
        return new JsonResponse(
            [
                'type' => '/errors/404',
                'title' => $message,
                'detail' => $message,
                'status' => 404,
            ],
            404,
            ['Content-Type' => 'application/problem+json']
        );
    }
}
