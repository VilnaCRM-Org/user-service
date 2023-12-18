<?php

namespace App\Tests\Behat;

use App\User\Domain\Entity\User\User;
use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Serializer\SerializerInterface;

class UserOperationsContext implements Context
{
    private array $requestBody;

    public function __construct(
        private readonly KernelInterface $kernel, private SerializerInterface $serializer,
        private ?Response $response
    ) {
        $this->requestBody = [];
    }

    /**
     * @Given updating user with email :email, initials :initials, oldPassword :oldPassword, newPassword :newPassword
     */
    public function replacingUser($email, $initials, $oldPassword, $newPassword): void
    {
        $this->requestBody['email'] = $email;
        $this->requestBody['initials'] = $initials;
        $this->requestBody['oldPassword'] = $oldPassword;
        $this->requestBody['newPassword'] = $newPassword;
    }

    /**
     * @Given creating user with email :email, initials :initials, password :password
     */
    public function creatingUser($email, $initials, $password): void
    {
        $this->requestBody['email'] = $email;
        $this->requestBody['initials'] = $initials;
        $this->requestBody['password'] = $password;
    }

    /**
     * @Given confirming user with token :token
     */
    public function confirmingUserWithToken($token): void
    {
        $this->requestBody['token'] = $token;
    }

    /**
     * @Given creating user with misformatted data
     */
    public function creatingUserWithMisformattedData(): void
    {
        $this->requestBody['email'] = 1;
        $this->requestBody['initials'] = 2;
        $this->requestBody['password'] = 3;
    }

    /**
     * @Given updating user with misformatted data
     */
    public function replacingUserWithMisformattedData(): void
    {
        $this->requestBody['email'] = 1;
        $this->requestBody['initials'] = 2;
        $this->requestBody['oldPassword'] = 3;
        $this->requestBody['newPassword'] = 3;
    }

    /**
     * @When :method request is send to :path
     */
    public function requestSendTo(string $method, string $path): void
    {
        $contentType = 'application/json';
        if ('PATCH' === $method) {
            $contentType = 'application/merge-patch+json';
        }
        $this->response = $this->kernel->handle(Request::create(
            $path,
            $method,
            [],
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => $contentType, ], $this->serializer->serialize($this->requestBody, 'json')
        ));
    }

    /**
     * @Then the response status code should be :statusCode
     */
    public function theResponseStatusCodeShouldBe($statusCode): void
    {
        if (null === $this->response) {
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
     * @Then user should be returned
     */
    public function theResponseShouldContainAReturnedUser(): void
    {
        $data = json_decode($this->response->getContent(), true);
        Assert::assertArrayHasKey('id', $data);
        Assert::assertArrayHasKey('email', $data);
        Assert::assertArrayHasKey('initials', $data);
        Assert::assertArrayHasKey('confirmed', $data);
        Assert::assertArrayHasKey('roles', $data);
        Assert::assertArrayNotHasKey('password', $data);
    }
}
