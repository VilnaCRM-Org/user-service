<?php

declare(strict_types=1);

namespace App\Tests\Behat\OAuthContext;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;

final class OAuthStateContext implements Context
{
    public function __construct(private OAuthContextState $state)
    {
    }

    /**
     * @BeforeScenario
     */
    public function resetOAuthState(BeforeScenarioScope $scope): void
    {
        $this->state->reset();
    }
}
