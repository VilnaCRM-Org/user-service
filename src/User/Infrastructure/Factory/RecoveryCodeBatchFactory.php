<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Factory;

use App\User\Application\Factory\RecoveryCodeBatchFactoryInterface;
use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\RecoveryCodeFactoryInterface;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use Closure;
use function random_bytes;
use Symfony\Component\Uid\Factory\UlidFactory;

/** @psalm-suppress UnusedClass */
final readonly class RecoveryCodeBatchFactory implements RecoveryCodeBatchFactoryInterface
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    /**
     * @var Closure(int): string
     */
    private Closure $randomBytes;

    /**
     * @param Closure(int): string|null $randomBytes
     */
    public function __construct(
        private RecoveryCodeRepositoryInterface $recoveryCodeRepository,
        private RecoveryCodeFactoryInterface $recoveryCodeFactory,
        private UlidFactory $ulidFactory,
        ?Closure $randomBytes = null,
    ) {
        $this->randomBytes = $randomBytes
            ?? static fn (int $length): string => random_bytes($length);
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
        $alphabetLength = strlen(self::ALPHABET);
        $maxUnbiasedByte = intdiv(256, $alphabetLength) * $alphabetLength;

        while (strlen($code) < $length) {
            foreach (str_split(($this->randomBytes)($length)) as $byte) {
                $value = ord($byte);
                if ($value >= $maxUnbiasedByte) {
                    continue;
                }

                $code .= self::ALPHABET[$value % $alphabetLength];
                if (strlen($code) === $length) {
                    break;
                }
            }
        }

        return $code;
    }
}
