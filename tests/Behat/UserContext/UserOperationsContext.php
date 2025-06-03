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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Serializer\SerializerInterface;
use TwentytwoLabs\BehatOpenApiExtension\Context\RestContext;

final class UserOperationsContext implements Context
{
    private ?RequestInput $requestBody;
    private int $violationNum;
    private string $language;
    private RestContext $restContext;

    public function __construct(
        private readonly KernelInterface $kernel,
        private SerializerInterface $serializer,
        private ?Response $response
    ) {
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
        $this->requestBody->addUser(
            [
                'email' => $email,
                'initials' => $initials,
                'password' => $password,
            ]
        );
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
        $this->setupHeaders($method);
        $body = $this->createRequestBody();
        $this->restContext->iSendARequestToWithBody($method, $path, $body);
    }

    /**
     * @Then user should be timed out
     */
    public function userShouldBeTimedOut(): void
    {
        $content = $this->restContext->getSession()->getPage()->getContent();
        $data = json_decode($content, true);
        Assert::assertStringContainsString(
            'Cannot send new email till',
            $data['detail']
        );
    }

    /**
     * @Then the error message should be :errorMessage
     */
    public function theErrorMessageShouldBe(string $errorMessage): void
    {
        $content = $this->restContext->getSession()->getPage()->getContent();
        $data = json_decode($content, true);
        Assert::assertEquals($errorMessage, $data['detail']);
    }

    /**
     * @Then the response status code should be :statusCode
     */
    public function theResponseStatusCodeShouldBe(string $statusCode): void
    {
        $actualCode = $this->restContext->getSession()->getStatusCode();
        Assert::assertEquals($statusCode, $actualCode);
    }

    /**
     * @Then violation should be :violation
     */
    public function theViolationShouldBe(string $violation): void
    {
        $content = $this->restContext->getSession()->getPage()->getContent();
        $data = json_decode($content, true);
        Assert::assertEquals(
            $violation,
            $data['violations'][$this->violationNum]['message']
        );
        $this->violationNum++;
    }

    /**
     * @Then the response should contain a list of users
     */
    public function theResponseShouldContainAListOfUsers(): void
    {
        $content = $this->restContext->getSession()->getPage()->getContent();
        $data = json_decode($content, true);
        Assert::assertIsArray($data);
    }

    /**
     * @Then user with email :email and initials :initials should be returned
     */
    public function userWithEmailAndInitialsShouldBeReturned(
        string $email,
        string $initials
    ): void {
        $content = $this->restContext->getSession()->getPage()->getContent();
        $data = json_decode($content, true);
        Assert::assertArrayHasKey('id', $data);
        Assert::assertArrayHasKey('email', $data);
        Assert::assertEquals($email, $data['email']);
        Assert::assertArrayHasKey('initials', $data);
        Assert::assertEquals($initials, $data['initials']);
        Assert::assertArrayHasKey('confirmed', $data);
        Assert::assertArrayNotHasKey('password', $data);
    }

    /**
     * @Then user with id :id should be returned
     */
    public function userWithIdShouldBeReturned(string $id): void
    {
        $content = $this->restContext->getSession()->getPage()->getContent();
        $data = json_decode($content, true);
        Assert::assertArrayHasKey('id', $data);
        Assert::assertEquals($id, $data['id']);
        Assert::assertArrayHasKey('email', $data);
        Assert::assertArrayHasKey('initials', $data);
        Assert::assertArrayHasKey('confirmed', $data);
        Assert::assertArrayNotHasKey('password', $data);
    }

    private function getContentType(string $method): string
    {
        return $method === 'PATCH'
            ? 'application/merge-patch+json'
            : 'application/json';
    }

    private function setupHeaders(string $method): void
    {
        $contentType = $this->getContentType($method);
        $this->restContext->iAddHeaderEqualTo(
            'HTTP_ACCEPT',
            'application/json'
        );
        $this->restContext->iAddHeaderEqualTo(
            'CONTENT_TYPE',
            $contentType
        );
        $this->restContext->iAddHeaderEqualTo(
            'HTTP_ACCEPT_LANGUAGE',
            $this->language
        );
    }

    private function createRequestBody(): PyStringNode
    {
        return new PyStringNode(
            [$this->serializer->serialize($this->requestBody, 'json')],
            0
        );
    }
}
