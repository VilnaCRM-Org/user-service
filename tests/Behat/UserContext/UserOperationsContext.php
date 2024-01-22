<?php

namespace App\Tests\Behat\UserContext;

use App\Tests\Behat\UserContext\Input\ConfirmUserInput;
use App\Tests\Behat\UserContext\Input\CreateUserInput;
use App\Tests\Behat\UserContext\Input\RequestInput;
use App\Tests\Behat\UserContext\Input\UpdateUserInput;
use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Serializer\SerializerInterface;

class UserOperationsContext implements Context
{
    private RequestInput $requestBody;

    public function __construct(
        private readonly KernelInterface $kernel,
        private SerializerInterface $serializer,
        private ?Response $response
    ) {
        $this->requestBody = new RequestInput();
    }

    /**
     * @Given updating user with email :email, initials :initials, oldPassword :oldPassword, newPassword :newPassword
     */
    public function replacingUser(string $email, string $initials, string $oldPassword, string $newPassword): void
    {
        $this->requestBody = new UpdateUserInput($email, $initials, $oldPassword, $newPassword);
    }

    /**
     * @Given creating user with email :email, initials :initials, password :password
     */
    public function creatingUser(string $email, string $initials, string $password): void
    {
        $this->requestBody = new CreateUserInput($email, $initials, $password);
    }

    /**
     * @Given confirming user with token :token
     */
    public function confirmingUserWithToken(string $token): void
    {
        $this->requestBody = new ConfirmUserInput($token);
    }

    /**
     * @Given creating user with invalid input
     */
    public function creatingUserWithMisformattedData(): void
    {
        $this->requestBody = new CreateUserInput();
    }

    /**
     * @Given updating user with invalid input
     */
    public function replacingUserWithMisformattedData(): void
    {
        $this->requestBody = new UpdateUserInput();
    }

    /**
     * @When :method request is send to :path
     */
    public function requestSendTo(string $method, string $path): void
    {
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
                'CONTENT_TYPE' => $contentType, ],
            $this->serializer->serialize($this->requestBody, 'json')
        ));
    }

    /**
     * @Then the response status code should be :statusCode
     */
    public function theResponseStatusCodeShouldBe(string $statusCode): void
    {
        if ($this->response === null) {
            throw new \RuntimeException('No response received');
        }

        if ($statusCode !== (string) $this->response->getStatusCode()) {
            throw new \RuntimeException("Response status code is not $statusCode.".' Actual code is '.$this->response->getStatusCode().$this->response->getContent());
        }
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
    public function userWithEmailAndInitialsShouldBeReturned(string $email, string $initials): void
    {
        $data = json_decode($this->response->getContent(), true);
        Assert::assertArrayHasKey('id', $data);
        Assert::assertArrayHasKey('email', $data);
        Assert::assertEquals($email, $data['email']);
        Assert::assertArrayHasKey('initials', $data);
        Assert::assertEquals($initials, $data['initials']);
        Assert::assertArrayHasKey('confirmed', $data);
        Assert::assertArrayHasKey('roles', $data);
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
        Assert::assertArrayHasKey('roles', $data);
        Assert::assertArrayNotHasKey('password', $data);
    }
}
