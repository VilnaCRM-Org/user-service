<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver;

use App\Shared\Application\Resolver\HttpExceptionDetailResolver;
use App\Tests\Unit\UnitTestCase;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class HttpExceptionDetailResolverTest extends UnitTestCase
{
    private TranslatorInterface $translator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->createMock(TranslatorInterface::class);
    }

    public function testResolveReturnsTranslatedMessageForNotFoundHttpException(): void
    {
        $translatedMessage = $this->faker->sentence();
        $this->translator
            ->method('trans')
            ->with('error.not.found.http')
            ->willReturn($translatedMessage);

        $resolver = new HttpExceptionDetailResolver($this->translator);
        $exception = new NotFoundHttpException($this->faker->sentence());

        self::assertSame($translatedMessage, $resolver->resolve($exception));
    }

    public function testResolveReturnsExceptionMessageForOtherExceptions(): void
    {
        $exceptionMessage = $this->faker->sentence();
        $resolver = new HttpExceptionDetailResolver($this->translator);
        $exception = new RuntimeException($exceptionMessage);

        self::assertSame($exceptionMessage, $resolver->resolve($exception));
    }
}
