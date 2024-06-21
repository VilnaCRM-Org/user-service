<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Request\ConfirmUserRequestFactory;
use App\Shared\Application\OpenApi\Factory\Response\TokenNotFoundFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserConfirmedFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ConfirmUserEndpointFactory implements AbstractEndpointFactory
{
    private const ENDPOINT_URI = '/api/users/confirm';

    private Response $userConfirmedResponse;
    private Response $notFoundResponse;

    private RequestBody $confirmUserRequest;

    public function __construct(
        private TokenNotFoundFactory $tokenNotFoundResponseFactory,
        private UserConfirmedFactory $userConfirmedResponseFactory,
        private ConfirmUserRequestFactory $confirmUserRequestFactory
    ) {
        $this->userConfirmedResponse =
            $this->userConfirmedResponseFactory->getResponse();
        $this->notFoundResponse =
            $this->tokenNotFoundResponseFactory->getResponse();
        $this->confirmUserRequest =
            $this->confirmUserRequestFactory->getRequest();
    }

    public function createEndpoint(OpenApi $openApi): void
    {
        $pathItem = $openApi->getPaths()->getPath(self::ENDPOINT_URI);
        $operationPatch = $pathItem->getPatch();

        $openApi->getPaths()->addPath(
            self::ENDPOINT_URI,
            $pathItem->withPatch(
                $this->getPatchOperation(
                    $operationPatch->withRequestBody($this->confirmUserRequest)
                ),
            )
        );
    }

    private function getPatchOperation(Operation $operation): Operation
    {
        return $operation
            ->withDescription('Confirms the User')
            ->withSummary('Confirms the User')
            ->withResponses(
                [
                    HttpResponse::HTTP_OK => $this->userConfirmedResponse,
                    HttpResponse::HTTP_NOT_FOUND => $this->notFoundResponse,
                ],
            );
    }
}
