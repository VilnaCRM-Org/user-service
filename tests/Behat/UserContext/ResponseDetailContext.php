<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\Tests\Behat\UserContext\Input\CompleteTwoFactorInput;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use OTPHP\TOTP;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Response;

final class ResponseDetailContext implements Context
{
    private const DEFAULT_TOTP_SECRET = 'JBSWY3DPEHPK3PXP';

    private UserRequestContext $userRequestContext;

    public function __construct(
        private UserOperationsState $state,
    ) {
    }

    /**
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope): void
    {
        $this->userRequestContext = $scope
            ->getEnvironment()
            ->getContext(UserRequestContext::class);
    }

    /**
     * @Then the response body should be empty
     */
    public function theResponseBodyShouldBeEmpty(): void
    {
        Assert::assertSame('', (string) $this->state->response?->getContent());
    }

    /**
     * @Then the RFC :rfc :field field should be :status
     */
    public function theRfcFieldShouldBe(
        string $rfc,
        string $field,
        int $status
    ): void {
        Assert::assertSame('7807', $rfc);
        Assert::assertSame('status', trim($field, "\"'"));

        $decoded = json_decode(
            (string) $this->state->response?->getContent(),
            true
        );

        Assert::assertIsArray($decoded);
        Assert::assertSame($status, $decoded['status'] ?? null);
    }

    /**
     * @Then /^the response field "([^"]+)" should be (\d+)$/
     */
    public function theResponseFieldShouldBe(
        string $field,
        int $value
    ): void {
        $data = json_decode(
            (string) $this->state->response?->getContent(),
            true
        );
        Assert::assertIsArray($data);
        Assert::assertArrayHasKey($field, $data);
        Assert::assertSame($value, $data[$field]);
    }

    /**
     * @Then the response field :field should be false
     */
    public function theResponseFieldShouldBeFalse(string $field): void
    {
        $data = json_decode(
            (string) $this->state->response->getContent(),
            true
        );
        Assert::assertIsArray($data);
        $field = trim($field, "\"'");
        Assert::assertArrayHasKey($field, $data);
        Assert::assertFalse($data[$field]);
    }

    /**
     * @Then the response field :field should be true
     */
    public function theResponseFieldShouldBeTrue(string $field): void
    {
        $data = json_decode(
            (string) $this->state->response->getContent(),
            true
        );
        Assert::assertIsArray($data);
        $field = trim($field, "\"'");
        Assert::assertArrayHasKey($field, $data);
        Assert::assertTrue($data[$field]);
    }

    /**
     * @Then the otpauth_uri should contain :fragment
     */
    public function theOtpauthUriShouldContain(string $fragment): void
    {
        $responseData = json_decode(
            (string) $this->state->response?->getContent(),
            true
        );
        Assert::assertIsArray($responseData);

        $otpauthUri = $responseData['otpauth_uri'] ?? null;
        Assert::assertIsString($otpauthUri);
        Assert::assertStringContainsString(
            trim($fragment, "\"'"),
            $otpauthUri
        );
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

    /**
     * @Then the response time should not reveal code validity
     */
    public function theResponseTimeShouldNotRevealCodeValidity(): void
    {
        $snapshot = $this->captureInvalidCodeResponseSnapshot();
        $controlResponseTime = $this->measureControlTwoFactorResponse();
        $this->assertTimingDeviationWithinRange(
            $controlResponseTime,
            $snapshot['responseTimeMs']
        );
        $this->restoreInvalidCodeResponseSnapshot($snapshot);
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
     * @return array{
     *     requestBody: array|bool|float|int|object|string|null,
     *     response: array|bool|float|int|object|string|null,
     *     responseTimeMs: float
     * }
     */
    private function captureInvalidCodeResponseSnapshot(): array
    {
        $invalidResponseTime = $this->state->lastResponseTimeMs;
        Assert::assertIsFloat(
            $invalidResponseTime,
            'No invalid-code response time is available.'
        );

        return [
            'requestBody' => $this->state->requestBody,
            'response' => $this->state->response,
            'responseTimeMs' => $invalidResponseTime,
        ];
    }

    private function measureControlTwoFactorResponse(): float
    {
        $requestBody = $this->state->requestBody;
        $this->state->requestBody = new CompleteTwoFactorInput(
            $this->resolvePendingSessionId(),
            $this->generateValidTotpCode()
        );
        $this->userRequestContext->requestSendTo('POST', '/api/signin/2fa');

        $controlResponse = $this->state->response;
        $controlResponseTime = $this->state->lastResponseTimeMs;
        $this->state->requestBody = $requestBody;

        Assert::assertInstanceOf(Response::class, $controlResponse);
        Assert::assertIsFloat($controlResponseTime);
        Assert::assertSame(200, $controlResponse->getStatusCode());

        return $controlResponseTime;
    }

    private function assertTimingDeviationWithinRange(
        float $currentTime,
        float $referenceTime
    ): void {
        $difference = abs($currentTime - $referenceTime);
        $maxAllowed = max(250.0, $referenceTime * 0.7, $currentTime * 0.7);
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

    /**
     * @param array{
     *     requestBody: array|bool|float|int|object|string|null,
     *     response: array|bool|float|int|object|string|null,
     *     responseTimeMs: float
     * } $snapshot
     */
    private function restoreInvalidCodeResponseSnapshot(array $snapshot): void
    {
        $this->state->requestBody = $snapshot['requestBody'];
        $this->state->response = $snapshot['response'];
        $this->state->lastResponseTimeMs = $snapshot['responseTimeMs'];
    }

    private function resolvePendingSessionId(): string
    {
        $pendingSessionId = $this->state->pendingSessionId;
        Assert::assertIsString($pendingSessionId);
        Assert::assertNotSame('', $pendingSessionId);

        return $pendingSessionId;
    }

    private function generateValidTotpCode(): string
    {
        return TOTP::create(self::DEFAULT_TOTP_SECRET)->now();
    }
}
