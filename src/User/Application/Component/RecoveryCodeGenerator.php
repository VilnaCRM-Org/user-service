<?php

declare(strict_types=1);

namespace App\User\Application\Component;

use App\User\Domain\Entity\RecoveryCode;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use Symfony\Component\Uid\Factory\UlidFactory;

final readonly class RecoveryCodeGenerator implements RecoveryCodeGeneratorInterface
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

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
        for ($generatedCodes = 0; $generatedCodes < RecoveryCode::COUNT; $generatedCodes++) {
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
        return $this->randomSegment(RecoveryCode::SEGMENT_LENGTH)
            . '-'
            . $this->randomSegment(RecoveryCode::SEGMENT_LENGTH);
    }

    private function randomSegment(int $length): string
    {
        $segment = '';
        $alphabetLength = strlen(self::ALPHABET);

        for ($index = 0; $index < $length; $index++) {
            $segment .= self::ALPHABET[random_int(0, $alphabetLength - 1)];
        }

        return $segment;
    }
}
