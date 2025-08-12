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
     * @Then the response should contain :text
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
     * @Given the cache is not working
     */
    public function theCacheIsNotWorking(): void
    {
        putenv('CACHE_FAILURE=true');
    }

    /**
     * @Given the database is not available
     */
    public function theDatabaseIsNotAvailable(): void
    {
        putenv('DATABASE_FAILURE=true');
    }

    /**
     * @Given the message broker is not available
     */
    public function theMessageBrokerIsNotAvailable(): void
    {
        putenv('MESSAGE_BROKER_FAILURE=true');
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
     */
    public function theResponseStatusCodeShouldBe(int $statusCode): void
    {
        $actualStatusCode = $this->restContext->getMink()
            ->getSession()
            ->getStatusCode();

        if ($actualStatusCode !== $statusCode) {
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

    /**
     * @AfterScenario
     */
    public function cleanupEnvironmentVariables(): void
    {
        putenv('CACHE_FAILURE');
        putenv('DATABASE_FAILURE');
        putenv('MESSAGE_BROKER_FAILURE');
    }
}
