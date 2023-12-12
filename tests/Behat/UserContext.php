<?php

namespace App\Tests\Behat;

use App\User\Domain\Entity\Token\ConfirmationToken;
use App\User\Domain\Entity\User\User;
use App\User\Domain\TokenRepository;
use App\User\Domain\UserRepository;
use App\User\Infrastructure\Exceptions\TokenNotFoundError;
use App\User\Infrastructure\Exceptions\UserNotFoundError;
use Behat\Behat\Context\Context;
use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Serializer\SerializerInterface;

class UserContext implements Context
{
    private array $requestBody;
    private Generator $faker;

    public function __construct(
        private readonly KernelInterface $kernel, private SerializerInterface $serializer,
        private ?Response $response, private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher, private TokenRepository $tokenRepository
    ) {
        $this->requestBody = [];
        $this->faker = Factory::create();
    }

    /**
     * @Given user with id :id has a confirmation token assigned to him
     */
    public function userHasConfirmationToken(string $id): void
    {
        try {
            $token = $this->tokenRepository->findByUserId($id);
        } catch (TokenNotFoundError) {
            $token = ConfirmationToken::generateToken($id);
            $this->tokenRepository->save($token);
        }
        $this->requestBody['token'] = $token->getTokenValue();
    }

    /**
     * @Given user with id :id exists
     */
    public function userWithIdExists(string $id): void
    {
        try {
            $this->userRepository->find($id);
        } catch (UserNotFoundError) {
            $this->userRepository->save(new User($id, $this->faker->email, $this->faker->name, $this->faker->password));
        }
    }

    /**
     * @Given user with id :id and password :password exists
     */
    public function userWithIdAndPasswordExists(string $id, string $password): void
    {
        try {
            $user = $this->userRepository->find($id);
            if (!$this->passwordHasher->isPasswordValid($user, $password)) {
                throw new UserNotFoundError();
            }
        } catch (UserNotFoundError) {
            $user = new User($id, $this->faker->email, $this->faker->name, $password);

            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $password
            );
            $user->setPassword($hashedPassword);

            $this->userRepository->save($user);
        }
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
