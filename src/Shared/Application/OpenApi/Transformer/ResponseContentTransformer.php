<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Transformer;

use ApiPlatform\OpenApi\Model\Response;
use ArrayObject;

use function in_array;

final class ResponseContentTransformer
{
    private const NO_CONTENT_STATUSES = ['204', '205'];

    /**
     * @param Response|ArrayObject<string, string|int|bool|array<string, string>>|array<string, string|int|bool|array<string, string>>|string|int|bool|null $response
     *
     * @return Response|ArrayObject<string, string|int|bool|array<string, string>>|array<string, string|int|bool|array<string, string>>|string|int|bool|null
     */
    public function transform(
        Response|ArrayObject|array|string|int|bool|null $response,
        string $status
    ): Response|ArrayObject|array|string|int|bool|null {
        if (!$response instanceof Response) {
            return $response;
        }

        if (!in_array($status, self::NO_CONTENT_STATUSES, true)) {
            return $response;
        }

        return $response->withContent(new ArrayObject());
    }
}
