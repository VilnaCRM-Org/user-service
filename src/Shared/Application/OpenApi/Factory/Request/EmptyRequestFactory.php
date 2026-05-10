<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Request;

use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Schema;
use App\Shared\Application\OpenApi\Builder\RequestBuilder;
use ArrayObject;

final class EmptyRequestFactory implements AbstractRequestFactory
{
    public function __construct(private RequestBuilder $requestBuilder)
    {
    }

    #[\Override]
    public function getRequest(): RequestBody
    {
        $schema = new Schema();
        $schema['type'] = 'object';
        $schema['properties'] = new ArrayObject();
        $this->requestBuilder->build([]);
        return new RequestBody(
            description: 'This operation does not expect a body.',
            content: new ArrayObject([
                'application/json' => new MediaType(
                    schema: $schema,
                ),
            ]),
            required: false,
        );
    }
}
