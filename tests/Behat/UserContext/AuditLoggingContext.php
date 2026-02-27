<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\Tests\Behat\Support\RecordingLogger;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use DateTimeImmutable;
use PHPUnit\Framework\Assert;

final class AuditLoggingContext implements Context
{
    private const EVENT_NAME_TO_KEY = [
        'UserSignedIn' => 'user.signed_in',
        'SignInFailed' => 'user.signin.failed',
        'TwoFactorCompleted' => 'user.two_factor.completed',
        'TwoFactorFailed' => 'user.two_factor.failed',
        'TwoFactorEnabled' => 'user.two_factor.enabled',
        'TwoFactorDisabled' => 'user.two_factor.disabled',
        'SessionRevoked' => 'user.session.revoked',
        'AllSessionsRevoked' => 'user.sessions.all_revoked',
        'RefreshTokenRotated' => 'user.refresh_token.rotated',
        'RefreshTokenTheftDetected' => 'user.refresh_token.theft_detected',
        'RecoveryCodeUsed' => 'user.recovery_code.used',
        'AccountLockedOut' => 'user.account.locked_out',
        'RateLimitExceeded' => 'api.rate_limit.exceeded',
    ];

    public function __construct(
        private readonly UserOperationsState $state,
        private readonly RecordingLogger $recordingLogger,
    ) {
    }

    /**
     * @BeforeScenario
     */
    public function resetAuditLoggingState(BeforeScenarioScope $scope): void
    {
        $this->recordingLogger->clear();
        $this->state->lastAuditLogRecord = null;
        $this->state->validRecoveryCode = '';
        $this->state->twoFactorSetupSecret = '';
        $this->state->userAgentHeader = '';
        $this->state->sessionsByUser = [];
    }

    /**
     * @Then /^(?:an|a) ([A-Z]+)-level audit log should be emitted for "([^"]+)"$/
     */
    public function auditLogShouldBeEmittedForEvent(
        string $level,
        string $eventName
    ): void {
        $record = $this->findLastRecord(
            strtolower($level),
            $this->resolveEventKey($eventName)
        );

        Assert::assertNotNull(
            $record,
            sprintf(
                'No "%s" audit log found for event "%s".',
                strtoupper($level),
                $eventName
            )
        );

        $this->state->lastAuditLogRecord = $record;
    }

    /**
     * @Then /^no ([A-Z]+)-level audit log should be emitted for "([^"]+)"$/
     */
    public function noAuditLogShouldBeEmittedForEvent(
        string $level,
        string $eventName
    ): void {
        $record = $this->findLastRecord(
            strtolower($level),
            $this->resolveEventKey($eventName)
        );

        Assert::assertNull(
            $record,
            sprintf(
                'Unexpected "%s" audit log found for event "%s".',
                strtoupper($level),
                $eventName
            )
        );
    }

    /**
     * @Then the audit log should contain :field
     */
    public function theAuditLogShouldContain(string $field): void
    {
        $record = $this->requireLastAuditLogRecord();
        Assert::assertArrayHasKey(
            $field,
            $record['context']
        );
    }

    /**
     * @Then the audit log should contain :field with value :value
     */
    public function theAuditLogShouldContainWithValue(
        string $field,
        string $value
    ): void {
        $record = $this->requireLastAuditLogRecord();
        Assert::assertArrayHasKey(
            $field,
            $record['context']
        );
        Assert::assertSame(
            $value,
            $this->stringifyLogValue($record['context'][$field])
        );
    }

    private function resolveEventKey(string $eventName): string
    {
        return self::EVENT_NAME_TO_KEY[$eventName] ?? strtolower($eventName);
    }

    /**
     * @return array{
     *     level: string,
     *     message: string,
     *     context: array<string, array|bool|float|int|object|string|null>
     * }|null
     */
    private function findLastRecord(
        string $level,
        string $eventKey
    ): ?array {
        $records = array_reverse($this->recordingLogger->records());

        foreach ($records as $record) {
            $recordLevel = strtolower((string) $record['level']);
            $recordEvent = $record['context']['event'] ?? null;

            if ($recordLevel !== $level || !is_string($recordEvent)) {
                continue;
            }

            if ($recordEvent === $eventKey) {
                return $record;
            }
        }

        return null;
    }

    /**
     * @return array{
     *     level: string,
     *     message: string,
     *     context: array<string, array|bool|float|int|object|string|null>
     * }
     */
    private function requireLastAuditLogRecord(): array
    {
        $record = $this->state->lastAuditLogRecord;
        Assert::assertIsArray($record);
        Assert::assertArrayHasKey('context', $record);
        Assert::assertIsArray($record['context']);

        return $record;
    }

    private function stringifyLogValue(
        array|bool|float|int|object|string|null $value
    ): string {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value instanceof DateTimeImmutable) {
            return $value->format(DATE_ATOM);
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if ($value === null) {
            return '';
        }

        return json_encode($value, JSON_THROW_ON_ERROR);
    }
}
