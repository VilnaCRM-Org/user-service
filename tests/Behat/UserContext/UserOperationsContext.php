<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\Tests\Behat\UserContext\Input\ConfirmUserInput;
use App\Tests\Behat\UserContext\Input\CreateUserBatchInput;
use App\Tests\Behat\UserContext\Input\CreateUserInput;
use App\Tests\Behat\UserContext\Input\EmptyInput;
use App\Tests\Behat\UserContext\Input\RequestInput;
use App\Tests\Behat\UserContext\Input\UpdateUserInput;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\PyStringNode;
use PHPUnit\Framework\Assert;
use TwentytwoLabs\BehatOpenApiExtension\Context\RestContext;

final class UserOperationsContext implements Context
{
    private ?RequestInput $requestBody;
    private int $violationNum;
    private string $language;
    private RestContext $restContext;

    public function __construct()
    {
        $this->requestBody = null;
        $this->violationNum = 0;
        $this->language = 'en';
    }

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope): void
    {
        $environment = $scope->getEnvironment();
        $this->restContext = $environment->getContext(RestContext::class);
        $this->violationNum = 0;
    }

    /**
     * @Given updating user with email :email, initials :initials, oldPassword :oldPassword, newPassword :newPassword
     */
    public function updatingUser(
        string $email,
        string $initials,
        string $oldPassword,
        string $newPassword
    ): void {
        $this->requestBody = new UpdateUserInput(
            $email,
            $initials,
            $oldPassword,
            $newPassword
        );
    }

    /**
     * @Given updating user with oldPassword :oldPassword
     */
    public function updatingUserWithNoOptionalFields(string $oldPassword): void
    {
        $this->requestBody = new UpdateUserInput(
            '',
            '',
            $oldPassword,
            ''
        );
    }

    /**
     * @Given creating user with email :email, initials :initials, password :password
     */
    public function creatingUser(
        string $email,
        string $initials,
        string $password
    ): void {
        $this->requestBody = new CreateUserInput($email, $initials, $password);
    }

    /**
     * @Given sending a batch of users
     */
    public function sendingUserBatch(): void
    {
        $this->requestBody = new CreateUserBatchInput();
    }

    /**
     * @Given with user with email :email, initials :initials, password :password
     */
    public function addUserToBatch(
        string $email,
        string $initials,
        string $password
    ): void {
        $this->requestBody->addUser([
            'email' => $email,
            'initials' => $initials,
            'password' => $password,
        ]);
    }

    /**
     * @Given confirming user with token :token
     */
    public function confirmingUserWithToken(string $token): void
    {
        $this->requestBody = new ConfirmUserInput($token);
    }

    /**
     * @Given sending empty body
     */
    public function sendingEmptyBody(): void
    {
        $this->requestBody = new EmptyInput();
    }

    /**
     * @Given with language :lang
     */
    public function setLanguage(string $lang): void
    {
        $this->language = $lang;
    }

    /**
     * @When :method request is send to :path
     */
    public function requestSendTo(string $method, string $path): void
    {
        $headers = $this->buildHeaders($method);
        foreach ($headers as $name => $value) {
            $this->restContext->iAddHeaderEqualTo($name, $value);
        }
        $body = $this->buildBody();
        $pyStringBody = new PyStringNode(explode(PHP_EOL, $body), 0);

        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $this->restContext->iSendARequestToWithBody(
                $method,
                $path,
                $pyStringBody
            );
         return; 
        }
            $this->restContext->iSendARequestTo($method, $path);
    }

    /**
     * @Then the error message should be :errorMessage
     */
    public function theErrorMessageShouldBe(string $errorMessage): void
    {
        $content = $this->restContext
            ->getMink()
            ->getSession()
            ->getPage()
            ->getContent();
        Assert::assertStringContainsString($errorMessage, $content);
    }

    /**
     * @Then violation should be :violation
     */
    public function theViolationShouldBe(string $violation): void
    {
        $content = $this->restContext
            ->getMink()
            ->getSession()
            ->getPage()
            ->getContent();
        Assert::assertStringContainsString($violation, $content);
        $this->violationNum++;
    }

    /**
     * @Then the response should contain a list of users
     */
    public function theResponseShouldContainAListOfUsers(): void
    {
        $content = $this->restContext
            ->getMink()
            ->getSession()
            ->getPage()
            ->getContent();
        Assert::assertStringContainsString('[', $content);
    }

    /**
     * @Then user should be timed out
     */
    public function userShouldBeTimedOut(): void
    {
        $content = $this->restContext
            ->getMink()
            ->getSession()
            ->getPage()
            ->getContent();
        Assert::assertStringContainsString(
            'Cannot send new email till',
            $content
        );
    }

    /**
     * @Then user with email :email and initials :initials should be returned
     */
    public function userWithEmailAndInitialsShouldBeReturned(
        string $email,
        string $initials
    ): void {
        $content = $this->restContext
            ->getMink()
            ->getSession()
            ->getPage()
            ->getContent();
        Assert::assertStringContainsString(
            $email,
            $content
        );
        Assert::assertStringContainsString(
            $initials,
            $content
        );
    }

    /**
     * @Then user with id :id should be returned
     */
    public function userWithIdShouldBeReturned(string $id): void
    {
        $content = $this->restContext
            ->getMink()
            ->getSession()
            ->getPage()
            ->getContent();
        Assert::assertStringContainsString($id, $content);
    }

    /**
     * @return array<string, string>
     */
    private function buildHeaders(string $method): array
    {
        $contentType = $method === 'PATCH'
            ? 'application/merge-patch+json'
            : 'application/json';

        return [
            'Accept' => 'application/json',
            'Content-Type' => $contentType,
            'Accept-Language' => $this->language,
        ];
    }

    private function buildBody(): string
    {
        return $this->requestBody
            ? json_encode($this->requestBody, JSON_UNESCAPED_UNICODE)
            : '';
    }
}
