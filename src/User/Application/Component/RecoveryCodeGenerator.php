<?php

declare(strict_types=1);

namespace App\User\Application\Component;

use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use Symfony\Component\Uid\Factory\UlidFactory;

final readonly class RecoveryCodeGenerator implements RecoveryCodeGeneratorInterface
{
    private const RECOVERY_CODE_COUNT = 8;
    private const RECOVERY_CODE_SEGMENT_LENGTH = 4;

    public function __construct(
        private RecoveryCodeRepositoryInterface $recoveryCodeRepository,
        private UlidFactory $ulidFactory,
    ) {
    }

    #[\Override]
    /**
     * @return list<string>
     */
    public function generateAndStore(User $user): array
    {
        $codes = [];
        for ($i = 0; $i < self::RECOVERY_CODE_COUNT; $i++) {
            $plainCode = $this->generateCode();
            $codes[] = $plainCode;
            $this->recoveryCodeRepository->save(new RecoveryCode(
                (string) $this->ulidFactory->create(),
                $user->getId(),
                $plainCode
            ));
        }

        return $codes;
    }

    private function generateCode(): string
    {
        return $this->randomSegment(self::RECOVERY_CODE_SEGMENT_LENGTH)
            . '-'
            . $this->randomSegment(self::RECOVERY_CODE_SEGMENT_LENGTH);
    }

    private function randomSegment(int $length): string
    {
        return strtoupper(bin2hex(random_bytes(intdiv($length, 2))));
    }
}
