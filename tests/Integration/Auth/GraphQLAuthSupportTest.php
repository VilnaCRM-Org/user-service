<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use App\User\Application\Resolver\CompleteTwoFactorAuthMutationResolver;
use App\User\Application\Resolver\ConfirmTwoFactorAuthMutationResolver;
use App\User\Application\Resolver\DisableTwoFactorAuthMutationResolver;
use App\User\Application\Resolver\PasskeyRegistrationCompleteAuthMutationResolver;
use App\User\Application\Resolver\PasskeyRegistrationOptionsAuthMutationResolver;
use App\User\Application\Resolver\PasskeySignInCompleteAuthMutationResolver;
use App\User\Application\Resolver\PasskeySignInOptionsAuthMutationResolver;
use App\User\Application\Resolver\PasskeySignUpCompleteAuthMutationResolver;
use App\User\Application\Resolver\PasskeySignUpOptionsAuthMutationResolver;
use App\User\Application\Resolver\RefreshTokenAuthMutationResolver;
use App\User\Application\Resolver\RegenerateRecoveryCodesAuthMutationResolver;
use App\User\Application\Resolver\SetupTwoFactorAuthMutationResolver;
use App\User\Application\Resolver\SignInAuthMutationResolver;
use App\User\Application\Resolver\SignOutAllAuthMutationResolver;
use App\User\Application\Resolver\SignOutAuthMutationResolver;
use Symfony\Component\Yaml\Yaml;

final class GraphQLAuthSupportTest extends AuthIntegrationTestCase
{
    private const AUTH_PAYLOAD_CONFIG_PATH =
        __DIR__ . '/../../../config/api_platform/resources/AuthPayload.yaml';
    private const GENERATED_GRAPHQL_SPEC_PATH = __DIR__ . '/../../../.github/graphql-spec/spec';
    private const PASSKEY_SIGN_UP_OPTIONS_DESCRIPTION =
        'Creates WebAuthn public key creation options for a new passkey-backed account.';
    private const PASSKEY_SIGN_IN_OPTIONS_DESCRIPTION =
        'Creates WebAuthn public key request options for passkey sign-in.';
    private const PASSKEY_SIGN_IN_COMPLETE_DESCRIPTION =
        'Verifies WebAuthn assertion and returns authentication data.';
    private const PASSKEY_REGISTRATION_OPTIONS_DESCRIPTION =
        'Creates WebAuthn public key creation options for the authenticated user.';
    private const PASSKEY_REGISTRATION_COMPLETE_DESCRIPTION =
        'Verifies WebAuthn attestation and stores a passkey for the authenticated user.';

    /**
     * @phpstan-type GraphQlOperation array<
     *     string,
     *     array<array-key, scalar|array<array-key, scalar>|null>|scalar|null
     * >
     */

    /**
     * AC: NFR-62 - Auth operations must be exposed through graphQlOperations.
     */
    public function testAuthOperationsAreConfiguredInAuthPayloadGraphQlOperations(): void
    {
        $this->assertFileExists(
            self::AUTH_PAYLOAD_CONFIG_PATH,
            'AuthPayload.yaml must exist'
        );

        $operations = $this->loadAuthPayloadGraphQlOperations();
        foreach ($this->getRequiredOperationNames() as $operationName) {
            $this->assertOperationIsConfigured($operations, $operationName);
        }
    }

    /**
     * AC: NFR-62 - Dedicated auth payload resource must define nested queries explicitly
     * so API Platform does not inject class-less defaults during metadata warmup.
     */
    public function testAuthPayloadDefinesNestedQueryOperationsExplicitly(): void
    {
        $operations = $this->loadAuthPayloadGraphQlOperations();

        $this->assertNestedOperationExists(
            $operations,
            'ApiPlatform\\Metadata\\GraphQl\\Query'
        );
        $this->assertNestedOperationExists(
            $operations,
            'ApiPlatform\\Metadata\\GraphQl\\QueryCollection'
        );
    }

    /**
     * AC: NFR-62 - Auth operations must return the dedicated AuthPayload DTO.
     */
    public function testAuthOperationsUseAuthPayloadOutput(): void
    {
        $operations = $this->loadAuthPayloadGraphQlOperations();

        foreach ($operations as $operation) {
            if (!in_array($operation['name'] ?? null, $this->getRequiredOperationNames(), true)) {
                continue;
            }

            $this->assertSame(
                'App\User\Application\DTO\AuthPayload',
                $operation['output'] ?? null,
                sprintf(
                    'GraphQL auth mutation "%s" must use AuthPayload output.',
                    $operation['name'] ?? 'unknown'
                )
            );
        }
    }

    /**
     * AC: NFR-62 - Auth operations must point to the dedicated GraphQL resolvers.
     */
    public function testAuthOperationsUseExpectedResolvers(): void
    {
        $operations = $this->loadAuthPayloadGraphQlOperations();

        foreach ($this->getExpectedResolversByOperation() as $operationName => $resolverClass) {
            $operation = $this->findOperation($operations, $operationName);

            $this->assertSame(
                $resolverClass,
                $operation['resolver'] ?? null,
                sprintf(
                    'GraphQL auth mutation "%s" must use resolver "%s".',
                    $operationName,
                    $resolverClass
                )
            );
        }
    }

    public function testPasskeyOperationsUseExplicitGraphQlDescriptions(): void
    {
        $operations = $this->loadAuthPayloadGraphQlOperations();

        foreach ($this->getPasskeyDescriptionsByOperation() as $operationName => $description) {
            $operation = $this->findOperation($operations, $operationName);

            $this->assertSame(
                $description,
                $operation['description'] ?? null,
                sprintf(
                    'GraphQL passkey mutation "%s" must define an explicit description.',
                    $operationName
                )
            );
        }
    }

    public function testGeneratedGraphQlSpecUsesPasskeyDescriptions(): void
    {
        $this->assertFileExists(
            self::GENERATED_GRAPHQL_SPEC_PATH,
            'Generated GraphQL specification must exist.'
        );

        $spec = file_get_contents(self::GENERATED_GRAPHQL_SPEC_PATH);
        if ($spec === false) {
            self::fail('Generated GraphQL specification must be readable.');
        }

        foreach ($this->getPasskeyDescriptionsByOperation() as $operationName => $description) {
            $this->assertGeneratedSpecContainsDescription($spec, $operationName, $description);
        }

        foreach (array_keys($this->getPasskeyDescriptionsByOperation()) as $operationName) {
            $this->assertGeneratedSpecDoesNotContainDefaultDescription($spec, $operationName);
        }
    }

    /**
     * @return array<string>
     */
    private function getRequiredOperationNames(): array
    {
        return [
            'signIn',
            'completeTwoFactor',
            'refreshToken',
            'setupTwoFactor',
            'confirmTwoFactor',
            'disableTwoFactor',
            'regenerateRecoveryCodes',
            'signOut',
            'signOutAll',
            'passkeySignUpOptions',
            'passkeySignUpComplete',
            'passkeySignInOptions',
            'passkeySignInComplete',
            'passkeyRegistrationOptions',
            'passkeyRegistrationComplete',
        ];
    }

    /**
     * @return array<string, class-string>
     */
    private function getExpectedResolversByOperation(): array
    {
        return [
            'signIn' => SignInAuthMutationResolver::class,
            'completeTwoFactor' => CompleteTwoFactorAuthMutationResolver::class,
            'refreshToken' => RefreshTokenAuthMutationResolver::class,
            'setupTwoFactor' => SetupTwoFactorAuthMutationResolver::class,
            'confirmTwoFactor' => ConfirmTwoFactorAuthMutationResolver::class,
            'disableTwoFactor' => DisableTwoFactorAuthMutationResolver::class,
            'regenerateRecoveryCodes' => RegenerateRecoveryCodesAuthMutationResolver::class,
            'signOut' => SignOutAuthMutationResolver::class,
            'signOutAll' => SignOutAllAuthMutationResolver::class,
            'passkeySignUpOptions' => PasskeySignUpOptionsAuthMutationResolver::class,
            'passkeySignUpComplete' => PasskeySignUpCompleteAuthMutationResolver::class,
            'passkeySignInOptions' => PasskeySignInOptionsAuthMutationResolver::class,
            'passkeySignInComplete' => PasskeySignInCompleteAuthMutationResolver::class,
            'passkeyRegistrationOptions' => PasskeyRegistrationOptionsAuthMutationResolver::class,
            'passkeyRegistrationComplete' => PasskeyRegistrationCompleteAuthMutationResolver::class,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getPasskeyDescriptionsByOperation(): array
    {
        $signUpCompleteDescription = sprintf(
            '%s%s',
            'Verifies WebAuthn attestation, creates the account, stores the passkey, ',
            'and returns authentication data.'
        );

        return [
            'passkeySignUpOptions' => self::PASSKEY_SIGN_UP_OPTIONS_DESCRIPTION,
            'passkeySignUpComplete' => $signUpCompleteDescription,
            'passkeySignInOptions' => self::PASSKEY_SIGN_IN_OPTIONS_DESCRIPTION,
            'passkeySignInComplete' => self::PASSKEY_SIGN_IN_COMPLETE_DESCRIPTION,
            'passkeyRegistrationOptions' => self::PASSKEY_REGISTRATION_OPTIONS_DESCRIPTION,
            'passkeyRegistrationComplete' => self::PASSKEY_REGISTRATION_COMPLETE_DESCRIPTION,
        ];
    }

    private function assertGeneratedSpecContainsDescription(
        string $spec,
        string $operationName,
        string $description
    ): void {
        $descriptionPattern = preg_quote($description, '/');
        $fieldPattern = preg_quote(sprintf('%sUser', $operationName), '/');

        $this->assertMatchesRegularExpression(
            sprintf('/"%s"\s+%s\(input:/', $descriptionPattern, $fieldPattern),
            $spec,
            sprintf(
                'Generated GraphQL spec must use the explicit "%s" description.',
                $operationName
            )
        );
    }

    private function assertGeneratedSpecDoesNotContainDefaultDescription(
        string $spec,
        string $operationName
    ): void {
        $this->assertStringNotContainsString(
            sprintf('%ss a AuthPayload.', ucfirst($operationName)),
            $spec
        );
    }

    /**
     * @return list<GraphQlOperation>
     */
    private function loadAuthPayloadGraphQlOperations(): array
    {
        $config = Yaml::parseFile(self::AUTH_PAYLOAD_CONFIG_PATH);
        $key = 'App\User\Application\DTO\AuthPayload';

        return $config['resources'][$key]['graphQlOperations'] ?? [];
    }

    /**
     * @param list<GraphQlOperation> $operations
     */
    private function assertOperationIsConfigured(
        array $operations,
        string $operationName
    ): void {
        foreach ($operations as $operation) {
            if (($operation['name'] ?? null) === $operationName) {
                return;
            }
        }

        self::fail(
            sprintf(
                'GraphQL auth mutation "%s" must be configured in AuthPayload.yaml.',
                $operationName
            )
        );
    }

    /**
     * @param list<GraphQlOperation> $operations
     */
    private function assertNestedOperationExists(
        array $operations,
        string $operationClass
    ): void {
        foreach ($operations as $operation) {
            if (($operation['class'] ?? null) !== $operationClass) {
                continue;
            }

            $this->assertTrue(
                (bool) ($operation['nested'] ?? false),
                sprintf(
                    'GraphQL operation "%s" must be configured as nested.',
                    $operationClass
                )
            );

            return;
        }

        self::fail(
            sprintf(
                'GraphQL operation "%s" must be configured in AuthPayload.yaml.',
                $operationClass
            )
        );
    }

    /**
     * @param list<GraphQlOperation> $operations
     *
     * @return array<string, array<array-key, scalar|array<array-key, scalar>|null>|scalar|null>
     */
    private function findOperation(array $operations, string $operationName): array
    {
        foreach ($operations as $operation) {
            if (($operation['name'] ?? null) === $operationName) {
                return $operation;
            }
        }

        self::fail(
            sprintf(
                'GraphQL auth mutation "%s" must be configured in AuthPayload.yaml.',
                $operationName
            )
        );
    }
}
