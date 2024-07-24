<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Request\PasswordResetRequestFactory;
use App\Shared\Application\OpenApi\Factory\Response\PasswordResetTokenEmailSendFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserByEmailNotFoundResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserIsNotConfirmedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserTimedOutResponseFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class RequestPasswordResetEndpointFactory implements AbstractEndpointFactory
{
    private const ENDPOINT_URI = '/api/users/request-password-reset';

    private Response $passwordResetTokenEmailSend;
    private Response $userIsNotConfirmed;
    private Response $userWithProvidedEmailNotFound;
    private Response $timedOutResponse;

    private RequestBody $passwordResetRequest;

    public function __construct(
        private PasswordResetTokenEmailSendFactory $passwordResetTokenEmailSendFactory,
        private UserByEmailNotFoundResponseFactory $userWithProvidedEmailNotFoundResponseFactory,
        private UserTimedOutResponseFactory $timedOutResponseFactory,
        private UserIsNotConfirmedResponseFactory $userIsNotConfirmedResponseFactory,
        private PasswordResetRequestFactory $passwordResetRequestFactory,
    ) {
        $this->passwordResetTokenEmailSend =
            $this->passwordResetTokenEmailSendFactory->getResponse();
        $this->userIsNotConfirmed =
            $this->userIsNotConfirmedResponseFactory->getResponse();
        $this->userWithProvidedEmailNotFound =
            $this->userWithProvidedEmailNotFoundResponseFactory->getResponse();
        $this->timedOutResponse =
            $this->timedOutResponseFactory->getResponse();
        $this->passwordResetRequest =
            $this->passwordResetRequestFactory->getRequest();
    }

    public function createEndpoint(OpenApi $openApi): void
    {
        $pathItem = $openApi->getPaths()->getPath(self::ENDPOINT_URI);
        $operation = $pathItem->getPost();

        $openApi->getPaths()->addPath(
            self::ENDPOINT_URI,
            $pathItem->withPost(
                $operation
                    ->withDescription('Sends password reset confirmation email')
                    ->withSummary('Sends password reset confirmation email')
                    ->withRequestBody($this->passwordResetRequest)
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
            HttpResponse::HTTP_OK => $this->passwordResetTokenEmailSend,
            HttpResponse::HTTP_FORBIDDEN => $this->userIsNotConfirmed,
            HttpResponse::HTTP_NOT_FOUND => $this->userWithProvidedEmailNotFound,
            HttpResponse::HTTP_TOO_MANY_REQUESTS => $this->timedOutResponse,
        ];
    }
}
