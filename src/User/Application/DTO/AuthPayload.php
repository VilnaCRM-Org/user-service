<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Attribute\Groups;

final class AuthPayload
{
    #[ApiProperty(identifier: true)]
    #[Groups(['auth:output'])]
    private string $id = 'auth';

    #[Groups(['auth:output'])]
    private bool $success = true;

    #[Groups(['auth:output'])]
    private ?bool $twoFactorEnabled = null;

    #[Groups(['auth:output'])]
    private ?string $accessToken = null;

    #[Groups(['auth:output'])]
    private ?string $refreshToken = null;

    #[Groups(['auth:output'])]
    private ?string $pendingSessionId = null;

    #[Groups(['auth:output'])]
    private ?string $otpauthUri = null;

    #[Groups(['auth:output'])]
    private ?string $secret = null;

    /**
     * @var list<string>
     */
    #[Groups(['auth:output'])]
    private array $recoveryCodes = [];

    #[Groups(['auth:output'])]
    private ?int $recoveryCodesRemaining = null;

    #[Groups(['auth:output'])]
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
