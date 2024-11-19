<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application;

use App\Shared\Application\NotFoundExceptionNormalizer;
use App\Tests\Unit\UnitTestCase;
use GraphQL\Error\Error;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class NotFoundExceptionNormalizerTest extends UnitTestCase
{
    private TranslatorInterface $translatorMock;
    private NotFoundExceptionNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translatorMock =
            $this->createMock(TranslatorInterface::class);
        $this->normalizer =
            new NotFoundExceptionNormalizer($this->translatorMock);
    }

    public function testNormalize(): void
    {
        $id = $this->faker->uuid();
        $errorText = $this->faker->word();
        $this->translatorMock->expects($this->once())
            ->method('trans')
            ->with(
                'error.not.found.graphql',
                ['id' => $id]
            )->willReturn('Item ' . $id . ' not found.');

        $exception = new NotFoundHttpException('Item ' . $id . ' not found.');
        $error = new Error(message: $errorText, previous: $exception);

        $normalizedError = $this->normalizer->normalize($error);

        $this->assertArrayHasKey('message', $normalizedError);
        $this->assertEquals(
            'Item ' . $id . ' not found.',
            $normalizedError['message']
        );
    }

    public function testSupportsNormalization(): void
    {
        $exception = new NotFoundHttpException();
        $error = new Error(message: $this->faker->word(), previous: $exception);

        $this->assertTrue($this->normalizer->supportsNormalization($error));
    }

    public function testSupportsNormalizationWithWrongPreviousType(): void
    {
        $exception = new HttpException($this->faker->numberBetween(200, 500));
        $error = new Error(message: $this->faker->word(), previous: $exception);

        $this->assertFalse($this->normalizer->supportsNormalization($error));
    }

    public function testSupportsNormalizationWithWrongType(): void
    {
        $exception = new NotFoundHttpException();
        $error = new \ApiPlatform\ApiResource\Error(
            $this->faker->word(),
            $this->faker->word(),
            $this->faker->numberBetween(200, 500),
            previous: $exception,
        );

        $this->assertFalse($this->normalizer->supportsNormalization($error));
    }

    public function testGetSupportedTypes(): void
    {
        $expected = [Error::class => true];

        $result = $this->normalizer->getSupportedTypes(null);

        $this->assertEquals($expected, $result);
    }
}
