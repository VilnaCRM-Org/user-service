<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Request\ConfirmPasswordResetRequestFactory;
use App\Shared\Application\OpenApi\Factory\Response\TokenNotFoundFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserNotFoundResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserUpdatedResponseFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ConfirmPasswordResetEndpointFactory implements
    AbstractEndpointFactory
{
    private const ENDPOINT_URI = '/api/users/confirm-password-reset';

    private Response $userUpdatedResponse;
    private Response $tokenNotFound;
    private Response $userNotFoundResponse;

    private RequestBody $confirmPasswordResetRequest;

    public function __construct(
        private UserUpdatedResponseFactory $userUpdatedResponseFactory,
        private TokenNotFoundFactory $tokenNotFoundFactory,
        private UserNotFoundResponseFactory $userNotFoundResponseFactory,
        private ConfirmPasswordResetRequestFactory $confirmPasswordResetRequestFactory,
    ) {
        $this->userUpdatedResponse
            = $this->userUpdatedResponseFactory->getResponse();
        $this->tokenNotFound
            = $this->tokenNotFoundFactory->getResponse();
        $this->userNotFoundResponse
            = $this->userNotFoundResponseFactory->getResponse();
        $this->confirmPasswordResetRequest
            = $this->confirmPasswordResetRequestFactory->getRequest();
    }

    public function createEndpoint(OpenApi $openApi): void
    {
        $pathItem = $openApi->getPaths()->getPath(self::ENDPOINT_URI);
        $operation = $pathItem->getPatch();

        $openApi->getPaths()->addPath(
            self::ENDPOINT_URI,
            $pathItem->withPatch(
                $operation
                    ->withDescription('Resets user password')
                    ->withSummary('Resets user password')
                    ->withRequestBody($this->confirmPasswordResetRequest)
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
            HttpResponse::HTTP_OK => $this->userUpdatedResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->tokenNotFound,
            HttpResponse::HTTP_GONE => $this->userNotFoundResponse,
        ];
    }
}
