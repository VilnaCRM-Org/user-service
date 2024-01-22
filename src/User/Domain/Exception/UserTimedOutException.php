<?php

declare(strict_types=1);

namespace App\User\Domain\Exception;

final class UserTimedOutException extends \RuntimeException
{
    public function __construct(\DateTimeImmutable $timeOutTill)
    {
        parent::__construct(
            'Cannot send new email till '.
            $timeOutTill->format('Y M d H:i:s')
        );
    }
}
