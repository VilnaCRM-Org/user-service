<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;

final class UserOperationsStateContext implements Context
{
    public function __construct(private UserOperationsState $state)
    {
    }

    /**
     * @BeforeScenario
     */
    public function resetUserOperationsState(BeforeScenarioScope $scope): void
    {
        $this->state->reset();
    }
}
