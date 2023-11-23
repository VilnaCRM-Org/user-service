<?php

namespace App\Shared\Infrastructure;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

class OpenApiFactory implements OpenApiFactoryInterface
{

    public function __construct(private OpenApiFactoryInterface $decorated)
    {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);

        $openApi->getPaths()->addPath(
            '/token', new Model\PathItem(post:
                new Model\Operation(
                    responses: [new Response(description: 'Access token provided',
                        content: new ArrayObject([
                            'application/json' => [
                                'example' => ['token_type' => 'Bearer',
                                    'expires_in' => '3600',
                                    'access_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiJkYzBiYzYzMjNmMTZmZWNkNDIyNGEzODYwY2E4OTRjNSIsImp0aSI6IjY5MGZhODFmYWE0YjdlNmExZDZjNThjMzI5N2IzYjViYjAwMGVlMGExMTlmMGQ5YzNjZjkxMTIzY2JlMGRlZTI4MjcxMDYxYTNmYzU1NDM4IiwiaWF0IjoxNzAwNzUxOTU0LjgzMDE5OSwibmJmIjoxNzAwNzUxOTU0LjgzMDIxOCwiZXhwIjoxNzAwNzU1NTU0Ljc5NDg1NSwic3ViIjoiIiwic2NvcGVzIjpbIkVNQUlMIl19.cDUpuOfe4Bazx-N241qYDW0rktSJfeVtnZckDMFt_dxy7pHByupef5JkC1GOZWt8GkW-Uc1d5vaGjopMowjFuQEWS-OowCjj5WHrS528UwwKFHevrLpAAR-GDfMpOu97mMd4XMhXNKIcp0rGutoWeh4aHM90p815q3YTiFtTidGksYqhLZgUzusyG_iLNzLDTbCME-9UMgk8rtjuvHrldRAMnbCloBURbyOM2x7ObFpnjosobX2D5upMbsGAXenswiZM8CUVVbUPPW358Q3ygGWiA1lN4w0WFSjba7NZdZ3fh5Ht--fcQHCae_ZNQp-SwSy5xe2vRKIRaxilWr-x7g'],
                            ],
                        ]),)],
                    summary: 'Requests for access token', description: 'Request for access token',
                    requestBody: new Model\RequestBody(description: 'Request for access token', content: new ArrayObject([
                        'application/json' => [
                            'example' => ['grant_type' => 'client_credentials',
                                'client_id' => 'dc0bc6323f16fecd4224a3860ca894c5',
                                'client_secret' => '8897b24436ac63e457fbd7d0bd5b678686c0cb214ef92fa9e8464fc777ec51a79507182836799d166776094c5b8bccc00e4d4cbb9a136a5d244349c6eee67b8c'],
                        ],
                    ])
                    ))));

        return $openApi;
    }
}