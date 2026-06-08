<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use App\Shared\Domain\Bus\Command\CommandInterface;
use App\Shared\Domain\Bus\Command\CommandResponseInterface;
use App\User\Application\Command\CompletePasskeyRegistrationCommand;
use App\User\Application\Command\CompletePasskeySignInCommand;
use App\User\Application\Command\CompletePasskeySignUpCommand;
use App\User\Application\DTO\PasskeyAuthenticationResult;
use App\User\Domain\Entity\PasskeyCredential;
use App\User\Domain\Entity\User;
use DateTimeImmutable;

/**
 * @phpstan-type GraphQlMap array<string, array|bool|float|int|string|null>
 * @phpstan-type GraphQlResponse array{response: \Symfony\Component\HttpFoundation\Response, body: GraphQlMap}
 * @phpstan-type CredentialInput array<string, array|bool|float|int|string|null>
 * @phpstan-type RequestHeaders array{REMOTE_ADDR: string, HTTP_USER_AGENT: string}
 * @phpstan-type SignUpScenario array{accessToken: string, refreshToken: string, challengeId: string, credential: CredentialInput, label: string, headers: RequestHeaders}
 * @phpstan-type SignInScenario array{pendingSessionId: string, challengeId: string, credential: CredentialInput, headers: RequestHeaders}
 * @phpstan-type RegistrationScenario array{credentialId: string, challengeId: string, credential: CredentialInput, label: string}
 *
 * @psalm-type GraphQlMap = array<string, array|bool|float|int|string|null>
 * @psalm-type GraphQlResponse = array{response: \Symfony\Component\HttpFoundation\Response, body: GraphQlMap}
 * @psalm-type CredentialInput = array<string, array|bool|float|int|string|null>
 * @psalm-type RequestHeaders = array{REMOTE_ADDR: string, HTTP_USER_AGENT: string}
 * @psalm-type SignUpScenario = array{accessToken: string, refreshToken: string, challengeId: string, credential: CredentialInput, label: string, headers: RequestHeaders}
 * @psalm-type SignInScenario = array{pendingSessionId: string, challengeId: string, credential: CredentialInput, headers: RequestHeaders}
 * @psalm-type RegistrationScenario = array{credentialId: string, challengeId: string, credential: CredentialInput, label: string}
 */
final class PasskeyGraphQLCompletionResponseTest extends PasskeyGraphQLAuthIntegrationTestCase
{
    public function testSignUpCompleteMutationSerializesTokenResponseShape(): void
    {
        $scenario = $this->createSignUpScenario();
        $this->interceptSignUpComplete($scenario);

        $response = $this->executeSignUpCompleteMutation($scenario);
        $payload = $this->requireUserPayload($response['body'], 'passkeySignUpCompleteUser');

        $this->assertTrue($payload['success'] ?? false);
        $this->assertFalse($payload['twoFactorEnabled'] ?? true);
        $this->assertSame($scenario['accessToken'], $payload['accessToken'] ?? null);
        $this->assertSame($scenario['refreshToken'], $payload['refreshToken'] ?? null);
        $this->assertNull($payload['pendingSessionId'] ?? null);
        $this->assertNull($payload['credentialId'] ?? null);
    }

    public function testSignInCompleteMutationSerializesPendingTwoFactorResponseShape(): void
    {
        $scenario = $this->createSignInScenario();
        $this->interceptSignInComplete($scenario);

        $response = $this->executeSignInCompleteMutation($scenario);
        $payload = $this->requireUserPayload($response['body'], 'passkeySignInCompleteUser');

        $this->assertTrue($payload['success'] ?? false);
        $this->assertTrue($payload['twoFactorEnabled'] ?? false);
        $this->assertSame($scenario['pendingSessionId'], $payload['pendingSessionId'] ?? null);
        $this->assertNull($payload['accessToken'] ?? null);
        $this->assertNull($payload['refreshToken'] ?? null);
    }

    public function testRegistrationCompleteMutationSerializesCredentialResponseShape(): void
    {
        $user = $this->createUserWithPassword();
        $scenario = $this->createRegistrationScenario();
        $this->interceptRegistrationComplete($user, $scenario);

        $response = $this->executeRegistrationCompleteMutation($user, $scenario);
        $payload = $this->requireUserPayload(
            $response['body'],
            'passkeyRegistrationCompleteUser'
        );

        $this->assertTrue($payload['success'] ?? false);
        $this->assertSame($scenario['credentialId'], $payload['credentialId'] ?? null);
        $this->assertNull($payload['accessToken'] ?? null);
        $this->assertNull($payload['refreshToken'] ?? null);
        $this->assertNull($payload['pendingSessionId'] ?? null);
    }

    /**
     * @return SignUpScenario
     */
    private function createSignUpScenario(): array
    {
        return [
            'accessToken' => $this->faker->sha256(),
            'refreshToken' => $this->faker->sha256(),
            'challengeId' => $this->faker->uuid(),
            'credential' => $this->createCredentialInput(),
            'label' => $this->faker->words(2, true),
            'headers' => $this->createRequestContextHeaders(),
        ];
    }

    /**
     * @param SignUpScenario $scenario
     */
    private function interceptSignUpComplete(array $scenario): void
    {
        $this->commandBus->interceptWith(
            function (CommandInterface $command) use ($scenario): ?CommandResponseInterface {
                $this->assertInstanceOf(CompletePasskeySignUpCommand::class, $command);
                $this->assertSignUpCommand($command, $scenario);
                $command->setResponse(new PasskeyAuthenticationResult(
                    $scenario['accessToken'],
                    $scenario['refreshToken'],
                    true,
                    $this->faker->uuid()
                ));

                return null;
            }
        );
    }

    /**
     * @param SignUpScenario $scenario
     */
    private function assertSignUpCommand(
        CompletePasskeySignUpCommand $command,
        array $scenario
    ): void {
        $this->assertSame($scenario['challengeId'], $command->challengeId);
        $this->assertSame($scenario['credential'], $command->credential);
        $this->assertSame($scenario['label'], $command->label);
        $this->assertTrue($command->rememberMe);
        $this->assertSame($scenario['headers']['REMOTE_ADDR'], $command->ipAddress);
        $this->assertSame($scenario['headers']['HTTP_USER_AGENT'], $command->userAgent);
    }

    /**
     * @param SignUpScenario $scenario
     *
     * @return GraphQlResponse
     */
    private function executeSignUpCompleteMutation(array $scenario): array
    {
        return $this->executePasskeyMutation(
            'passkeySignUpCompleteUser',
            'passkeySignUpCompleteUserInput',
            'success twoFactorEnabled accessToken refreshToken pendingSessionId credentialId',
            [
                'challengeId' => $scenario['challengeId'],
                'credential' => $scenario['credential'],
                'label' => $scenario['label'],
                'rememberMe' => true,
            ],
            $scenario['headers']
        );
    }

    /**
     * @return SignInScenario
     */
    private function createSignInScenario(): array
    {
        return [
            'pendingSessionId' => $this->faker->uuid(),
            'challengeId' => $this->faker->uuid(),
            'credential' => $this->createCredentialInput(),
            'headers' => $this->createRequestContextHeaders(),
        ];
    }

    /**
     * @param SignInScenario $scenario
     */
    private function interceptSignInComplete(array $scenario): void
    {
        $this->commandBus->interceptWith(
            function (CommandInterface $command) use ($scenario): ?CommandResponseInterface {
                $this->assertInstanceOf(CompletePasskeySignInCommand::class, $command);
                $this->assertSignInCommand($command, $scenario);
                $command->setResponse(new PasskeyAuthenticationResult(
                    '',
                    '',
                    true,
                    '',
                    $scenario['pendingSessionId']
                ));

                return null;
            }
        );
    }

    /**
     * @param SignInScenario $scenario
     */
    private function assertSignInCommand(
        CompletePasskeySignInCommand $command,
        array $scenario
    ): void {
        $this->assertSame($scenario['challengeId'], $command->challengeId);
        $this->assertSame($scenario['credential'], $command->credential);
        $this->assertSame($scenario['headers']['REMOTE_ADDR'], $command->ipAddress);
        $this->assertSame($scenario['headers']['HTTP_USER_AGENT'], $command->userAgent);
    }

    /**
     * @param SignInScenario $scenario
     *
     * @return GraphQlResponse
     */
    private function executeSignInCompleteMutation(array $scenario): array
    {
        return $this->executePasskeyMutation(
            'passkeySignInCompleteUser',
            'passkeySignInCompleteUserInput',
            'success twoFactorEnabled accessToken refreshToken pendingSessionId',
            [
                'challengeId' => $scenario['challengeId'],
                'credential' => $scenario['credential'],
            ],
            $scenario['headers']
        );
    }

    /**
     * @return RegistrationScenario
     */
    private function createRegistrationScenario(): array
    {
        return [
            'credentialId' => $this->faker->sha256(),
            'challengeId' => $this->faker->uuid(),
            'credential' => $this->createCredentialInput(),
            'label' => $this->faker->words(2, true),
        ];
    }

    /**
     * @param RegistrationScenario $scenario
     */
    private function interceptRegistrationComplete(User $user, array $scenario): void
    {
        $this->commandBus->interceptWith(
            function (
                CommandInterface $command
            ) use ($scenario, $user): ?CommandResponseInterface {
                $this->assertInstanceOf(CompletePasskeyRegistrationCommand::class, $command);
                $this->assertRegistrationCommand($command, $user, $scenario);
                $command->setResponse($this->createPasskeyCredential($user, $scenario));

                return null;
            }
        );
    }

    /**
     * @param RegistrationScenario $scenario
     */
    private function assertRegistrationCommand(
        CompletePasskeyRegistrationCommand $command,
        User $user,
        array $scenario
    ): void {
        $this->assertSame($scenario['challengeId'], $command->challengeId);
        $this->assertSame($scenario['credential'], $command->credential);
        $this->assertSame($scenario['label'], $command->label);
        $this->assertSame($user->getId(), $command->currentUserId);
    }

    /**
     * @param RegistrationScenario $scenario
     */
    private function createPasskeyCredential(User $user, array $scenario): PasskeyCredential
    {
        return new PasskeyCredential(
            $this->faker->uuid(),
            $user->getId(),
            $scenario['credentialId'],
            json_encode(['credential' => true], JSON_THROW_ON_ERROR),
            $this->faker->words(2, true),
            new DateTimeImmutable()
        );
    }

    /**
     * @param RegistrationScenario $scenario
     *
     * @return GraphQlResponse
     */
    private function executeRegistrationCompleteMutation(User $user, array $scenario): array
    {
        return $this->executePasskeyMutation(
            'passkeyRegistrationCompleteUser',
            'passkeyRegistrationCompleteUserInput',
            'success credentialId accessToken refreshToken pendingSessionId',
            [
                'challengeId' => $scenario['challengeId'],
                'credential' => $scenario['credential'],
                'label' => $scenario['label'],
            ],
            $this->createAuthenticatedHeaders($user->getId())
        );
    }
}
