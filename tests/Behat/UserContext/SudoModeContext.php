<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\User;
use Behat\Behat\Context\Context;
use DateTimeImmutable;
use RuntimeException;

final readonly class SudoModeContext implements Context
{
    public function __construct(
        private UserOperationsState $state,
        private UserContextUserManagementServices $userManagement,
        private UserContextAuthServices $auth,
    ) {
    }

    /**
     * @Given user :email has completed high-trust re-auth within 5 minutes
     */
    public function userHasCompletedHighTrustReAuthWithinFiveMinutes(string $email): void
    {
        $this->rewriteCurrentSession($email, new DateTimeImmutable('-1 minute'));
    }

    /**
     * @Given user :email has not completed high-trust re-auth recently
     */
    public function userHasNotCompletedHighTrustReAuthRecently(string $email): void
    {
        $this->rewriteCurrentSession($email, new DateTimeImmutable('-10 minutes'));
    }

    private function rewriteCurrentSession(string $email, DateTimeImmutable $createdAt): void
    {
        $user = $this->resolveUser($email);
        $sessionId = $this->resolveSessionIdFromCurrentAccessToken();
        $existingSession = $this->resolveExistingSession($sessionId, $user);

        $updatedSession = $this->buildUpdatedSession(
            $existingSession,
            $sessionId,
            $user->getId(),
            $createdAt
        );

        $this->replaceSession($existingSession, $updatedSession);
    }

    private function resolveUser(string $email): User
    {
        $user = $this->userManagement->userRepository->findByEmail($email);
        if (!$user instanceof User) {
            throw new RuntimeException(sprintf('User with email %s was not found.', $email));
        }

        return $user;
    }

    private function resolveExistingSession(string $sessionId, User $user): ?AuthSession
    {
        $existingSession = $this->auth->authSessionRepository->findById($sessionId);
        if (
            $existingSession instanceof AuthSession
            && $existingSession->getUserId() !== $user->getId()
        ) {
            throw new RuntimeException('Current session does not belong to the expected user.');
        }

        return $existingSession;
    }

    private function replaceSession(
        ?AuthSession $existingSession,
        AuthSession $updatedSession
    ): void {
        if ($existingSession instanceof AuthSession) {
            $this->auth->authSessionRepository->delete($existingSession);
        }

        $this->auth->authSessionRepository->save($updatedSession);
    }

    private function resolveSessionIdFromCurrentAccessToken(): string
    {
        $accessToken = $this->state->accessToken;
        if (!is_string($accessToken) || $accessToken === '') {
            throw new RuntimeException('Current access token is missing from scenario state.');
        }

        $payload = $this->decodeJwtPayload($accessToken);
        $sessionId = $payload['sid'] ?? null;
        if (!is_string($sessionId) || $sessionId === '') {
            throw new RuntimeException('Session id is missing in current access token.');
        }

        return $sessionId;
    }

    /**
     * @return array<string, array<string>|int|string>
     */
    private function decodeJwtPayload(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid access token format.');
        }

        $encodedPayload = $parts[1];
        $remainder = strlen($encodedPayload) % 4;
        if ($remainder !== 0) {
            $encodedPayload .= str_repeat('=', 4 - $remainder);
        }

        $rawPayload = base64_decode(strtr($encodedPayload, '-_', '+/'), true);
        if (!is_string($rawPayload) || $rawPayload === '') {
            throw new RuntimeException('Unable to decode JWT payload.');
        }

        $decodedPayload = json_decode($rawPayload, true);
        if (!is_array($decodedPayload)) {
            throw new RuntimeException('JWT payload is not a valid JSON object.');
        }

        return $decodedPayload;
    }

    private function buildUpdatedSession(
        ?AuthSession $existingSession,
        string $sessionId,
        string $userId,
        DateTimeImmutable $createdAt
    ): AuthSession {
        $rememberMe = $existingSession?->isRememberMe() ?? false;
        $ttlSeconds = $rememberMe ? 2592000 : 900;

        return new AuthSession(
            $sessionId,
            $userId,
            $existingSession?->getIpAddress() ?? '127.0.0.1',
            $existingSession?->getUserAgent() ?? 'BehatSudoModeContext',
            $createdAt,
            $createdAt->modify(sprintf('+%d seconds', $ttlSeconds)),
            $rememberMe
        );
    }
}
