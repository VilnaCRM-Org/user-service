<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

final class AuthPayload
{
    private string $id = 'auth';

    private bool $success = true;

    private ?bool $twoFactorEnabled = null;

    private ?string $accessToken = null;

    private ?string $refreshToken = null;

    private ?string $pendingSessionId = null;

    private ?string $challengeId = null;

    /**
     * @var array<string, scalar|array|null>
     */
    private array $publicKey = [];

    private ?string $credentialId = null;

    private ?string $otpauthUri = null;

    private ?string $secret = null;

    /**
     * @var list<string>
     */
    private array $recoveryCodes = [];

    private ?int $recoveryCodesRemaining = null;

    private ?string $warning = null;

    public static function createSuccessPayload(): self
    {
        return (new self())->setId('auth-success');
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function setTwoFactorEnabled(?bool $twoFactorEnabled): self
    {
        $this->twoFactorEnabled = $twoFactorEnabled;

        return $this;
    }

    public function setAccessToken(?string $accessToken): self
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function setRefreshToken(?string $refreshToken): self
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    public function setPendingSessionId(?string $pendingSessionId): self
    {
        $this->pendingSessionId = $pendingSessionId;

        return $this;
    }

    public function setChallengeId(?string $challengeId): self
    {
        $this->challengeId = $challengeId;

        return $this;
    }

    /**
     * @param array<string, scalar|array|null> $publicKey
     */
    public function setPublicKey(array $publicKey): self
    {
        $this->publicKey = $publicKey;

        return $this;
    }

    public function setCredentialId(?string $credentialId): self
    {
        $this->credentialId = $credentialId;

        return $this;
    }

    public function setOtpauthUri(?string $otpauthUri): self
    {
        $this->otpauthUri = $otpauthUri;

        return $this;
    }

    public function setSecret(?string $secret): self
    {
        $this->secret = $secret;

        return $this;
    }

    /**
     * @param list<string> $recoveryCodes
     */
    public function setRecoveryCodes(array $recoveryCodes): self
    {
        $this->recoveryCodes = $recoveryCodes;

        return $this;
    }

    public function setRecoveryCodesRemaining(?int $recoveryCodesRemaining): self
    {
        $this->recoveryCodesRemaining = $recoveryCodesRemaining;

        return $this;
    }

    public function setWarning(?string $warning): self
    {
        $this->warning = $warning;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isTwoFactorEnabled(): ?bool
    {
        return $this->twoFactorEnabled;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function getPendingSessionId(): ?string
    {
        return $this->pendingSessionId;
    }

    public function getChallengeId(): ?string
    {
        return $this->challengeId;
    }

    /**
     * @return array<string, scalar|array|null>
     */
    public function getPublicKey(): array
    {
        return $this->publicKey;
    }

    public function getCredentialId(): ?string
    {
        return $this->credentialId;
    }

    public function getOtpauthUri(): ?string
    {
        return $this->otpauthUri;
    }

    public function getSecret(): ?string
    {
        return $this->secret;
    }

    /**
     * @return list<string>
     */
    public function getRecoveryCodes(): array
    {
        return $this->recoveryCodes;
    }

    public function getRecoveryCodesRemaining(): ?int
    {
        return $this->recoveryCodesRemaining;
    }

    public function getWarning(): ?string
    {
        return $this->warning;
    }
}
