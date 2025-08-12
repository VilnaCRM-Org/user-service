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
        $body = $this->buildBody();
        $this->sendRequest($method, $path, $body);
    }

    /**
     * @Then the error message should be :errorMessage
     */
    public function theErrorMessageShouldBe(string $errorMessage): void
    {
        $this->assertContentContains($errorMessage);
    }

    /**
     * @Then the error message should contain :partialMessage
     */
    public function theErrorMessageShouldContain(string $partialMessage): void
    {
        $content = $this->getPageContent();
        $decoded = json_decode($content, true);

        $errorMessage = '';
        if ($decoded && isset($decoded['detail'])) {
            $errorMessage = $decoded['detail'];
        } else {
            $errorMessage = $content;
        }

        Assert::assertStringContainsString($partialMessage, $errorMessage);
    }

    /**
     * @Then violation should be :violation
     */
    public function theViolationShouldBe(string $violation): void
    {
        $this->assertContentContains($violation);
        $this->violationNum++;
    }

    /**
     * @Then the response should contain a list of users
     */
    public function theResponseShouldContainAListOfUsers(): void
    {
        $content = $this->getPageContent();
        Assert::assertStringContainsString('[', $content);
    }

    /**
     * @Then user should be timed out
     */
    public function userShouldBeTimedOut(): void
    {
        $content = $this->getPageContent();
        Assert::assertStringContainsString('Too Many Requests', $content);
    }

    /**
     * @Then user with email :email and initials :initials should be returned
     */
    public function userWithEmailAndInitialsShouldBeReturned(
        string $email,
        string $initials
    ): void {
        $content = $this->getPageContent();
        Assert::assertStringContainsString($email, $content);
        Assert::assertStringContainsString($initials, $content);
    }

    /**
     * @Then user with id :id should be returned
     */
    public function userWithIdShouldBeReturned(string $id): void
    {
        $content = $this->getPageContent();
        Assert::assertStringContainsString($id, $content);
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
            echo 'Expected: ' . $statusCode . ', Got: ' .
                $actualStatusCode . "\n";
        }

        Assert::assertSame($statusCode, $actualStatusCode);
    }

    private function getPageContent(): string
    {
        return $this->restContext
            ->getMink()
            ->getSession()
            ->getPage()
            ->getContent();
    }

    private function assertContentContains(string $expectedContent): void
    {
        $content = $this->getPageContent();
        $decoded = json_decode($content, true);

        if ($decoded && isset($decoded['detail'])) {
            Assert::assertStringContainsString(
                $expectedContent,
                $decoded['detail']
            );
            return;
        }

        Assert::assertStringContainsString($expectedContent, $content);
    }

    /**
     * @return array<string, string>
     */
    private function buildHeaders(string $method): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Accept-Language' => $this->language,
        ];

        if ($method === 'PATCH') {
            $headers['Content-Type'] = 'application/merge-patch+json';
        } else {
            $headers['Content-Type'] = 'application/json';
        }

        return $headers;
    }

    private function buildBody(): string
    {
        if ($this->requestBody === null) {
            return '';
        }

        return $this->requestBody->getJson();
    }

    private function sendRequest(
        string $method,
        string $path,
        string $body
    ): void {
        if ($this->isRequestBodyMethod($method)) {
            $this->sendRequestWithBody($method, $path, $body);
            return;
        }

        $this->sendRequestWithoutBody($method, $path);
    }

    private function isRequestBodyMethod(string $method): bool
    {
        return in_array($method, ['POST', 'PUT', 'PATCH']);
    }

    private function sendRequestWithBody(
        string $method,
        string $path,
        string $body
    ): void {
        $headers = $this->buildHeaders($method);
        $this->addHeaders($headers);

        $bodyContent = $body !== '' ? $body : '{}';
        $pyStringBody = new PyStringNode([$bodyContent], 0);

        $this->restContext->iSendARequestToWithBody(
            $method,
            $path,
            $pyStringBody
        );
    }

    private function sendRequestWithoutBody(string $method, string $path): void
    {
        $headers = $this->buildHeaders($method);
        $this->addHeaders($headers);

        $this->restContext->iSendARequestTo($method, $path);
    }

    /**
     * @param array<string, string> $headers
     */
    private function addHeaders(array $headers): void
    {
        foreach ($headers as $name => $value) {
            $this->restContext->iAddHeaderEqualTo($name, $value);
        }
    }
}
