<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\Tests\Behat\UserContext\Input\ConfirmUserInput;
use App\Tests\Behat\UserContext\Input\CreateUserBatchInput;
use App\Tests\Behat\UserContext\Input\CreateUserInput;
use App\Tests\Behat\UserContext\Input\EmptyInput;
use App\Tests\Behat\UserContext\Input\UpdateUserInput;
use Behat\Behat\Context\Context;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class UserRequestContext implements Context
{
    private UrlResolver $urlResolver;
    private RequestBodySerializer $bodySerializer;

    public function __construct(
        private UserOperationsState $state,
        private readonly KernelInterface $kernel,
        SerializerInterface $serializer
    ) {
        $this->urlResolver = new UrlResolver();
        $this->bodySerializer = new RequestBodySerializer($serializer);
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
        $this->state->requestBody = new UpdateUserInput(
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
        $this->state->requestBody = new UpdateUserInput(
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
        $this->state->requestBody = new CreateUserInput($email, $initials, $password);
    }

    /**
     * @Given sending a batch of users
     */
    public function sendingUserBatch(): void
    {
        $this->state->requestBody = new CreateUserBatchInput();
    }

    /**
     * @Given with user with email :email, initials :initials, password :password
     */
    public function addUserToBatch(
        string $email,
        string $initials,
        string $password
    ): void {
        $this->state->requestBody->addUser(
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
        $this->state->requestBody = new ConfirmUserInput($token);
    }

    /**
     * @Given sending empty body
     */
    public function sendingEmptyBody(): void
    {
        $this->state->requestBody = new EmptyInput();
    }

    /**
     * @Given with language :lang
     */
    public function setLanguage(string $lang): void
    {
        $this->state->language = $lang;
    }

    /**
     * @When :method request is send to :path
     * @When :method request is sent to :path
     */
    public function requestSendTo(string $method, string $path): void
    {
        $processedPath = $this->processRequestPath($path);
        $headers = $this->buildRequestHeaders($method);
        $requestBody = $this->bodySerializer->serialize($this->state->requestBody, $method);

        $this->state->response = $this->kernel->handle(Request::create(
            $processedPath,
            $method,
            [],
            [],
            [],
            $headers,
            $requestBody
        ));
    }

    private function processRequestPath(string $path): string
    {
        $this->urlResolver->setCurrentUserEmail($this->state->currentUserEmail);
        return $this->urlResolver->resolve($path);
    }

    /**
     * @return array<string, string>
     */
    private function buildRequestHeaders(string $method): array
    {
        return [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => $this->getContentTypeForMethod($method),
            'HTTP_ACCEPT_LANGUAGE' => $this->state->language,
        ];
    }

    private function getContentTypeForMethod(string $method): string
    {
        return $method === 'PATCH' ? 'application/merge-patch+json' : 'application/json';
    }
}
