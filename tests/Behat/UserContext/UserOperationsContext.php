<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\Tests\Behat\UserContext\Input\ConfirmPasswordResetInput;
use App\Tests\Behat\UserContext\Input\ConfirmUserInput;
use App\Tests\Behat\UserContext\Input\CreateUserBatchInput;
use App\Tests\Behat\UserContext\Input\CreateUserInput;
use App\Tests\Behat\UserContext\Input\EmptyInput;
use App\Tests\Behat\UserContext\Input\RequestInput;
use App\Tests\Behat\UserContext\Input\RequestPasswordResetInput;
use App\Tests\Behat\UserContext\Input\UpdateUserInput;
use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class UserOperationsContext implements Context
{
    private ?RequestInput $requestBody;
    private int $violationNum;
    private string $language;
    private string $currentUserEmail = '';

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
        // For password reset endpoints, replace the URL with the actual user ID
        if (str_contains($path, '/users/{id}/reset-password')) {
            if (empty($this->currentUserEmail)) {
                // For invalid token tests or cases where user doesn't exist, use a placeholder user ID
                $path = str_replace('{id}', 'placeholder-id', $path);
            } else {
                try {
                    $userId = UserContext::getUserIdByEmail($this->currentUserEmail);
                    $path = str_replace('{id}', $userId, $path);
                } catch (\RuntimeException $e) {
                    // User doesn't exist, use placeholder ID for non-existing user tests
                    $path = str_replace('{id}', 'nonexistent-user-id', $path);
                }
            }
        }

        $contentType = 'application/json';
        if ($method === 'PATCH') {
            $contentType = 'application/merge-patch+json';
        }
        $this->response = $this->kernel->handle(Request::create(
            $path,
            $method,
            [],
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => $contentType,
                'HTTP_ACCEPT_LANGUAGE' => $this->language,
            ],
            $this->serializer->serialize($this->requestBody, 'json')
        ));
    }

    /**
     * @Then user should be timed out
     */
    public function userShouldBeTimedOut(): void
    {
        $data = json_decode($this->response->getContent(), true);
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
        $data = json_decode($this->response->getContent(), true);
        Assert::assertEquals($errorMessage, $data['detail']);
    }

    /**
     * @Then the response status code should be :statusCode
     */
    public function theResponseStatusCodeShouldBe(string $statusCode): void
    {
        Assert::assertEquals($statusCode, $this->response->getStatusCode());
    }

    /**
     * @Then violation should be :violation
     */
    public function theViolationShouldBe(string $violation): void
    {
        $data = json_decode($this->response->getContent(), true);
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
        $data = json_decode($this->response->getContent(), true);
        Assert::assertIsArray($data);
    }

    /**
     * @Then user with email :email and initials :initials should be returned
     */
    public function userWithEmailAndInitialsShouldBeReturned(
        string $email,
        string $initials
    ): void {
        $data = json_decode($this->response->getContent(), true);
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
        $data = json_decode($this->response->getContent(), true);
        Assert::assertArrayHasKey('id', $data);
        Assert::assertEquals($id, $data['id']);
        Assert::assertArrayHasKey('email', $data);
        Assert::assertArrayHasKey('initials', $data);
        Assert::assertArrayHasKey('confirmed', $data);
        Assert::assertArrayNotHasKey('password', $data);
    }

    /**
     * @Given requesting password reset for email :email
     */
    public function requestingPasswordResetForEmail(string $email): void
    {
        $this->currentUserEmail = $email;
        $this->requestBody = new RequestPasswordResetInput($email);
    }

    /**
     * @Given confirming password reset with valid token and password :password
     */
    public function confirmingPasswordResetWithValidTokenAndPassword(string $password): void
    {
        // Use the actual token that was created in the previous step
        $token = UserContext::getLastPasswordResetToken();
        $this->currentUserEmail = UserContext::getCurrentTokenUserEmail();
        $this->requestBody = new ConfirmPasswordResetInput($token, $password);
    }

    /**
     * @Given confirming password reset with token :token and password :password
     */
    public function confirmingPasswordResetWithTokenAndPassword(string $token, string $password): void
    {
        // For invalid token tests, we need a fallback email or handle it differently
        // Since this is an invalid token test, we don't have a valid user email
        // We'll handle this case by using a placeholder that the URL replacement will skip
        $this->currentUserEmail = '';
        $this->requestBody = new ConfirmPasswordResetInput($token, $password);
    }

    /**
     * @Then the response should contain :text
     */
    public function theResponseShouldContain(string $text): void
    {
        $responseContent = $this->response->getContent();
        Assert::assertStringContainsString(
            $text,
            $responseContent,
            "The response does not contain the expected text: '{$text}'."
        );
    }
}
