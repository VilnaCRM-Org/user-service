<?php

declare(strict_types=1);

namespace App\Tests\Behat\HealthCheckContext;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use TwentytwoLabs\BehatOpenApiExtension\Context\RestContext;

final class HealthCheckContext extends KernelTestCase implements Context
{
    private KernelInterface $kernelInterface;
    private RestContext $restContext;

    public function __construct(
        KernelInterface $kernel
    ) {
        parent::__construct();
        $this->kernelInterface = $kernel;
    }

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope): void
    {
        $environment = $scope->getEnvironment();
        $this->restContext = $environment->getContext(RestContext::class);
    }

    /**
     * @When :method request is sent to :path
     */
    public function sendRequestTo(string $method, string $path): void
    {
        $this->restContext->iSendARequestTo($method, $path);
    }

    /**
     * @Then the response status code should be :statusCode
     */
    public function theResponseStatusCodeShouldBe(string $statusCode): void
    {
        $receivedCode = $this->restContext->getSession()->getStatusCode();
        Assert::assertEquals($statusCode, $receivedCode);
    }

    /**
     * @Then the response body should contain :text
     */
    public function theResponseBodyShouldContain(string $text): void
    {
        $responseContent =
            $this->restContext->getSession()->getPage()->getContent();
        Assert::assertStringContainsString(
            $text,
            $responseContent,
            "The response body does not contain the expected text: '{$text}'."
        );
    }

    /**
     * @Given the cache is not working
     */
    public function theCacheIsNotWorking(): void
    {
        if ($this->controller) {
            $this->controller->setCacheFailure(true);
        }
    }

    /**
     * @Given the database is not available
     */
    public function theDatabaseIsNotAvailable(): void
    {
        if ($this->controller) {
            $this->controller->setDatabaseFailure(true);
        }
    }

    /**
     * @Given the message broker is not available
     */
    public function theMessageBrokerIsNotAvailable(): void
    {
        if ($this->controller) {
            $this->controller->setMessageBrokerFailure(true);
        }
    }
}
