<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;

/**
 */
final class UserResponseContext implements Context
{
    public function __construct(
        private UserOperationsState $state,
    ) {
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
     * @Then the response status code should not be :statusCode
     */
    public function theResponseStatusCodeShouldNotBe(string $statusCode): void
    {
        Assert::assertNotEquals(
            $statusCode,
            (string) $this->state->response->getStatusCode()
        );
    }

    /**
     * @Then the response should be RFC :rfc problem+json
     */
    public function theResponseShouldBeRfcProblemJson(string $rfc): void
    {
        Assert::assertSame('7807', $rfc);
        $response = $this->state->response;
        Assert::assertNotNull($response);
        $contentType = $response->headers->get('Content-Type');
        Assert::assertIsString($contentType);
        Assert::assertStringContainsString(
            'application/problem+json',
            $contentType
        );
        $content = $response->getContent();
        Assert::assertIsString($content);
        Assert::assertNotSame('', $content);
        $decoded = json_decode($content, true);
        Assert::assertIsArray($decoded);
        Assert::assertArrayHasKey('title', $decoded);
        Assert::assertArrayHasKey('detail', $decoded);
        Assert::assertArrayHasKey('status', $decoded);
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

    /**
     * @Then the user should have :field set to :value
     */
    public function theUserShouldHaveFieldSetTo(string $field, string $value): void
    {
        $responseData = json_decode((string) $this->state->response->getContent(), true);

        Assert::assertIsArray($responseData);
        $resolvedField = $this->resolveUserField($field, $responseData);

        $expectedValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if (!is_bool($expectedValue)) {
            throw new \RuntimeException(
                sprintf('Unsupported boolean value "%s" in assertion.', $value)
            );
        }

        Assert::assertSame($expectedValue, $responseData[$resolvedField]);
    }

    /**
     * @Then the response should not contain :text
     */
    public function theResponseShouldNotContain(string $text): void
    {
        $responseContent = $this->state->response->getContent();
        $normalizedText = trim($text, "\"'");
        if ($normalizedText === '__schema') {
            $this->assertNoSchemaInResponse($responseContent);
            return;
        }
        Assert::assertStringNotContainsString(
            $normalizedText,
            $responseContent,
            "The response unexpectedly contains text: '{$normalizedText}'."
        );
    }

    /**
     * @Then the response should not set auth cookie
     */
    public function theResponseShouldNotSetAuthCookie(): void
    {
        $cookies = $this->state->response->headers->getCookies();
        $authCookieNames = array_map(
            static fn ($cookie): string => $cookie->getName(),
            $cookies
        );

        Assert::assertNotContains('__Host-auth_token', $authCookieNames);
    }

    /**
     * @Then the response should set auth cookie
     */
    public function theResponseShouldSetAuthCookie(): void
    {
        $cookies = $this->state->response->headers->getCookies();
        $authCookieNames = array_map(
            static fn ($cookie): string => $cookie->getName(),
            $cookies
        );

        Assert::assertContains('__Host-auth_token', $authCookieNames);
    }

    /**
     * @Then I store the pending_session_id from the response
     */
    public function iStoreThePendingSessionIdFromTheResponse(): void
    {
        $responseData = json_decode((string) $this->state->response->getContent(), true);
        $pendingSessionId = is_array($responseData)
            ? ($responseData['pending_session_id'] ?? '')
            : '';

        Assert::assertIsString($pendingSessionId);
        Assert::assertNotSame('', $pendingSessionId);

        $this->state->pendingSessionId = $pendingSessionId;
    }

    /**
     * @Then I store the response time as :key
     */
    public function iStoreTheResponseTimeAs(string $key): void
    {
        Assert::assertIsFloat(
            $this->state->lastResponseTimeMs,
            'No response time captured for the latest request.'
        );

        $this->state->{$key} = $this->state->lastResponseTimeMs;
    }

    /**
     * @Then the response time should be within acceptable range of :key
     */
    public function theResponseTimeShouldBeWithinAcceptableRangeOf(string $key): void
    {
        $referenceTime = $this->state->{$key};
        $currentTime = $this->state->lastResponseTimeMs;
        Assert::assertIsFloat($referenceTime, "Stored response time '{$key}' is missing.");
        Assert::assertIsFloat($currentTime, 'No response time captured for the latest request.');
        $difference = abs($currentTime - $referenceTime);
        $maxAllowed = max(250.0, $referenceTime * 0.7);
        Assert::assertLessThanOrEqual(
            $maxAllowed,
            $difference,
            $this->buildDeviationMessage(
                $currentTime,
                $referenceTime,
                $difference,
                $maxAllowed
            )
        );
    }

    private function assertNoSchemaInResponse(
        string $responseContent
    ): void {
        $decodedResponse = json_decode($responseContent, true);
        if (
            is_array($decodedResponse)
            && array_key_exists('data', $decodedResponse)
            && is_array($decodedResponse['data'])
        ) {
            Assert::assertArrayNotHasKey(
                '__schema',
                $decodedResponse['data'],
                'GraphQL response unexpectedly returned schema data.'
            );
        }
    }

    private function buildDeviationMessage(
        float $current,
        float $reference,
        float $difference,
        float $maxAllowed
    ): string {
        $format = 'Response time deviation is too high. ';
        $format .= 'Current: %.2fms, Reference: %.2fms, ';
        $format .= 'Difference: %.2fms, Allowed: %.2fms';
        return sprintf($format, $current, $reference, $difference, $maxAllowed);
    }

    /**
     * @param array<string, array<string>|int|string> $responseData
     */
    private function resolveUserField(string $field, array $responseData): string
    {
        $candidates = $this->buildFieldCandidates($field);
        foreach ($candidates as $candidate) {
            if (array_key_exists($candidate, $responseData)) {
                return $candidate;
            }
        }
        throw new \RuntimeException(sprintf(
            'Field "%s" was not found in response keys: %s',
            $field,
            implode(', ', array_keys($responseData))
        ));
    }

    /**
     * @return array<string>
     */
    private function buildFieldCandidates(string $field): array
    {
        $candidates = [$field];
        if (str_contains($field, '_')) {
            $candidates[] = lcfirst(str_replace(
                ' ',
                '',
                ucwords(str_replace('_', ' ', $field))
            ));
        } else {
            $candidates[] = strtolower(
                (string) preg_replace('/[A-Z]/', '_$0', $field)
            );
        }
        return $candidates;
    }
}
