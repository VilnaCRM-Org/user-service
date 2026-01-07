<?php

declare(strict_types=1);

namespace App\Shared\Application\Normalizer;

use App\User\Domain\Exception\DomainException;
use GraphQL\Error\Error;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class DomainExceptionNormalizer implements NormalizerInterface
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

    /**
     * @param Error $object
     * @param string|null $format
     * @param array<string,array<string>> $context
     *
     * @return array<string,array<string>>
     */
    #[\Override]
    public function normalize(
        mixed $object,
        mixed $format = null,
        array $context = []
    ): array {
        $exception = $object->getPrevious();
        $translatedMessage = $this->translator->trans(
            $exception->getTranslationTemplate(),
            $exception->getTranslationArgs()
        );

        return ['message' => $translatedMessage];
    }

    /**
     * @param object $data
     * @param string|null $format
     * @param array<string> $context
     */
    #[\Override]
    public function supportsNormalization(
        mixed $data,
        mixed $format = null,
        array $context = []
    ): bool {
        return $data instanceof Error && $data->getPrevious()
            instanceof DomainException;
    }

    /**
     * @return array<string, bool>
     */
    #[\Override]
    public function getSupportedTypes(?string $format): array
    {
        return [Error::class => false];
    }
}
