<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\Tests\Behat\UserContext\Input\SignInInput;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;

final class AuthSessionAssertionContext implements Context
{
    public function __construct(
        private UserOperationsState $state,
        private readonly UserContextUserManagementServices $userManagement,
        private readonly AuthSessionRepositoryInterface $sessionRepository,
    ) {
    }

    /**
     * @Then no active AuthSession should exist for user :email
     */
    public function noActiveAuthSessionShouldExistForUser(
        string $email
    ): void {
        $user = $this->userManagement->userRepository->findByEmail($email);
        Assert::assertNotNull($user);

        $activeSessions = array_filter(
            iterator_to_array($this->sessionRepository->findByUserId($user->getId())),
            static fn (AuthSession $session): bool => !$session->isRevoked()
                && !$session->isExpired()
        );

        Assert::assertSame([], array_values($activeSessions));
    }

    /**
     * @Then the AuthSession should have IP address :ip
     */
    public function theAuthSessionShouldHaveIpAddress(
        string $ip
    ): void {
        Assert::assertSame(
            $ip,
            $this->resolveLatestActiveSessionForScenarioUser()
                ->getIpAddress()
        );
    }

    /**
     * @Then the AuthSession should have User-Agent :userAgent
     */
    public function theAuthSessionShouldHaveUserAgent(
        string $userAgent
    ): void {
        Assert::assertSame(
            $userAgent,
            $this->resolveLatestActiveSessionForScenarioUser()
                ->getUserAgent()
        );
    }

    private function resolveLatestActiveSessionForScenarioUser(): AuthSession
    {
        $user = $this->userManagement->userRepository->findByEmail(
            $this->resolveScenarioEmail()
        );
        Assert::assertNotNull($user);

        $activeSessions = $this->resolveSortedActiveSessions($user->getId());
        Assert::assertNotSame([], $activeSessions);

        return $activeSessions[0];
    }

    private function resolveScenarioEmail(): string
    {
        $currentUserEmail = $this->state->currentUserEmail;
        if (is_string($currentUserEmail) && $currentUserEmail !== '') {
            return $currentUserEmail;
        }

        $requestBody = $this->state->requestBody;
        Assert::assertInstanceOf(SignInInput::class, $requestBody);

        return $requestBody->email;
    }

    /**
     * @return array<int, AuthSession>
     */
    private function resolveSortedActiveSessions(string $userId): array
    {
        $activeSessions = array_values(array_filter(
            iterator_to_array($this->sessionRepository->findByUserId($userId)),
            static fn (AuthSession $session): bool => !$session->isRevoked()
                && !$session->isExpired()
        ));

        usort(
            $activeSessions,
            static fn (AuthSession $left, AuthSession $right): int => $right
                ->getCreatedAt()
                <=> $left->getCreatedAt()
        );

        return $activeSessions;
    }
}
