<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use Behat\Behat\Context\Context;
use DateTimeImmutable;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class TokenRefreshRequestContext implements Context
{
    private TokenRefreshWorkflow $workflow;

    public function __construct(
        private UserOperationsState $state,
        KernelInterface $kernel,
        SerializerInterface $serializer,
        private readonly AuthRefreshTokenRepositoryInterface $refreshRepo,
        UserContextAuthServices $auth,
        UserContextUserManagementServices $userManagement,
    ) {
        $this->workflow = new TokenRefreshWorkflow(
            $state,
            $kernel,
            $serializer,
            $this->refreshRepo,
            $auth,
            $userManagement
        );
    }

    /**
     * @Given submitting the refresh token to exchange
     */
    public function submittingTheRefreshTokenToExchange(): void
    {
        $refreshToken = $this->workflow->resolveStateToken(
            'refreshToken'
        );
        $this->workflow->submitRefreshToken($refreshToken);
    }

    /**
     * @Given submitting the stored refresh token to exchange
     */
    public function submittingTheStoredRefreshTokenToExchange(): void
    {
        $storedTokens = $this->state->storedRefreshTokens;
        if (
            !is_array($storedTokens) ||
            !isset($storedTokens['default'])
        ) {
            throw new \RuntimeException(
                'No stored refresh token found.'
            );
        }

        $refreshToken = $storedTokens['default'];
        Assert::assertIsString($refreshToken);
        Assert::assertNotSame('', $refreshToken);

        $this->workflow->submitRefreshToken($refreshToken);
    }

    /**
     * @Given submitting the new stored refresh token to exchange
     * @Given submitting the new refresh token to exchange
     */
    public function submittingTheNewStoredRefreshTokenToExchange(): void
    {
        $storedTokens = $this->state->storedRefreshTokens;
        if (
            !is_array($storedTokens) ||
            !isset($storedTokens['new'])
        ) {
            throw new \RuntimeException(
                'No stored new refresh token found.'
            );
        }

        $refreshToken = $storedTokens['new'];
        Assert::assertIsString($refreshToken);
        Assert::assertNotSame('', $refreshToken);

        $this->workflow->submitRefreshToken($refreshToken);
    }

    /**
     * @Given user :email has an expired refresh token
     */
    public function userHasAnExpiredRefreshToken(string $email): void
    {
        $this->workflow->issueRefreshTokenForUser(
            $email,
            new DateTimeImmutable('-1 minute')
        );
    }

    /**
     * @Given submitting the expired refresh token to exchange
     * @Given submitting the revoked refresh token to exchange
     * @Given submitting the original refresh token to exchange
     * @Given submitting the same original refresh token again within the grace window
     */
    public function submittingTheOriginalRefreshTokenToExchange(): void
    {
        $this->workflow->submitRefreshToken(
            $this->workflow->resolveOriginalRefreshToken()
        );
    }

    /**
     * @Given /^the original refresh token is submitted \(theft attempt\)$/
     */
    public function theOriginalRefreshTokenIsSubmittedDuringTheftAttempt(): void
    {
        $this->submittingTheOriginalRefreshTokenToExchange();
    }

    /**
     * @Given user :email has a revoked refresh token
     */
    public function userHasARevokedRefreshToken(string $email): void
    {
        $this->workflow->issueRefreshTokenForUser(
            $email,
            new DateTimeImmutable('+30 days')
        );

        $token = $this->refreshRepo->findByTokenHash(
            hash('sha256', $this->workflow->resolveStateToken('refreshToken'))
        );
        Assert::assertInstanceOf(AuthRefreshToken::class, $token);

        $token->revoke();
        $this->refreshRepo->save($token);
    }

    /**
     * @Given submitting refresh token :refreshToken
     */
    public function submittingRefreshToken(
        string $refreshToken
    ): void {
        $this->workflow->submitRefreshToken($refreshToken);
    }

    /**
     * @Given submitting the rotated refresh token to exchange
     */
    public function submittingTheRotatedRefreshTokenToExchange(): void
    {
        $rotatedToken = $this->workflow->resolveStateToken(
            'rotatedRefreshToken'
        );
        $this->workflow->submitRefreshToken($rotatedToken);
    }

    /**
     * @Given the refresh token has been rotated within the grace window
     */
    public function theRefreshTokenHasBeenRotatedWithinTheGraceWindow(): void
    {
        $originalToken = $this->workflow->resolveOriginalRefreshToken();
        $this->workflow->exchangeRefreshTokenAndStoreUnderKey(
            $originalToken,
            'tokenB'
        );
        $this->state->rotatedRefreshToken = $originalToken;
    }

    /**
     * @Given the refresh token has been rotated to token B
     */
    public function theRefreshTokenHasBeenRotatedToTokenB(): void
    {
        $this->workflow->exchangeRefreshTokenAndStoreUnderKey(
            $this->workflow->resolveOriginalRefreshToken(),
            'tokenB'
        );
    }

    /**
     * @Given token B has been rotated to token C
     */
    public function tokenBHasBeenRotatedToTokenC(): void
    {
        $storedTokens = $this->state->storedRefreshTokens;
        Assert::assertIsArray($storedTokens);
        Assert::assertArrayHasKey('tokenB', $storedTokens);

        $tokenB = $storedTokens['tokenB'];
        Assert::assertIsString($tokenB);
        Assert::assertNotSame('', $tokenB);

        $this->workflow->exchangeRefreshTokenAndStoreUnderKey(
            $tokenB,
            'tokenC'
        );
    }

    /**
     * @Given the refresh token has been rotated to token B within the grace window
     */
    public function theRefreshTokenHasBeenRotatedToTokenBWithinTheGraceWindow(): void
    {
        $this->theRefreshTokenHasBeenRotatedWithinTheGraceWindow();
    }

    /**
     * @Given the refresh token has been rotated and grace reuse has been consumed
     */
    public function theRefreshTokenHasBeenRotatedAndGraceReuseHasBeenConsumed(): void
    {
        $originalToken = $this->workflow->resolveStateToken(
            'refreshToken'
        );
        $this->workflow->exchangeRefreshTokenAndStoreLatest(
            $originalToken
        );
        $this->workflow->exchangeRefreshTokenAndStoreLatest(
            $originalToken
        );

        $this->state->rotatedRefreshToken = $originalToken;
    }

    /**
     * @Given the refresh token has been rotated and the grace window has expired
     */
    public function theRefreshTokenHasBeenRotatedAndTheGraceWindowHasExpired(): void
    {
        $originalToken = $this->workflow->resolveStateToken(
            'refreshToken'
        );
        $this->workflow->exchangeRefreshTokenAndStoreLatest(
            $originalToken
        );
        $token = $this->refreshRepo->findByTokenHash(
            hash('sha256', $originalToken)
        );
        Assert::assertInstanceOf(
            AuthRefreshToken::class,
            $token
        );
        $token->markAsRotated(
            new DateTimeImmutable('-120 seconds')
        );
        $this->refreshRepo->save($token);
        $this->state->rotatedRefreshToken = $originalToken;
    }
}
