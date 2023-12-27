<?php

namespace App\User\Infrastructure\Exception;

class UserTimedOutException extends \RuntimeException
{
    public function __construct(\DateTime $timeOutTill)
    {
        parent::__construct('Cannot send new email till '.$timeOutTill->format('d M Y H:i:s'));
    }
}
