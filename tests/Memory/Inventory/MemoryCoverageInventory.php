<?php

declare(strict_types=1);

namespace App\Tests\Memory\Inventory;

use App\Tests\Memory\GraphQL\GraphQLAuthMemoryTest;
use App\Tests\Memory\GraphQL\GraphQLUserOperationMemoryTest;
use App\Tests\Memory\OAuth\OAuthSocialFlowMemoryTest;
use App\Tests\Memory\Rest\AuthTokenMemoryTest;
use App\Tests\Memory\Rest\OAuthClientCredentialsMemoryTest;
use App\Tests\Memory\Rest\PasswordResetMemoryTest;
use App\Tests\Memory\Rest\PublicReadMemoryTest;
use App\Tests\Memory\Rest\RestMemoryScenarioInventory;
use App\Tests\Memory\Rest\TwoFactorMemoryTest;
use App\Tests\Memory\Rest\UserLifecycleMemoryTest;

final class MemoryCoverageInventory
{
    public const INVENTORY_COVERAGE_THRESHOLD = 100;

    public const REST_LOAD_SCENARIOS = [
        'apiContextUser',
        'apiDocs',
        'apiEntrypoint',
        'apiErrors400',
        'apiValidationErrors',
        'apiWellKnownGenid',
        'cachePerformance',
        'confirmTwoFactor',
        'confirmUser',
        'createUser',
        'createUserBatch',
        'deleteUser',
        'disableTwoFactor',
        'getUser',
        'getUsers',
        'health',
        'oauth',
        'oauthAuthorize',
        'oauthSocialCallback',
        'oauthSocialInitiate',
        'refreshToken',
        'regenerateRecoveryCodes',
        'replaceUser',
        'resendEmailToUser',
        'resetPassword',
        'resetPasswordConfirm',
        'setupTwoFactor',
        'signin',
        'signinTwoFactor',
        'signout',
        'signoutAll',
        'updateUser',
    ];

    public const GRAPHQL_LOAD_SCENARIOS = [
        'graphQLCompleteTwoFactor',
        'graphQLConfirmPasswordReset',
        'graphQLConfirmTwoFactor',
        'graphQLConfirmUser',
        'graphQLCreateUser',
        'graphQLDeleteUser',
        'graphQLDisableTwoFactor',
        'graphQLGetUser',
        'graphQLGetUsers',
        'graphQLRefreshToken',
        'graphQLRegenerateRecoveryCodes',
        'graphQLRequestPasswordReset',
        'graphQLResendEmailToUser',
        'graphQLSetupTwoFactor',
        'graphQLSignin',
        'graphQLSignout',
        'graphQLSignoutAll',
        'graphQLUpdateUser',
    ];

    public const BEHAT_FEATURES = [
        'account_lockout',
        'auth_gate',
        'cookie_security',
        'cors',
        'data_protection',
        'error_format',
        'graphql_authentication',
        'graphql_password_reset',
        'health_check',
        'input_validation',
        'jwt_validation',
        'oauth',
        'oauth_social',
        'password_reset',
        'password_reset_auth_integration',
        'rate_limiting',
        'security_headers',
        'session_lifecycle',
        'signin',
        'signin_story_1_1',
        'signin_story_1_2',
        'signin_story_2_1',
        'signin_story_2_2',
        'token_refresh',
        'two_factor_auth',
        'user_graphql_localization',
        'user_graphql_operations',
        'user_localization',
        'user_operations',
    ];

    private const GRAPHQL_AUTH_LOAD_SCENARIOS = [
        'graphQLCompleteTwoFactor',
        'graphQLConfirmPasswordReset',
        'graphQLConfirmTwoFactor',
        'graphQLDisableTwoFactor',
        'graphQLRefreshToken',
        'graphQLRegenerateRecoveryCodes',
        'graphQLRequestPasswordReset',
        'graphQLSetupTwoFactor',
        'graphQLSignin',
        'graphQLSignout',
        'graphQLSignoutAll',
    ];

    private const SIGNIN_AUTH_FEATURES = [
        'account_lockout',
        'auth_gate',
        'cookie_security',
        'cors',
        'data_protection',
        'error_format',
        'input_validation',
        'jwt_validation',
        'rate_limiting',
        'security_headers',
        'signin',
        'signin_story_1_1',
        'signin_story_1_2',
    ];

    private const SIGNIN_TWO_FACTOR_FEATURES = [
        'signin_story_2_1',
        'signin_story_2_2',
        'two_factor_auth',
    ];

    /**
     * @return list<string>
     */
    public static function baselineMemoryTests(): array
    {
        $inventory = [];

        foreach (self::BEHAT_FEATURES as $feature) {
            $inventory[] = sprintf('feature:%s', $feature);
        }

        foreach (self::GRAPHQL_LOAD_SCENARIOS as $scenario) {
            $inventory[] = sprintf('graphql:%s', $scenario);
        }

        foreach (self::REST_LOAD_SCENARIOS as $scenario) {
            $inventory[] = sprintf('rest:%s', $scenario);
        }

        sort($inventory);

        return $inventory;
    }

    /**
     * @return array<string, array{class: class-string, method: string}>
     */
    public static function implementedMemoryTests(): array
    {
        $implemented = array_merge(
            self::featureMemoryTests(),
            self::graphQlMemoryTests(),
            self::restMemoryTests(),
        );

        ksort($implemented);

        return $implemented;
    }

    /**
     * @return array<string, array{class: class-string, method: string}>
     */
    private static function featureMemoryTests(): array
    {
        return array_merge(
            self::signinFeatureMemoryTests(),
            self::graphqlFeatureMemoryTests(),
            self::publicFeatureMemoryTests(),
            self::oauthFeatureMemoryTests(),
            self::passwordResetFeatureMemoryTests(),
            self::userFeatureMemoryTests(),
        );
    }

    /**
     * @return array<string, array{class: class-string, method: string}>
     */
    private static function graphQlMemoryTests(): array
    {
        return array_merge(
            self::graphQlCoverage(
                self::GRAPHQL_AUTH_LOAD_SCENARIOS,
                GraphQLAuthMemoryTest::class,
                'testGraphQlAuthMutationsStayStableAcrossRepeatedSameKernelRequests',
            ),
            self::graphQlCoverage(
                GraphQLUserOperationMemoryTest::inventoryTargets(),
                GraphQLUserOperationMemoryTest::class,
                'testGraphQlUserOperationsStayStableAcrossRepeatedSameKernelRequests',
            ),
        );
    }

    /**
     * @return array<string, array{class: class-string, method: string}>
     */
    private static function restMemoryTests(): array
    {
        $implemented = [];

        foreach (RestMemoryScenarioInventory::COVERED_LOAD_SCENARIOS as $scenario => $coverage) {
            $implemented[sprintf('rest:%s', $scenario)] = $coverage;
        }

        foreach (RestMemoryScenarioInventory::DEFERRED_LOAD_SCENARIOS as $scenario) {
            $implemented[sprintf('rest:%s', $scenario)] = self::coverage(
                OAuthSocialFlowMemoryTest::class,
                'testOAuthSocialFlowsStayStableAcrossRepeatedSameKernelRequests',
            );
        }

        return $implemented;
    }

    /**
     * @param list<string> $targets
     *
     * @return array<string, array{class: class-string, method: string}>
     */
    private static function prefixedCoverageMap(
        array $targets,
        string $prefix,
        string $class,
        string $method,
    ): array {
        return array_combine(
            array_map(
                static fn (string $target): string => sprintf('%s:%s', $prefix, $target),
                $targets,
            ),
            array_fill(0, count($targets), self::coverage($class, $method)),
        );
    }

    /**
     * @return array{class: class-string, method: string}
     */
    private static function coverage(string $class, string $method): array
    {
        return [
            'class' => $class,
            'method' => $method,
        ];
    }

    /**
     * @return array<string, array{class: class-string, method: string}>
     */
    private static function graphqlFeatureMemoryTests(): array
    {
        return self::featureCoverage(
            [
                'graphql_authentication',
                'graphql_password_reset',
            ],
            GraphQLAuthMemoryTest::class,
            'testGraphQlAuthMutationsStayStableAcrossRepeatedSameKernelRequests',
        ) + self::featureCoverage(
            [
                'user_graphql_localization',
                'user_graphql_operations',
            ],
            GraphQLUserOperationMemoryTest::class,
            'testGraphQlUserOperationsStayStableAcrossRepeatedSameKernelRequests',
        );
    }

    /**
     * @return array<string, array{class: class-string, method: string}>
     */
    private static function oauthFeatureMemoryTests(): array
    {
        return self::featureCoverage(
            ['oauth'],
            OAuthClientCredentialsMemoryTest::class,
            'testOauthClientCredentialsScenarioReusesSameKernelAcrossRepeatedRequests',
        ) + self::featureCoverage(
            ['oauth_social'],
            OAuthSocialFlowMemoryTest::class,
            'testOAuthSocialFlowsStayStableAcrossRepeatedSameKernelRequests',
        );
    }

    /**
     * @return array<string, array{class: class-string, method: string}>
     */
    private static function passwordResetFeatureMemoryTests(): array
    {
        return self::featureCoverage(
            [
                'password_reset',
                'password_reset_auth_integration',
            ],
            PasswordResetMemoryTest::class,
            'testResetPasswordScenarioReusesSameKernelAcrossRepeatedRequests',
        );
    }

    /**
     * @return array<string, array{class: class-string, method: string}>
     */
    private static function publicFeatureMemoryTests(): array
    {
        return self::featureCoverage(
            ['health_check'],
            PublicReadMemoryTest::class,
            'testHealthScenarioReusesSameKernelAcrossRepeatedRequests',
        );
    }

    /**
     * @return array<string, array{class: class-string, method: string}>
     */
    private static function signinFeatureMemoryTests(): array
    {
        return self::featureCoverage(
            self::SIGNIN_AUTH_FEATURES,
            AuthTokenMemoryTest::class,
            'testSigninScenarioReusesSameKernelAcrossRepeatedRequests',
        ) + self::featureCoverage(
            ['session_lifecycle'],
            AuthTokenMemoryTest::class,
            'testSignoutAllScenarioReusesSameKernelAcrossRepeatedRequests',
        ) + self::featureCoverage(
            ['token_refresh'],
            AuthTokenMemoryTest::class,
            'testRefreshTokenScenarioReusesSameKernelAcrossRepeatedRequests',
        ) + self::featureCoverage(
            self::SIGNIN_TWO_FACTOR_FEATURES,
            TwoFactorMemoryTest::class,
            'testSigninTwoFactorScenarioReusesSameKernelAcrossRepeatedRequests',
        );
    }

    /**
     * @return array<string, array{class: class-string, method: string}>
     */
    private static function userFeatureMemoryTests(): array
    {
        return self::featureCoverage(
            ['user_localization'],
            UserLifecycleMemoryTest::class,
            'testGetUsersScenarioReusesSameKernelAcrossRepeatedRequests',
        ) + self::featureCoverage(
            ['user_operations'],
            UserLifecycleMemoryTest::class,
            'testCreateUserScenarioReusesSameKernelAcrossRepeatedRequests',
        );
    }

    /**
     * @param list<string> $targets
     *
     * @return array<string, array{class: class-string, method: string}>
     */
    private static function featureCoverage(
        array $targets,
        string $class,
        string $method,
    ): array {
        return self::prefixedCoverageMap($targets, 'feature', $class, $method);
    }

    /**
     * @param list<string> $targets
     *
     * @return array<string, array{class: class-string, method: string}>
     */
    private static function graphQlCoverage(
        array $targets,
        string $class,
        string $method,
    ): array {
        return self::prefixedCoverageMap($targets, 'graphql', $class, $method);
    }
}
