<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;

final class TwoFactorRecoveryResponseContext implements Context
{
    public function __construct(
        private UserOperationsState $state,
        private readonly UserContextUserManagementServices $userManagement,
        private readonly RecoveryCodeRepositoryInterface $recoveryCodeRepository,
    ) {
    }

    /**
     * @Then all recovery codes for user :email should be invalidated
     */
    public function allRecoveryCodesForUserShouldBeInvalidated(
        string $email
    ): void {
        $user = $this->requireUser($email);

        Assert::assertCount(
            0,
            $this->recoveryCodeRepository->findByUserId($user->getId())
        );
    }

    /**
     * @Then the response should contain :count recovery codes
     */
    public function theResponseShouldContainRecoveryCodes(int $count): void
    {
        Assert::assertCount($count, $this->extractRecoveryCodes());
    }

    /**
     * @Then each recovery code should match the format :format
     */
    public function eachRecoveryCodeShouldMatchTheFormat(
        string $format
    ): void {
        Assert::assertSame('xxxx-xxxx', trim($format, "\"'"));

        foreach ($this->extractRecoveryCodes() as $code) {
            Assert::assertMatchesRegularExpression(
                '/^[A-Za-z0-9]{4}-[A-Za-z0-9]{4}$/',
                $code
            );
        }
    }

    /**
     * @Then all :count recovery codes should be unique
     */
    public function allRecoveryCodesShouldBeUnique(int $count): void
    {
        $codes = $this->extractRecoveryCodes();

        Assert::assertCount($count, $codes);
        Assert::assertCount($count, array_unique($codes));
    }

    /**
     * @Then all previous recovery codes should be invalidated
     */
    public function allPreviousRecoveryCodesShouldBeInvalidated(): void
    {
        $previousCodes = $this->state->previousRecoveryCodes;
        Assert::assertIsArray($previousCodes);

        $user = $this->requireUser($this->resolveScenarioEmail());
        $currentCodes = iterator_to_array(
            $this->recoveryCodeRepository->findByUserId($user->getId())
        );

        foreach ($previousCodes as $previousCode) {
            Assert::assertIsString($previousCode);
            Assert::assertFalse(
                $this->matchesAnyCurrentRecoveryCode($currentCodes, $previousCode)
            );
        }
    }

    /**
     * @Then I store the current recovery codes
     */
    public function iStoreTheCurrentRecoveryCodes(): void
    {
        $storedCodes = $this->state->storedRecoveryCodesByEmail;
        $email = $this->resolveScenarioEmail();

        Assert::assertIsArray($storedCodes);
        Assert::assertArrayHasKey($email, $storedCodes);
        Assert::assertIsArray($storedCodes[$email]);

        $this->state->previousRecoveryCodes = $storedCodes[$email];
    }

    /**
     * @Then the new recovery codes should differ from the stored ones
     */
    public function theNewRecoveryCodesShouldDifferFromTheStoredOnes(): void
    {
        $previousCodes = $this->state->previousRecoveryCodes;
        Assert::assertIsArray($previousCodes);

        $currentCodes = $this->extractRecoveryCodes();

        Assert::assertNotEqualsCanonicalizing($previousCodes, $currentCodes);
        Assert::assertSame([], array_values(array_intersect($previousCodes, $currentCodes)));
    }

    /**
     * @Then each recovery code should be :length characters long
     */
    public function eachRecoveryCodeShouldBeCharactersLong(
        int $length
    ): void {
        foreach ($this->extractRecoveryCodes() as $code) {
            Assert::assertSame($length, strlen($code));
        }
    }

    /**
     * @Then each recovery code should match pattern :pattern
     */
    public function eachRecoveryCodeShouldMatchPattern(
        string $pattern
    ): void {
        $regex = '/^' . trim($pattern, "\"'") . '$/';

        foreach ($this->extractRecoveryCodes() as $code) {
            Assert::assertMatchesRegularExpression($regex, $code);
        }
    }

    /**
     * @return array<string>
     */
    private function extractRecoveryCodes(): array
    {
        $response = $this->state->response;
        Assert::assertNotNull($response);

        $decoded = json_decode((string) $response->getContent(), true);
        Assert::assertIsArray($decoded);
        Assert::assertArrayHasKey('recovery_codes', $decoded);
        Assert::assertIsArray($decoded['recovery_codes']);

        return array_values(
            array_map(
                static fn (mixed $code): string => (string) $code,
                $decoded['recovery_codes']
            )
        );
    }

    /**
     * @param array<RecoveryCode> $currentCodes
     */
    private function matchesAnyCurrentRecoveryCode(
        array $currentCodes,
        string $plainCode
    ): bool {
        foreach ($currentCodes as $currentCode) {
            if ($currentCode->matchesCode($plainCode)) {
                return true;
            }
        }

        return false;
    }

    private function requireUser(string $email): User
    {
        $user = $this->userManagement->userRepository->findByEmail($email);
        if (!$user instanceof User) {
            throw new \RuntimeException(
                sprintf('User with email %s was not found.', $email)
            );
        }

        return $user;
    }

    private function resolveScenarioEmail(): string
    {
        $currentUserEmail = $this->state->currentUserEmail;
        if (is_string($currentUserEmail) && $currentUserEmail !== '') {
            return $currentUserEmail;
        }

        $storedCodes = $this->state->storedRecoveryCodesByEmail;
        if (is_array($storedCodes) && count($storedCodes) === 1) {
            return (string) array_key_first($storedCodes);
        }

        throw new \RuntimeException(
            'Unable to resolve the current recovery-code user email.'
        );
    }
}
