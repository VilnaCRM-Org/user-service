<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use Symfony\Component\HttpFoundation\Response;

/**
 * @phpstan-type GraphQlMap array<string, array|bool|float|int|string|null>
 * @phpstan-type GraphQlResponse array{response: Response, body: GraphQlMap}
 *
 * @psalm-type GraphQlMap = array<string, array|bool|float|int|string|null>
 * @psalm-type GraphQlResponse = array{response: Response, body: GraphQlMap}
 */
final class PasskeyGraphQLAuthOptionsIntegrationTest extends PasskeyGraphQLAuthIntegrationTestCase
{
    public function testSignUpOptionsMutationReturnsBrowserSafePublicKey(): void
    {
        $response = $this->executeSignUpOptionsMutation($this->createSignUpOptionsInput());

        $this->assertSame(Response::HTTP_OK, $response['response']->getStatusCode());
        $payload = $this->requireUserPayload($response['body'], 'passkeySignUpOptionsUser');

        $this->assertTrue($payload['success'] ?? false);
        $this->assertNotSame('', $this->requireStringField($payload, 'challengeId'));
        $this->assertBrowserSafeRegistrationPublicKey($this->requirePublicKey($payload));
    }

    public function testSignUpOptionsMutationRejectsInvalidEmail(): void
    {
        $response = $this->executeSignUpOptionsMutation(
            $this->createSignUpOptionsInput($this->faker->word()),
            'success challengeId'
        );

        $this->assertGraphQlErrorsContain(
            $response['body'],
            'This value is not a valid email address.'
        );
        $this->assertNoUserPayloadValues(
            $response['body'],
            'passkeySignUpOptionsUser',
            ['challengeId', 'publicKey']
        );
    }

    public function testSignUpOptionsMutationRejectsExistingEmail(): void
    {
        $user = $this->createUserWithPassword();
        $response = $this->executeSignUpOptionsMutation(
            $this->createSignUpOptionsInput($user->getEmail()),
            'success challengeId'
        );

        $this->assertGraphQlErrorsContain($response['body'], 'Email is already registered.');
        $this->assertNoUserPayloadValues(
            $response['body'],
            'passkeySignUpOptionsUser',
            ['challengeId']
        );
        $this->assertNoSignupChallengeWasCreatedForEmail($user->getEmail());
    }

    public function testSignInOptionsMutationPreservesPrivacyShapeForKnownAndUnknownEmails(): void
    {
        $knownUser = $this->createUserWithPassword();

        $known = $this->executeSignInOptionsMutation($knownUser->getEmail());
        $unknown = $this->executeSignInOptionsMutation($this->faker->unique()->safeEmail());

        $knownPayload = $this->requireUserPayload($known['body'], 'passkeySignInOptionsUser');
        $unknownPayload = $this->requireUserPayload($unknown['body'], 'passkeySignInOptionsUser');

        $this->assertPasskeySignInOptionsPayload($knownPayload);
        $this->assertPasskeySignInOptionsPayload($unknownPayload);
        $this->assertSame(
            $this->publicKeyShapeWithoutChallenge($this->requirePublicKey($knownPayload)),
            $this->publicKeyShapeWithoutChallenge($this->requirePublicKey($unknownPayload))
        );
    }

    public function testRegistrationOptionsMutationRequiresAuthentication(): void
    {
        $response = $this->executeRegistrationOptionsMutation();

        $this->assertGraphQlErrorsContain($response['body'], 'Access Denied');
        $this->assertNoUserPayloadValues(
            $response['body'],
            'passkeyRegistrationOptionsUser',
            ['challengeId', 'publicKey']
        );
    }

    public function testRegistrationOptionsMutationReturnsBrowserSafePublicKey(): void
    {
        $user = $this->createUserWithPassword();
        $response = $this->executeRegistrationOptionsMutation(
            $this->createAuthenticatedHeaders($user->getId())
        );

        $this->assertSame(Response::HTTP_OK, $response['response']->getStatusCode());
        $payload = $this->requireUserPayload(
            $response['body'],
            'passkeyRegistrationOptionsUser'
        );

        $this->assertTrue($payload['success'] ?? false);
        $this->assertNotSame('', $this->requireStringField($payload, 'challengeId'));
        $this->assertBrowserSafeRegistrationPublicKey($this->requirePublicKey($payload));
    }

    /**
     * @param array<string, string> $headers
     *
     * @return GraphQlResponse
     */
    private function executeRegistrationOptionsMutation(array $headers = []): array
    {
        return $this->executePasskeyMutation(
            'passkeyRegistrationOptionsUser',
            'passkeyRegistrationOptionsUserInput',
            'success challengeId publicKey',
            [],
            $headers
        );
    }

    /**
     * @param array<string, array|bool|float|int|string|null> $input
     *
     * @return GraphQlResponse
     */
    private function executeSignUpOptionsMutation(
        array $input,
        string $selectionSet = 'success challengeId publicKey'
    ): array {
        return $this->executePasskeyMutation(
            'passkeySignUpOptionsUser',
            'passkeySignUpOptionsUserInput',
            $selectionSet,
            $input
        );
    }

    /**
     * @return array{email: string, initials: string, displayName: string}
     */
    private function createSignUpOptionsInput(?string $email = null): array
    {
        return [
            'email' => $email ?? $this->faker->unique()->safeEmail(),
            'initials' => strtoupper($this->faker->lexify('??')),
            'displayName' => $this->faker->name(),
        ];
    }
}
