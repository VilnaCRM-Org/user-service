<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;

final class UserResponseContext implements Context
{
    public function __construct(private UserOperationsState $state)
    {
    }

    /**
     * @Then user should be timed out
     */
    public function userShouldBeTimedOut(): void
    {
        $data = json_decode($this->state->response->getContent(), true);
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
        $data = json_decode($this->state->response->getContent(), true);
        Assert::assertEquals($errorMessage, $data['detail']);
    }

    /**
     * @Then the response status code should be :statusCode
     */
    public function theResponseStatusCodeShouldBe(string $statusCode): void
    {
        Assert::assertEquals($statusCode, $this->state->response->getStatusCode());
    }

    /**
     * @Then the response body should contain :text
     */
    public function theResponseBodyShouldContain(string $text): void
    {
        Assert::assertNotNull($this->state->response);
        Assert::assertStringContainsString($text, (string) $this->state->response->getContent());
    }

    /**
     * @Then violation should be :violation
     */
    public function theViolationShouldBe(string $violation): void
    {
        $data = json_decode($this->state->response->getContent(), true);
        Assert::assertEquals(
            $violation,
            $data['violations'][$this->state->violationNum]['message']
        );
        $this->state->violationNum++;
    }

    /**
     * @Then the response should contain a list of users
     */
    public function theResponseShouldContainAListOfUsers(): void
    {
        $data = json_decode($this->state->response->getContent(), true);
        Assert::assertIsArray($data);
    }

    /**
     * @Then user with email :email and initials :initials should be returned
     */
    public function userWithEmailAndInitialsShouldBeReturned(
        string $email,
        string $initials
    ): void {
        $data = json_decode($this->state->response->getContent(), true);
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
        $data = json_decode($this->state->response->getContent(), true);
        Assert::assertArrayHasKey('id', $data);
        Assert::assertEquals($id, $data['id']);
        Assert::assertArrayHasKey('email', $data);
        Assert::assertArrayHasKey('initials', $data);
        Assert::assertArrayHasKey('confirmed', $data);
        Assert::assertArrayNotHasKey('password', $data);
    }

    /**
     * @Then the response should contain :text
     */
    public function theResponseShouldContain(string $text): void
    {
        $responseContent = $this->state->response->getContent();
        Assert::assertStringContainsString(
            $text,
            $responseContent,
            "The response does not contain the expected text: '{$text}'."
        );
    }
}
