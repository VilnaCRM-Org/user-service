<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;

final readonly class SudoModeResponseContext implements Context
{
    public function __construct(private UserOperationsState $state)
    {
    }

    /**
     * @Then the response should indicate sudo mode is required
     */
    public function theResponseShouldIndicateSudoModeIsRequired(): void
    {
        $responseContent = $this->state->response?->getContent();
        Assert::assertIsString($responseContent);
        Assert::assertStringContainsString('Re-authentication required.', $responseContent);
    }
}
