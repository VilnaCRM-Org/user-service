<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Cleaner;

use ApiPlatform\OpenApi\Model\Response;
use ArrayObject;

use function in_array;

final class ResponseContentCleaner
{
    private const NO_CONTENT_STATUSES = ['204', '205'];

    public function clean(
        Response|ArrayObject|array|string|int|bool|null $response,
        string $status
    ): Response|ArrayObject|array|string|int|bool|null {
        return match (true) {
            !$response instanceof Response => $response,
            !in_array($status, self::NO_CONTENT_STATUSES, true) => $response,
            default => $response->withContent(new ArrayObject()),
        };
    }
}
