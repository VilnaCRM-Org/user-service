<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Request\ConfirmUserRequestFactory;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\TokenNotFoundFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserConfirmedFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use Symfony\Component\HttpFoundation\Response as Http;

final class ConfirmUserEndpointFactory implements AbstractEndpointFactory
{
    private string $endpointUri = '/users/confirm';

    private Response $userConfirmedResponse;
    private Response $notFoundResponse;
    private Response $validationErrorResponse;
    private Response $badRequestResponse;

    private RequestBody $confirmUserRequest;

    public function __construct(
        string $apiPrefix,
        TokenNotFoundFactory $tokenNotFoundResponseFactory,
        BadRequestResponseFactory $badRequestResponseFactory,
        UserConfirmedFactory $userConfirmedResponseFactory,
        ValidationErrorFactory $validationErrorFactory,
        ConfirmUserRequestFactory $confirmUserRequestFactory
    ) {
        $this->endpointUri = $apiPrefix . $this->endpointUri;
        $this->userConfirmedResponse =
            $userConfirmedResponseFactory->getResponse();
        $this->notFoundResponse =
            $tokenNotFoundResponseFactory->getResponse();
        $this->validationErrorResponse =
            $validationErrorFactory->getResponse();
        $this->badRequestResponse =
            $badRequestResponseFactory->getResponse();
        $this->confirmUserRequest =
            $confirmUserRequestFactory->getRequest();
    }

    #[\Override]
    public function createEndpoint(OpenApi $openApi): void
    {
        $pathItem = $openApi->getPaths()->getPath($this->endpointUri);
        $operationPatch = $pathItem->getPatch();

        $openApi->getPaths()->addPath(
            $this->endpointUri,
            $pathItem->withPatch(
                $this->getPatchOperation(
                    $operationPatch->withRequestBody($this->confirmUserRequest)
                ),
            )
        );
    }

    private function getPatchOperation(Operation $operation): Operation
    {
        $validationResponse = $this->validationErrorResponse;

        return $operation
            ->withDescription('Confirms the User')
            ->withSummary('Confirms the User')
            ->withResponses(
                [
                    Http::HTTP_OK => $this->userConfirmedResponse,
                    Http::HTTP_BAD_REQUEST => $this->badRequestResponse,
                    Http::HTTP_NOT_FOUND => $this->notFoundResponse,
                    Http::HTTP_UNPROCESSABLE_ENTITY => $validationResponse,
                ],
            );
    }
}
