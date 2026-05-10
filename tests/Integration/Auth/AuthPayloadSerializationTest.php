<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use App\User\Application\DTO\AuthPayload;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class AuthPayloadSerializationTest extends AuthIntegrationTestCase
{
    public function testSuccessFieldNormalizesWithoutRecursiveSerialization(): void
    {
        $normalizedPayload = $this->container
            ->get(NormalizerInterface::class)
            ->normalize(
                AuthPayload::createSuccessPayload(),
                'json',
                [
                    'resource_class' => AuthPayload::class,
                    'api_sub_level' => true,
                    'allow_extra_attributes' => false,
                    'groups' => ['auth:output'],
                    'attributes' => ['success'],
                ]
            );

        $this->assertSame(
            ['success' => true],
            $normalizedPayload
        );
    }

    public function testAccessorsExposeDefaultSuccessPayloadValues(): void
    {
        $payload = AuthPayload::createSuccessPayload();

        $this->assertSame('auth-success', $payload->getId());
        $this->assertTrue($payload->isSuccess());
        $this->assertNull($payload->isTwoFactorEnabled());
        $this->assertNull($payload->getAccessToken());
        $this->assertNull($payload->getRefreshToken());
        $this->assertNull($payload->getPendingSessionId());
        $this->assertNull($payload->getOtpauthUri());
        $this->assertNull($payload->getSecret());
        $this->assertSame([], $payload->getRecoveryCodes());
        $this->assertNull($payload->getRecoveryCodesRemaining());
        $this->assertNull($payload->getWarning());
    }
}
