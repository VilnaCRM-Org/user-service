<?php

declare(strict_types=1);

namespace App\Shared\Application;

use GraphQL\Error\Error;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class NotFoundExceptionNormalizer implements NormalizerInterface
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
        $errorMessage = $exception->getMessage();

        $pattern = '/Item (.*?) not found./';
        preg_match($pattern, $errorMessage, $matches);

        $id = $matches[1] ?? 'unknown';

        $translatedMessage = $this->translator->trans(
            'error.not.found.graphql',
            ['id' => $id]
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
            instanceof NotFoundHttpException;
    }

    /**
     * @return array<string, bool>
     */
    #[\Override]
    public function getSupportedTypes(?string $format): array
    {
        return [Error::class => true];
    }
}
