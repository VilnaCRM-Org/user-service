<?php

namespace App\Tests\Unit\Shared\Application;

use App\Shared\Application\NotFoundExceptionNormalizer;
use App\Tests\Unit\UnitTestCase;
use GraphQL\Error\Error;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

class NotFoundExceptionNormalizerTest extends UnitTestCase
{
    public function testNormalize()
    {
        $id = $this->faker->uuid();
        $errorText = $this->faker->word();
        $translatorMock = $this->createMock(TranslatorInterface::class);
        $translatorMock->expects($this->once())
            ->method('trans')
            ->with(
                'error.not.found.graphql',
                ['id' => $id]
            )
            ->willReturn('Item ' . $id . ' not found.');

        $exception = new NotFoundHttpException('Item ' . $id . ' not found.');
        $error = new Error(message: $errorText, previous: $exception);

        $normalizer = new NotFoundExceptionNormalizer($translatorMock);

        $normalizedError = $normalizer->normalize($error);

        $this->assertArrayHasKey('message', $normalizedError);
        $this->assertEquals('Item ' . $id . ' not found.', $normalizedError['message']);
        $this->assertEquals('internal', $normalizedError['extensions']['category']);
    }

    public function testSupportsNormalization()
    {
        $translatorMock = $this->createMock(TranslatorInterface::class);
        $normalizer = new NotFoundExceptionNormalizer($translatorMock);

        $exception = new NotFoundHttpException();
        $error = new Error(message: $this->faker->word(), previous: $exception);

        $this->assertTrue($normalizer->supportsNormalization($error));
    }

    public function testSupportsNormalizationWithWrongPreviousType()
    {
        $translatorMock = $this->createMock(TranslatorInterface::class);
        $normalizer = new NotFoundExceptionNormalizer($translatorMock);

        $exception = new HttpException($this->faker->numberBetween(200, 500));
        $error = new Error(message: $this->faker->word(), previous: $exception);

        $this->assertFalse($normalizer->supportsNormalization($error));
    }

    public function testSupportsNormalizationWithWrongType()
    {
        $translatorMock = $this->createMock(TranslatorInterface::class);
        $normalizer = new NotFoundExceptionNormalizer($translatorMock);

        $exception = new NotFoundHttpException();
        $error = new \ApiPlatform\ApiResource\Error(
            $this->faker->word(),
            $this->faker->word(),
            $this->faker->numberBetween(200, 500),
            previous: $exception,
        );

        $this->assertFalse($normalizer->supportsNormalization($error));
    }
}