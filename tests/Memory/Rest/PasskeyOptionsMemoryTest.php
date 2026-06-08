<?php

declare(strict_types=1);

namespace App\Tests\Memory\Rest;

use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\Group('memory')]
#[\PHPUnit\Framework\Attributes\Group('memory-rest')]
final class PasskeyOptionsMemoryTest extends RestMemoryWebTestCase
{
    public function testPasskeySignupOptionsScenarioReusesSameKernelAcrossRepeatedRequests(): void
    {
        $this->runRepeatedRestScenario('passkeySignupOptions', function (): void {
            ['response' => $response, 'body' => $body] = $this->requestJson(
                'POST',
                '/api/passkeys/signup/options',
                [
                    'email' => $this->uniqueEmail('memory-rest-passkey-signup'),
                    'initials' => strtoupper($this->faker->lexify('??')),
                    'displayName' => $this->faker->name(),
                ]
            );

            self::assertSame(Response::HTTP_OK, $response->getStatusCode());
            $this->assertOptionsResponse($body);
        });
    }

    public function testPasskeySigninOptionsScenarioReusesSameKernelAcrossRepeatedRequests(): void
    {
        $this->runRepeatedRestScenario('passkeySigninOptions', function (): void {
            $user = $this->createConfirmedUser();

            ['response' => $response, 'body' => $body] = $this->requestJson(
                'POST',
                '/api/passkeys/signin/options',
                [
                    'email' => $user->getEmail(),
                    'rememberMe' => false,
                ]
            );

            self::assertSame(Response::HTTP_OK, $response->getStatusCode());
            $this->assertOptionsResponse($body);
        });
    }

    public function testPasskeyRegisterOptionsScenarioReusesSameKernelAcrossRepeatedRequests(): void
    {
        $this->runRepeatedRestScenario('passkeyRegistrationOptions', function (): void {
            $user = $this->createConfirmedUser();
            $headers = $this->createUserAuthorizationHeader($user->getId());

            ['response' => $response, 'body' => $body] = $this->requestJson(
                'POST',
                '/api/passkeys/register/options',
                [],
                $headers
            );

            self::assertSame(Response::HTTP_OK, $response->getStatusCode());
            $this->assertOptionsResponse($body);
        });
    }

    /**
     * @param array<string, array|bool|float|int|string|null> $body
     */
    private function assertOptionsResponse(array $body): void
    {
        self::assertIsString($body['challenge_id'] ?? null);
        self::assertNotSame('', $body['challenge_id']);
        self::assertIsArray($body['public_key'] ?? null);
    }
}
