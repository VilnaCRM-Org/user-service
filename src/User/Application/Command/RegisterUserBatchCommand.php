<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;
use App\User\Domain\Collection\UserCollection;

final class RegisterUserBatchCommand implements CommandInterface
{
    private RegisterUserBatchCommandResponse $response;

    public function __construct(
        public readonly UserCollection $users,
    ) {
    }

    public function getResponse(): RegisterUserBatchCommandResponse
    {
        return $this->response;
    }

    public function setResponse(
        RegisterUserBatchCommandResponse $response
    ): void {
        $this->response = $response;
    }
}
