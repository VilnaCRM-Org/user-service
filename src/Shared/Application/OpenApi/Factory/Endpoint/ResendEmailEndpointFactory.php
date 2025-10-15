<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Request\EmptyRequestFactory;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\EmailSendFactory;
use App\Shared\Application\OpenApi\Factory\Response\UnsupportedMediaTypeFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserNotFoundResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserTimedOutResponseFactory;
use App\Shared\Application\OpenApi\Factory\UriParameter\UuidUriParameterFactory;
use Symfony\Component\HttpFoundation\Response as Http;

final class ResendEmailEndpointFactory implements AbstractEndpointFactory
{
    private string $endpointUri = '/users/{id}/resend-confirmation-email';

    private Parameter $uuidWithExamplePathParam;
    private Response $sendAgainResponse;
    private Response $badRequestResponse;
    private Response $userNotFoundResponse;
    private Response $timedOutResponse;
    private Response $unsupportedMediaResponse;

    public function __construct(
        string $apiPrefix,
        private UserNotFoundResponseFactory $userNotFoundResponseFactory,
        private EmailSendFactory $sendAgainResponseFactory,
        private UserTimedOutResponseFactory $timedOutResponseFactory,
        private UnsupportedMediaTypeFactory $unsupportedMediaTypeFactory,
        private BadRequestResponseFactory $badRequestResponseFactory,
        private EmptyRequestFactory $emptyRequestFactory,
        private UuidUriParameterFactory $parameterFactory
    ) {
        $this->endpointUri = $apiPrefix . $this->endpointUri;
        $this->uuidWithExamplePathParam =
            $this->parameterFactory->getParameter();
        $this->sendAgainResponse =
            $this->sendAgainResponseFactory->getResponse();
        $this->badRequestResponse =
            $badRequestResponseFactory->getResponse();
        $this->userNotFoundResponse =
            $this->userNotFoundResponseFactory->getResponse();
        $this->timedOutResponse = $this->timedOutResponseFactory->getResponse();
        $this->unsupportedMediaResponse =
            $this->unsupportedMediaTypeFactory->getResponse();
    }

    public function createEndpoint(OpenApi $openApi): void
    {
        $pathItem = $openApi->getPaths()->getPath($this->endpointUri);
        $operation = $pathItem->getPost();

        $openApi->getPaths()->addPath(
            $this->endpointUri,
            $pathItem->withPost(
                $operation
                    ->withParameters([$this->uuidWithExamplePathParam])
                    ->withDescription('Resends confirmation email')
                    ->withSummary('Resends confirmation email')
                    ->withRequestBody(requestBody: $this->emptyRequestFactory
                        ->getRequest())
                    ->withResponses($this->getResponses())
            )
        );
    }

    /**
     * @return array<int,Response>
     */
    private function getResponses(): array
    {
        $unsupportedMediaResponse = $this->unsupportedMediaResponse;

        return [
            Http::HTTP_OK => $this->sendAgainResponse,
            Http::HTTP_BAD_REQUEST => $this->badRequestResponse,
            Http::HTTP_NOT_FOUND => $this->userNotFoundResponse,
            Http::HTTP_TOO_MANY_REQUESTS => $this->timedOutResponse,
            Http::HTTP_UNSUPPORTED_MEDIA_TYPE => $unsupportedMediaResponse,
        ];
    }
}
