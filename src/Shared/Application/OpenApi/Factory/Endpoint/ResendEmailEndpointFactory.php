<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Request\EmptyRequestFactory;
use App\Shared\Application\OpenApi\Factory\Response\EmailSendFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserNotFoundResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserTimedOutResponseFactory;
use App\Shared\Application\OpenApi\Factory\UriParameter\UuidUriParameterFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ResendEmailEndpointFactory implements AbstractEndpointFactory
{
    private string $endpointUri = '/users/{id}/resend-confirmation-email';

    private Parameter $uuidWithExamplePathParam;
    private Response $sendAgainResponse;
    private Response $userNotFoundResponse;
    private Response $timedOutResponse;

    public function __construct(
        string $apiPrefix,
        private UserNotFoundResponseFactory $userNotFoundResponseFactory,
        private EmailSendFactory $sendAgainResponseFactory,
        private UserTimedOutResponseFactory $timedOutResponseFactory,
        private EmptyRequestFactory $emptyRequestFactory,
        private UuidUriParameterFactory $parameterFactory
    ) {
        $this->endpointUri = $apiPrefix . $this->endpointUri;
        $this->uuidWithExamplePathParam =
            $this->parameterFactory->getParameter();
        $this->sendAgainResponse =
            $this->sendAgainResponseFactory->getResponse();
        $this->userNotFoundResponse =
            $this->userNotFoundResponseFactory->getResponse();
        $this->timedOutResponse = $this->timedOutResponseFactory->getResponse();
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
        return [
            HttpResponse::HTTP_OK => $this->sendAgainResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->userNotFoundResponse,
            HttpResponse::HTTP_TOO_MANY_REQUESTS => $this->timedOutResponse,
        ];
    }
}
