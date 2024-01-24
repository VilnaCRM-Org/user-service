<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Factory\EndpointFactory;

use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Infrastructure\OpenApi\Factory\RequestFactory\EmptyRequestFactory;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\EmailSendAgainResponseFactory;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\UserNotFoundResponseFactory;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\UserTimedOutResponseFactory;
use App\Shared\Infrastructure\OpenApi\Factory\UriParamFactory\UuidUriParameterFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ResendEmailEndpointFactory implements AbstractEndpointFactory
{
    private const ENDPOINT_URI = '/api/users/{id}/resend-confirmation-email';

    public function __construct(
        private UserNotFoundResponseFactory $userNotFoundResponseFactory,
        private EmailSendAgainResponseFactory $sendAgainResponseFactory,
        private UserTimedOutResponseFactory $timedOutResponseFactory,
        private EmptyRequestFactory $emptyRequestFactory,
        private UuidUriParameterFactory $parameterFactory
    ) {
        $this->uuidWithExamplePathParam = $this->parameterFactory->getParameter();
        $this->sendAgainResponse = $this->sendAgainResponseFactory->getResponse();
        $this->userNotFoundResponse = $this->userNotFoundResponseFactory->getResponse();
        $this->timedOutResponse = $this->timedOutResponseFactory->getResponse();
    }

    private Parameter $uuidWithExamplePathParam;
    private Response $sendAgainResponse;
    private Response $userNotFoundResponse;
    private Response $timedOutResponse;

    public function createEndpoint(OpenApi $openApi): void
    {
        $pathItem = $openApi->getPaths()->getPath(self::ENDPOINT_URI);
        $operation = $pathItem->getPost();

        $openApi->getPaths()->addPath(
            self::ENDPOINT_URI,
            $pathItem->withPost(
                $operation
                    ->withParameters([$this->uuidWithExamplePathParam])
                    ->withDescription('Resends confirmation email')
                    ->withSummary('Resends confirmation email')
                    ->withRequestBody(requestBody: $this->emptyRequestFactory
                        ->getRequest())
                    ->withResponses([
                        HttpResponse::HTTP_OK => $this->sendAgainResponse,
                        HttpResponse::HTTP_NOT_FOUND => $this->userNotFoundResponse,
                        HttpResponse::HTTP_TOO_MANY_REQUESTS => $this->timedOutResponse,
                    ])
            )
        );
    }
}
