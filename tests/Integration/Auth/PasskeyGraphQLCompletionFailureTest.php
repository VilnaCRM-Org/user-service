<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use App\Shared\Domain\Bus\Command\CommandInterface;
use App\Shared\Domain\Bus\Command\CommandResponseInterface;
use App\User\Application\Command\CompletePasskeyRegistrationCommand;
use App\User\Domain\Entity\User;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * @phpstan-type GraphQlMap array<string, array|bool|float|int|string|null>
 * @phpstan-type GraphQlResponse array{response: \Symfony\Component\HttpFoundation\Response, body: GraphQlMap}
 * @phpstan-type CredentialInput array<string, array|bool|float|int|string|null>
 * @phpstan-type RegistrationScenario array{challengeId: string, credential: CredentialInput, label: string}
 *
 * @psalm-type GraphQlMap = array<string, array|bool|float|int|string|null>
 * @psalm-type GraphQlResponse = array{response: \Symfony\Component\HttpFoundation\Response, body: GraphQlMap}
 * @psalm-type CredentialInput = array<string, array|bool|float|int|string|null>
 * @psalm-type RegistrationScenario = array{challengeId: string, credential: CredentialInput, label: string}
 */
final class PasskeyGraphQLCompletionFailureTest extends PasskeyGraphQLAuthIntegrationTestCase
{
    public function testSignUpCompleteMutationRejectsInvalidCredentialJson(): void
    {
        $challengeId = $this->createSignUpChallengeId();
        $response = $this->executeSignUpCompleteWithInvalidCredential($challengeId);

        $this->assertGraphQlHasErrors($response['body']);
        $this->assertNoAuthResultPayloadValues($response['body'], 'passkeySignUpCompleteUser');
    }

    public function testSignInCompleteMutationRejectsReusedChallengeAfterInvalidCredential(): void
    {
        $input = $this->createSignInCompleteInputFromOptions();

        $invalid = $this->executeSignInCompleteMutation($input);
        $this->assertGraphQlHasErrors($invalid['body']);
        $this->assertNoAuthResultPayloadValues($invalid['body'], 'passkeySignInCompleteUser');

        $reused = $this->executeSignInCompleteMutation($input);
        $this->assertGraphQlErrorsContain(
            $reused['body'],
            'Invalid or expired passkey challenge.'
        );
        $this->assertNoAuthResultPayloadValues($reused['body'], 'passkeySignInCompleteUser');
    }

    public function testRegistrationCompleteMutationRejectsChallengeOwnedByAnotherUser(): void
    {
        $owner = $this->createUserWithPassword();
        $otherUser = $this->createUserWithPassword();
        $challengeId = $this->createRegistrationChallengeId($owner);
        $field = 'passkeyRegistrationCompleteUser';
        $scenario = [
            'challengeId' => $challengeId,
            'credential' => $this->createCredentialInput(),
            'label' => $this->faker->words(2, true),
        ];

        $response = $this->executeRegistrationCompleteForUser($otherUser, $scenario);

        $this->assertGraphQlErrorsContain(
            $response['body'],
            'Invalid or expired passkey challenge.'
        );
        $this->assertNoAuthResultPayloadValues($response['body'], $field);

        $ownerResponse = $this->executeRegistrationCompleteForUser($owner, $scenario);

        $this->assertGraphQlErrorsContain($ownerResponse['body'], 'Invalid passkey credential.');
        $this->assertNoAuthResultPayloadValues($ownerResponse['body'], $field);
    }

    public function testRegistrationCompleteMutationSerializesDuplicateCredentialConflict(): void
    {
        $user = $this->createUserWithPassword();
        $scenario = $this->createRegistrationScenario();
        $this->interceptDuplicateRegistrationCredential($user, $scenario);

        $response = $this->executeRegistrationCompleteForUser($user, $scenario);

        $this->assertGraphQlErrorsContain(
            $response['body'],
            'Passkey credential is already registered.'
        );
        $this->assertNoAuthResultPayloadValues(
            $response['body'],
            'passkeyRegistrationCompleteUser'
        );
    }

    private function createSignUpChallengeId(): string
    {
        $options = $this->executePasskeyMutation(
            'passkeySignUpOptionsUser',
            'passkeySignUpOptionsUserInput',
            'challengeId',
            [
                'email' => $this->faker->unique()->safeEmail(),
                'initials' => strtoupper($this->faker->lexify('??')),
            ]
        );

        return $this->requireStringField(
            $this->requireUserPayload($options['body'], 'passkeySignUpOptionsUser'),
            'challengeId'
        );
    }

    /**
     * @return GraphQlResponse
     */
    private function executeSignUpCompleteWithInvalidCredential(string $challengeId): array
    {
        return $this->executePasskeyMutation(
            'passkeySignUpCompleteUser',
            'passkeySignUpCompleteUserInput',
            'success accessToken refreshToken',
            [
                'challengeId' => $challengeId,
                'credential' => $this->createCredentialInput(),
                'label' => $this->faker->words(2, true),
            ]
        );
    }

    /**
     * @return array{challengeId: string, credential: CredentialInput}
     */
    private function createSignInCompleteInputFromOptions(): array
    {
        $options = $this->executeSignInOptionsMutation($this->faker->unique()->safeEmail());

        return [
            'challengeId' => $this->requireStringField(
                $this->requireUserPayload($options['body'], 'passkeySignInOptionsUser'),
                'challengeId'
            ),
            'credential' => $this->createCredentialInput(),
        ];
    }

    /**
     * @param array{challengeId: string, credential: CredentialInput} $input
     *
     * @return GraphQlResponse
     */
    private function executeSignInCompleteMutation(array $input): array
    {
        return $this->executePasskeyMutation(
            'passkeySignInCompleteUser',
            'passkeySignInCompleteUserInput',
            'success accessToken refreshToken',
            $input
        );
    }

    private function createRegistrationChallengeId(User $owner): string
    {
        $options = $this->executePasskeyMutation(
            'passkeyRegistrationOptionsUser',
            'passkeyRegistrationOptionsUserInput',
            'challengeId',
            [],
            $this->createAuthenticatedHeaders($owner->getId())
        );

        return $this->requireStringField(
            $this->requireUserPayload($options['body'], 'passkeyRegistrationOptionsUser'),
            'challengeId'
        );
    }

    /**
     * @return RegistrationScenario
     */
    private function createRegistrationScenario(): array
    {
        return [
            'challengeId' => $this->faker->uuid(),
            'credential' => $this->createCredentialInput(),
            'label' => $this->faker->words(2, true),
        ];
    }

    /**
     * @param RegistrationScenario $scenario
     */
    private function interceptDuplicateRegistrationCredential(User $user, array $scenario): void
    {
        $this->commandBus->interceptWith(
            function (
                CommandInterface $command
            ) use ($scenario, $user): ?CommandResponseInterface {
                $this->assertInstanceOf(CompletePasskeyRegistrationCommand::class, $command);
                $this->assertSame($scenario['challengeId'], $command->challengeId);
                $this->assertSame($scenario['credential'], $command->credential);
                $this->assertSame($scenario['label'], $command->label);
                $this->assertSame($user->getId(), $command->currentUserId);

                throw new ConflictHttpException('Passkey credential is already registered.');
            }
        );
    }

    /**
     * @param RegistrationScenario $scenario
     *
     * @return GraphQlResponse
     */
    private function executeRegistrationCompleteForUser(User $user, array $scenario): array
    {
        return $this->executePasskeyMutation(
            'passkeyRegistrationCompleteUser',
            'passkeyRegistrationCompleteUserInput',
            'success credentialId',
            [
                'challengeId' => $scenario['challengeId'],
                'credential' => $scenario['credential'],
                'label' => $scenario['label'],
            ],
            $this->createAuthenticatedHeaders($user->getId())
        );
    }
}
