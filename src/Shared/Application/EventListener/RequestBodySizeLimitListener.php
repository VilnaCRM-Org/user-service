<?php

declare(strict_types=1);

namespace App\Shared\Application\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final readonly class RequestBodySizeLimitListener
{
    public function __construct(private int $maxBodySizeBytes = 65_536)
    {
    }

    public function __invoke(RequestEvent $event): void
    {
        if ($event->getRequestType() !== HttpKernelInterface::MAIN_REQUEST) {
            return;
        }

        if ($this->isBodyTooLarge($event->getRequest())) {
            throw new HttpException(413, 'Request body too large.');
        }
    }

    private function isBodyTooLarge(Request $request): bool
    {
        $contentLength = $request->headers->get('Content-Length');
        if ($contentLength !== null && (int) $contentLength > $this->maxBodySizeBytes) {
            return true;
        }

        $content = $request->getContent();

        return strlen($content) > $this->maxBodySizeBytes;
    }
}
