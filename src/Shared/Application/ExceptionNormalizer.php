<?php

declare(strict_types=1);

namespace App\Shared\Application;

use App\User\Domain\Exception\DuplicateEmailException;
use GraphQL\Error\Error;
use GraphQL\Error\FormattedError;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ExceptionNormalizer implements NormalizerInterface
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
    public function normalize(
        mixed $object,
        mixed $format = null,
        array $context = []
    ): array {
        $exception = $object->getPrevious();
        $error = FormattedError::createFromException($exception);

        $error['message'] = $this->translator->trans(
            $exception->getTranslationTemplate(),
            $exception->getTranslationArgs()
        );

        return $error;
    }

    /**
     * @param object $data
     * @param string|null $format
     */
    public function supportsNormalization(
        mixed $data,
        mixed $format = null
    ): bool {
        return $data instanceof Error && $data->getPrevious()
            instanceof DuplicateEmailException;
    }
}
