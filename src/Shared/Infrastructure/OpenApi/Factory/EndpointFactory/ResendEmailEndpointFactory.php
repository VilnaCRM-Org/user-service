<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Factory\EndpointFactory;

use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Infrastructure\OpenApi\Factory\RequestFactory\EmptyRequestFactory;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\EmailSendAgainResponseFactory;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\UserNotFoundResponseFactory;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\UserTimedOutResponseFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ResendEmailEndpointFactory implements AbstractEndpointFactory
{
    public function __construct(
        private UserNotFoundResponseFactory $userNotFoundResponseFactory,
        private EmailSendAgainResponseFactory $sendAgainResponseFactory,
        private UserTimedOutResponseFactory $timedOutResponseFactory,
        private EmptyRequestFactory $emptyRequestFactory
    ) {
    }

    public function createEndpoint(OpenApi $openApi): void
    {
        $pathItem = $openApi->getPaths()->getPath('/api/users/{id}/resend-confirmation-email');
        $operation = $pathItem->getPost();

        $UuidWithExamplePathParam = new Model\Parameter(
            name: 'id',
            in: 'path',
            description: 'User identifier',
            required: true,
            example: '2b10b7a3-67f0-40ea-a367-44263321592a'
        );

        $openApi->getPaths()->addPath(
            '/api/users/{id}/resend-confirmation-email',
            $pathItem->withPost(
                $operation
                    ->withParameters([$UuidWithExamplePathParam])
                    ->withDescription('Resends confirmation email')
                    ->withSummary('Resends confirmation email')
                    ->withRequestBody(requestBody:
                        $this->emptyRequestFactory->getRequest())
                    ->withResponses([
                        HttpResponse::HTTP_OK =>
                            $this->sendAgainResponseFactory->getResponse(),
                        HttpResponse::HTTP_NOT_FOUND =>
                            $this->userNotFoundResponseFactory->getResponse(),
                        HttpResponse::HTTP_TOO_MANY_REQUESTS =>
                            $this->timedOutResponseFactory->getResponse(),
                    ])
            )
        );
    }
}
