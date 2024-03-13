<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application;

use App\Shared\Application\DomainExceptionNormalizer;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Exception\DomainException;
use GraphQL\Error\Error;
use Symfony\Contracts\Translation\TranslatorInterface;

class DomainExceptionNormalizerTest extends UnitTestCase
{
    private DomainException $previousException;

    protected function setUp(): void
    {
        parent::setUp();

        $template = $this->faker->word();
        $args = [];
        $this->previousException = new class($template, $args) extends DomainException {
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

    }


    public function testNormalize(): void
    {
        $errorText = $this->faker->word();

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->once())
            ->method('trans')
            ->willReturn($errorText);

        $graphqlError = new Error(message: $errorText, previous: $this->previousException);

        $normalizer = new DomainExceptionNormalizer($translator);

        $normalizedError = $normalizer->normalize($graphqlError);

        $this->assertEquals($errorText, $normalizedError['message']);
        $this->assertEquals('internal', $normalizedError['extensions']['category']);
    }

    public function testSupportsNormalizationWithoutPrevious(): void
    {
        $errorText = $this->faker->word();
        $graphqlError = new Error($errorText);

        $normalizer = new DomainExceptionNormalizer($this->createMock(TranslatorInterface::class));

        $supportsNormalization = $normalizer->supportsNormalization($graphqlError);

        $this->assertFalse($supportsNormalization);
    }

    public function testSupportsNormalization(): void
    {
        $errorText = $this->faker->word();

        $graphqlError = new Error(message: $errorText, previous: $this->previousException);

        $normalizer = new DomainExceptionNormalizer($this->createMock(TranslatorInterface::class));

        $supportsNormalization = $normalizer->supportsNormalization($graphqlError);

        $this->assertTrue($supportsNormalization);
    }


    public function testSupportsNormalizationWithWrongType(): void
    {
        $error = new \ApiPlatform\ApiResource\Error(
            $this->faker->word(),
            $this->faker->word(),
            $this->faker->numberBetween(200, 500),
            previous: $this->previousException,
        );

        $normalizer = new DomainExceptionNormalizer($this->createMock(TranslatorInterface::class));

        $supportsNormalization = $normalizer->supportsNormalization($error);

        $this->assertFalse($supportsNormalization);
    }
}
