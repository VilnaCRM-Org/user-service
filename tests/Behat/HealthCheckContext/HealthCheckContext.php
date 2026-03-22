<?php

declare(strict_types=1);

namespace App\Tests\Behat\HealthCheckContext;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use PHPUnit\Framework\Assert;
use TwentytwoLabs\BehatOpenApiExtension\Context\RestContext;

final class HealthCheckContext implements Context
{
    private RestContext $restContext;

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope): void
    {
        $environment = $scope->getEnvironment();
        $this->restContext = $environment->getContext(RestContext::class);
    }

    /**
     * @Then the response should contain :text
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function theResponseShouldContain(string $text): void
    {
        $content = $this->restContext->getMink()
            ->getSession()
            ->getPage()
            ->getContent();
        Assert::assertStringContainsString($text, $content);
    }

    /**
     * @Then print last response
     */
    public function printLastResponse(): void
    {
        $content = $this->restContext->getMink()
            ->getSession()
            ->getPage()
            ->getContent();
        echo 'Response content: ' . $content . "\n";
    }

    /**
     * @Then the response status code should be :statusCode
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function theResponseStatusCodeShouldBe(int $statusCode): void
    {
        $actualStatusCode = $this->restContext->getMink()
            ->getSession()
            ->getStatusCode();

        if (
            $actualStatusCode !== $statusCode
            && filter_var(getenv('BEHAT_DEBUG'), FILTER_VALIDATE_BOOLEAN)
        ) {
            $content = $this->restContext->getMink()
                ->getSession()
                ->getPage()
                ->getContent();
            echo 'Response content: ' . $content . "\n";
            echo 'Expected: ' . $statusCode . ', Got: ' . $actualStatusCode
                . "\n";
        }

        Assert::assertSame($statusCode, $actualStatusCode);
    }
}
