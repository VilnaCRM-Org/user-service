<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use Symfony\Component\Uid\Factory\UlidFactory;

/**
 * @psalm-api
 */
final readonly class RecoveryCodeGeneratorService implements
    RecoveryCodeGeneratorInterface
{
    private const RECOVERY_CODE_COUNT = 8;
    private const RECOVERY_CODE_SEGMENT_LENGTH = 4;

    public function __construct(
        private RecoveryCodeRepositoryInterface $recoveryCodeRepository,
        private UlidFactory $ulidFactory,
    ) {
    }

    /**
     * @return list<string>
     */
    #[\Override]
    public function generateAndStore(User $user): array
    {
        $codes = [];
        for ($i = 0; $i < self::RECOVERY_CODE_COUNT; $i++) {
            $plainCode = $this->generateCode();
            $codes[] = $plainCode;
            $this->recoveryCodeRepository->save(
                new RecoveryCode(
                    (string) $this->ulidFactory->create(),
                    $user->getId(),
                    $plainCode
                )
            );
        }

        return $codes;
    }

    private function generateCode(): string
    {
        $segment1 = $this->randomSegment(self::RECOVERY_CODE_SEGMENT_LENGTH);
        $segment2 = $this->randomSegment(self::RECOVERY_CODE_SEGMENT_LENGTH);

        return $segment1 . '-' . $segment2;
    }

    private function randomSegment(int $length): string
    {
        $bytes = intdiv($length, 2);

        return strtoupper(bin2hex(random_bytes($bytes)));
    }
}
