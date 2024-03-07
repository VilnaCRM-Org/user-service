<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application;

use App\Shared\Application\ExceptionNormalizer;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Exception\DomainException;
use GraphQL\Error\Error;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExceptionNormalizerTest extends UnitTestCase
{
    public function testNormalize(): void
    {
        $template = $this->faker->word();
        $args = [];
        $errorText = $this->faker->word();

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->once())
            ->method('trans')
            ->willReturn($errorText);

        $exception = new class($template, $args) extends DomainException {
            public function __construct(
                private string $template,
                private array $args
            ) {
                parent::__construct();
            }

            public function getTranslationTemplate(): string
            {
                return $this->template;
            }

            public function getTranslationArgs(): array
            {
                return $this->args;
            }
        };


        $graphqlError = new Error(message: $errorText, previous: $exception);

        $normalizer = new ExceptionNormalizer($translator);

        $normalizedError = $normalizer->normalize($graphqlError);

        $this->assertEquals($errorText, $normalizedError['message']);
    }

    public function testSupportsNormalization(): void
    {
        $errorText = $this->faker->word();
        $graphqlError = new Error($errorText);

        $normalizer = new ExceptionNormalizer($this->createMock(TranslatorInterface::class));

        $supportsNormalization = $normalizer->supportsNormalization($graphqlError);

        $this->assertFalse($supportsNormalization);
    }
}
