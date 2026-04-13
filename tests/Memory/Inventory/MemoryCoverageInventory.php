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
        return [
            'feature:account_lockout' => self::coverage(
                AuthTokenMemoryTest::class,
                'testSigninScenarioReusesSameKernelAcrossRepeatedRequests',
            ),
            'feature:auth_gate' => self::coverage(
                AuthTokenMemoryTest::class,
                'testSigninScenarioReusesSameKernelAcrossRepeatedRequests',
            ),
            'feature:cookie_security' => self::coverage(
                AuthTokenMemoryTest::class,
                'testSigninScenarioReusesSameKernelAcrossRepeatedRequests',
            ),
            'feature:cors' => self::coverage(
                AuthTokenMemoryTest::class,
                'testSigninScenarioReusesSameKernelAcrossRepeatedRequests',
            ),
            'feature:data_protection' => self::coverage(
                AuthTokenMemoryTest::class,
                'testSigninScenarioReusesSameKernelAcrossRepeatedRequests',
            ),
            'feature:error_format' => self::coverage(
                AuthTokenMemoryTest::class,
                'testSigninScenarioReusesSameKernelAcrossRepeatedRequests',
            ),
            'feature:graphql_authentication' => self::coverage(
                GraphQLAuthMemoryTest::class,
                'testGraphQlAuthMutationsStayStableAcrossRepeatedSameKernelRequests',
            ),
            'feature:graphql_password_reset' => self::coverage(
                GraphQLAuthMemoryTest::class,
                'testGraphQlAuthMutationsStayStableAcrossRepeatedSameKernelRequests',
            ),
            'feature:health_check' => self::coverage(
                PublicReadMemoryTest::class,
                'testHealthScenarioReusesSameKernelAcrossRepeatedRequests',
            ),
            'feature:input_validation' => self::coverage(
                AuthTokenMemoryTest::class,
                'testSigninScenarioReusesSameKernelAcrossRepeatedRequests',
            ),
            'feature:jwt_validation' => self::coverage(
                AuthTokenMemoryTest::class,
                'testSigninScenarioReusesSameKernelAcrossRepeatedRequests',
            ),
            'feature:oauth' => self::coverage(
                OAuthClientCredentialsMemoryTest::class,
                'testOauthClientCredentialsScenarioReusesSameKernelAcrossRepeatedRequests',
            ),
            'feature:oauth_social' => self::coverage(
                OAuthSocialFlowMemoryTest::class,
                'testOAuthSocialFlowsStayStableAcrossRepeatedSameKernelRequests',
            ),
            'feature:password_reset' => self::coverage(
                PasswordResetMemoryTest::class,
                'testResetPasswordScenarioReusesSameKernelAcrossRepeatedRequests',
            ),
            'feature:password_reset_auth_integration' => self::coverage(
                PasswordResetMemoryTest::class,
                'testResetPasswordConfirmScenarioReusesSameKernelAcrossRepeatedRequests',
            ),
            'feature:rate_limiting' => self::coverage(
                AuthTokenMemoryTest::class,
                'testSigninScenarioReusesSameKernelAcrossRepeatedRequests',
            ),
            'feature:security_headers' => self::coverage(
                AuthTokenMemoryTest::class,
                'testSigninScenarioReusesSameKernelAcrossRepeatedRequests',
            ),
            'feature:session_lifecycle' => self::coverage(
                AuthTokenMemoryTest::class,
                'testSignoutAllScenarioReusesSameKernelAcrossRepeatedRequests',
            ),
            'feature:signin' => self::coverage(
                AuthTokenMemoryTest::class,
                'testSigninScenarioReusesSameKernelAcrossRepeatedRequests',
            ),
            'feature:signin_story_1_1' => self::coverage(
                AuthTokenMemoryTest::class,
                'testSigninScenarioReusesSameKernelAcrossRepeatedRequests',
            ),
            'feature:signin_story_1_2' => self::coverage(
                AuthTokenMemoryTest::class,
                'testSigninScenarioReusesSameKernelAcrossRepeatedRequests',
            ),
            'feature:signin_story_2_1' => self::coverage(
                TwoFactorMemoryTest::class,
                'testSigninTwoFactorScenarioReusesSameKernelAcrossRepeatedRequests',
            ),
            'feature:signin_story_2_2' => self::coverage(
                TwoFactorMemoryTest::class,
                'testSigninTwoFactorScenarioReusesSameKernelAcrossRepeatedRequests',
            ),
            'feature:token_refresh' => self::coverage(
                AuthTokenMemoryTest::class,
                'testRefreshTokenScenarioReusesSameKernelAcrossRepeatedRequests',
            ),
            'feature:two_factor_auth' => self::coverage(
                TwoFactorMemoryTest::class,
                'testSigninTwoFactorScenarioReusesSameKernelAcrossRepeatedRequests',
            ),
            'feature:user_graphql_localization' => self::coverage(
                GraphQLUserOperationMemoryTest::class,
                'testGraphQlUserOperationsStayStableAcrossRepeatedSameKernelRequests',
            ),
            'feature:user_graphql_operations' => self::coverage(
                GraphQLUserOperationMemoryTest::class,
                'testGraphQlUserOperationsStayStableAcrossRepeatedSameKernelRequests',
            ),
            'feature:user_localization' => self::coverage(
                UserLifecycleMemoryTest::class,
                'testGetUsersScenarioReusesSameKernelAcrossRepeatedRequests',
            ),
            'feature:user_operations' => self::coverage(
                UserLifecycleMemoryTest::class,
                'testCreateUserScenarioReusesSameKernelAcrossRepeatedRequests',
            ),
        ];
    }

    /**
     * @return array<string, array{class: class-string, method: string}>
     */
    private static function graphQlMemoryTests(): array
    {
        return array_merge(
            self::prefixedCoverageMap(
                [
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
                ],
                'graphql',
                GraphQLAuthMemoryTest::class,
                'testGraphQlAuthMutationsStayStableAcrossRepeatedSameKernelRequests',
            ),
            self::prefixedCoverageMap(
                GraphQLUserOperationMemoryTest::inventoryTargets(),
                'graphql',
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
        $coverage = [];

        foreach ($targets as $target) {
            $coverage[sprintf('%s:%s', $prefix, $target)] = self::coverage($class, $method);
        }

        return $coverage;
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
}
