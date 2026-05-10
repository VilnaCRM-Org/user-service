<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserGraphQLContext;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;

final class UserGraphQLStateContext implements Context
{
    public function __construct(private UserGraphQLState $state)
    {
    }

    /**
     * @BeforeScenario
     */
    public function resetGraphQlState(BeforeScenarioScope $scope): void
    {
        $this->state->reset();
    }
}
