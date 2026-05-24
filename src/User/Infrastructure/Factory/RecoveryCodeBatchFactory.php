<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Factory;

use App\User\Application\Factory\RecoveryCodeBatchFactoryInterface;
use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\RecoveryCodeFactoryInterface;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use App\User\Infrastructure\Exception\RecoveryCodeGenerationFailedException;
use Symfony\Component\Uid\Factory\UlidFactory;

/** @psalm-suppress UnusedClass */
final readonly class RecoveryCodeBatchFactory implements RecoveryCodeBatchFactoryInterface
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    private const EMPTY_RANDOM_BYTES_MESSAGE = 'Random byte generator returned no bytes.';
    private const UNUSABLE_RANDOM_BYTES_MESSAGE =
        'Random byte generator did not produce usable bytes.';
    private const MAX_RANDOM_BYTE_CHUNK_ATTEMPTS_MULTIPLIER = 16;

    /**
     * @param (\Closure(int): string)|null $randomBytes
     */
    public function __construct(
        private RecoveryCodeRepositoryInterface $recoveryCodeRepository,
        private RecoveryCodeFactoryInterface $recoveryCodeFactory,
        private UlidFactory $ulidFactory,
        private ?\Closure $randomBytes = null,
    ) {
    }

    /**
     * @return list<string>
     */
    #[\Override]
    public function create(User $user): array
    {
        $codes = [];
        $recoveryCodes = [];
        for ($generatedCodes = 0; $generatedCodes < RecoveryCode::COUNT; $generatedCodes++) {
            $plainCode = $this->generateCode();
            $codes[] = $plainCode;
            $recoveryCodes[] = $this->recoveryCodeFactory->create(
                (string) $this->ulidFactory->create(),
                $user->getId(),
                $plainCode
            );
        }

        $this->recoveryCodeRepository->saveAll(...$recoveryCodes);

        return $codes;
    }

    private function generateCode(): string
    {
        $characters = $this->randomCodeCharacters(RecoveryCode::SEGMENT_LENGTH * 2);

        return substr($characters, 0, RecoveryCode::SEGMENT_LENGTH)
            . '-'
            . substr($characters, RecoveryCode::SEGMENT_LENGTH);
    }

    private function randomCodeCharacters(int $length): string
    {
        $code = '';
        $maxAttempts = $length * self::MAX_RANDOM_BYTE_CHUNK_ATTEMPTS_MULTIPLIER;
        $attempts = 0;

        while (strlen($code) < $length) {
            $attempts++;
            $this->guardUsableRandomByteAttempts($attempts, $maxAttempts);
            $code .= $this->acceptedRandomCharacters($this->randomBytes($length));
        }

        return substr($code, 0, $length);
    }

    private function acceptedRandomCharacters(string $bytes): string
    {
        $characters = '';
        $alphabetLength = strlen(self::ALPHABET);
        $maxUnbiasedByte = intdiv(256, $alphabetLength) * $alphabetLength;

        foreach (str_split($bytes) as $byte) {
            $value = ord($byte);
            if ($value < $maxUnbiasedByte) {
                $characters .= self::ALPHABET[$value % $alphabetLength];
            }
        }

        return $characters;
    }

    private function guardUsableRandomByteAttempts(int $attempts, int $maxAttempts): void
    {
        if ($attempts > $maxAttempts) {
            throw new RecoveryCodeGenerationFailedException(
                self::UNUSABLE_RANDOM_BYTES_MESSAGE
            );
        }
    }

    private function randomBytes(int $length): string
    {
        if ($this->randomBytes === null) {
            return random_bytes($length);
        }

        $bytes = ($this->randomBytes)($length);
        if ($bytes === '') {
            throw new RecoveryCodeGenerationFailedException(self::EMPTY_RANDOM_BYTES_MESSAGE);
        }

        return $bytes;
    }
}
