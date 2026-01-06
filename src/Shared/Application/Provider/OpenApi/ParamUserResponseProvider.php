<?php

declare(strict_types=1);

namespace App\Shared\Application\Provider\OpenApi;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Factory\Response\AbstractResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserDeletedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserNotFoundResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserReturnedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserUpdatedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;

final class ParamUserResponseProvider
{
    /** @var array<string, Response> */
    private array $responses = [];

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
        return $this->getCachedResponse(
            'badRequest',
            $this->badRequestResponseFactory
        );
    }

    public function userNotFound(): Response
    {
        return $this->getCachedResponse(
            'userNotFound',
            $this->userNotFoundResponseFactory
        );
    }

    public function validationError(): Response
    {
        return $this->getCachedResponse(
            'validationError',
            $this->validationErrorResponseFactory
        );
    }

    public function userDeleted(): Response
    {
        return $this->getCachedResponse(
            'userDeleted',
            $this->deletedResponseFactory
        );
    }

    public function userUpdated(): Response
    {
        return $this->getCachedResponse(
            'userUpdated',
            $this->userUpdatedResponseFactory
        );
    }

    public function userReturned(): Response
    {
        return $this->getCachedResponse(
            'userReturned',
            $this->userReturnedResponseFactory
        );
    }

    private function getCachedResponse(
        string $key,
        AbstractResponseFactory $factory
    ): Response {
        return $this->responses[$key] ??= $factory->getResponse();
    }
}
