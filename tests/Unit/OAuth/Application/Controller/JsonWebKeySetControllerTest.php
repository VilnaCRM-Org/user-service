<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Application\Controller;

use App\OAuth\Application\Controller\JsonWebKeySetController;
use App\Shared\Application\Provider\JwtVerificationKeyProviderInterface;
use App\Shared\Domain\ValueObject\JwtVerificationKey;
use App\Tests\Unit\UnitTestCase;
use RuntimeException;

final class JsonWebKeySetControllerTest extends UnitTestCase
{
    public function testPublishesEveryVerificationKeyAsAJwkSet(): void
    {
        $current = new JwtVerificationKey('arn:current', 'kid-current', 'PEM', 'n-1', 'AQAB');
        $previous = new JwtVerificationKey('arn:previous', 'kid-previous', 'PEM', 'n-2', 'AQAB');
        $keyProvider = $this->createMock(JwtVerificationKeyProviderInterface::class);
        $keyProvider->method('verificationKeys')->willReturn([$current, $previous]);

        $response = (new JsonWebKeySetController($keyProvider))();

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(
            ['keys' => [$current->toJwk(), $previous->toJwk()]],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR)
        );
        self::assertSame('application/json', $response->headers->get('Content-Type'));
    }

    public function testPublishesAnEmptySetWithoutKeys(): void
    {
        $keyProvider = $this->createMock(JwtVerificationKeyProviderInterface::class);
        $keyProvider->method('verificationKeys')->willReturn([]);

        $response = (new JsonWebKeySetController($keyProvider))();

        self::assertSame('{"keys":[]}', $response->getContent());
    }

    public function testKmsErrorsFailClosed(): void
    {
        $keyProvider = $this->createMock(JwtVerificationKeyProviderInterface::class);
        $keyProvider->method('verificationKeys')->willThrowException(new RuntimeException('KMS'));

        $this->expectException(RuntimeException::class);

        (new JsonWebKeySetController($keyProvider))();
    }
}
