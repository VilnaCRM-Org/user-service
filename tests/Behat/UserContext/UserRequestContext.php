<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\Tests\Behat\UserContext\Input\ConfirmUserInput;
use App\Tests\Behat\UserContext\Input\CreateUserBatchInput;
use App\Tests\Behat\UserContext\Input\CreateUserInput;
use App\Tests\Behat\UserContext\Input\EmptyInput;
use App\Tests\Behat\UserContext\Input\UpdateUserInput;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\PyStringNode;
use RuntimeException;
use Symfony\Component\Serializer\SerializerInterface;
use TwentytwoLabs\BehatOpenApiExtension\Context\RestContext;

final class UserRequestContext implements Context
{
    private const CONTENT_TYPES = [
        'PATCH' => 'application/merge-patch+json',
    ];

    private UrlResolver $urlResolver;
    private RequestBodySerializer $bodySerializer;
    private RestContext $restContext;

    public function __construct(
        private UserOperationsState $state,
        SerializerInterface $serializer
    ) {
        $this->urlResolver = new UrlResolver();
        $this->bodySerializer = new RequestBodySerializer($serializer);
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
        if (!$this->state->requestBody instanceof CreateUserBatchInput) {
            throw new RuntimeException(
                sprintf(
                    'requestBody must be initialized with "%s" before calling addUser().',
                    'Given sending a batch of users'
                )
            );
        }

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
        $method = strtoupper($method);
        $processedPath = $this->processRequestPath($path);

        if ($this->isRequestBodyMethod($method)) {
            $requestBody = $this->bodySerializer->serialize(
                $this->state->requestBody,
                $method
            );
            $this->sendRequestWithBody($method, $processedPath, $requestBody);

            return;
        }

        $this->sendRequestWithoutBody($method, $processedPath);
    }

    private function processRequestPath(string $path): string
    {
        $this->urlResolver->setCurrentUserEmail($this->state->currentUserEmail);

        return $this->urlResolver->resolve($path);
    }

    /**
     * @return array<string, string>
     */
    private function buildHeaders(string $method): array
    {
        $method = strtoupper($method);
        $headers = [
            'Accept' => 'application/json',
            'Accept-Language' => $this->state->language,
        ];

        if ($this->isRequestBodyMethod($method)) {
            $headers['Content-Type'] =
                self::CONTENT_TYPES[$method] ?? 'application/json';
        }

        return $headers;
    }

    private function isRequestBodyMethod(string $method): bool
    {
        $method = strtoupper($method);

        return in_array($method, ['POST', 'PUT', 'PATCH'], true);
    }

    private function sendRequestWithBody(
        string $method,
        string $path,
        string $body
    ): void {
        $this->addHeaders($this->buildHeaders($method));

        $pyStringBody = new PyStringNode([$body], 0);
        $this->restContext->iSendARequestToWithBody($method, $path, $pyStringBody);
    }

    private function sendRequestWithoutBody(string $method, string $path): void
    {
        $this->resetBodyHeaders();
        $this->addHeaders($this->buildHeaders($method));
        $this->restContext->iSendARequestTo($method, $path);
    }

    private function resetBodyHeaders(): void
    {
        $this->restContext
            ->getMink()
            ->getSession()
            ->setRequestHeader('Content-Type', '');
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
