<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\CompanySubdomain\SomeModule\Application\Command\SomeCommand;

class SomeEntity
{
    public function someDomainLogic()
    {
        $command = new SomeCommand(); // This is a violation
        $command->execute();
    }
}
