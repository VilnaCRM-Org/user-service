<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application;

use App\Shared\Application\DomainExceptionNormalizer;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Exception\DomainException;
use GraphQL\Error\Error;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DomainExceptionNormalizerTest extends UnitTestCase
{
    private DomainException $previousException;
    private DomainExceptionNormalizer $normalizer;
    private TranslatorInterface $translatorMock;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->translatorMock = $this->createMock(TranslatorInterface::class);
        $this->normalizer =
            new DomainExceptionNormalizer($this->translatorMock);

        $this->previousException = $this->buildPreviousException();
    }

    public function testNormalize(): void
    {
        $errorText = $this->faker->word();

        $this->translatorMock->expects($this->once())
            ->method('trans')
            ->willReturn($errorText);

        $graphqlError = new Error(
            message: $errorText,
            previous: $this->previousException
        );

        $normalizedError = $this->normalizer->normalize($graphqlError);

        $this->assertEquals($errorText, $normalizedError['message']);
    }

    public function testSupportsNormalizationWithoutPrevious(): void
    {
        $errorText = $this->faker->word();
        $graphqlError = new Error($errorText);

        $supportsNormalization =
            $this->normalizer->supportsNormalization($graphqlError);

        $this->assertFalse($supportsNormalization);
    }

    public function testSupportsNormalization(): void
    {
        $errorText = $this->faker->word();

        $graphqlError = new Error(
            message: $errorText,
            previous: $this->previousException
        );

        $supportsNormalization =
            $this->normalizer->supportsNormalization($graphqlError);

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

        $supportsNormalization =
            $this->normalizer->supportsNormalization($error);

        $this->assertFalse($supportsNormalization);
    }

    public function testGetSupportedTypes(): void
    {
        $expected = [Error::class => false];

        $result = $this->normalizer->getSupportedTypes(null);

        $this->assertEquals($expected, $result);
    }

    private function buildPreviousException(): DomainException
    {
        $template = $this->faker->word();
        $args = [];
        return new class($template, $args) extends DomainException {
            /**
             * @param array<string> $args
             */
            public function __construct(
                private string $template,
                private array $args
            ) {
                parent::__construct();
            }

            #[\Override]
            public function getTranslationTemplate(): string
            {
                return $this->template;
            }

            /**
             * @return array<string>
             */
            #[\Override]
            public function getTranslationArgs(): array
            {
                return $this->args;
            }
        };
    }
}
