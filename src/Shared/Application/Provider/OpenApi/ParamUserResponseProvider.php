<?php

declare(strict_types=1);

namespace App\Shared\Application\Provider\OpenApi;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserDeletedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserNotFoundResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserReturnedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserUpdatedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;

final class ParamUserResponseProvider
{
    private ?Response $badRequest = null;
    private ?Response $userNotFound = null;
    private ?Response $validationError = null;
    private ?Response $userDeleted = null;
    private ?Response $userUpdated = null;
    private ?Response $userReturned = null;

    public function __construct(
        private ValidationErrorFactory $validationErrorResponseFactory,
        private BadRequestResponseFactory $badRequestResponseFactory,
        private UserNotFoundResponseFactory $userNotFoundResponseFactory,
        private UserDeletedResponseFactory $deletedResponseFactory,
        private UserUpdatedResponseFactory $userUpdatedResponseFactory,
        private UserReturnedResponseFactory $userReturnedResponseFactory
    ) {
    }

    public function badRequest(): Response
    {
        return $this->badRequest ??=
            $this->badRequestResponseFactory->getResponse();
    }

    public function userNotFound(): Response
    {
        return $this->userNotFound ??=
            $this->userNotFoundResponseFactory->getResponse();
    }

    public function validationError(): Response
    {
        return $this->validationError ??=
            $this->validationErrorResponseFactory->getResponse();
    }

    public function userDeleted(): Response
    {
        return $this->userDeleted ??=
            $this->deletedResponseFactory->getResponse();
    }

    public function userUpdated(): Response
    {
        return $this->userUpdated ??=
            $this->userUpdatedResponseFactory->getResponse();
    }

    public function userReturned(): Response
    {
        return $this->userReturned ??=
            $this->userReturnedResponseFactory->getResponse();
    }
}
