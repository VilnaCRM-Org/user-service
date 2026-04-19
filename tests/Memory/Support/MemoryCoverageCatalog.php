<?php

declare(strict_types=1);

namespace App\Tests\Memory\Support;

use function array_merge;
use function array_unique;
use function array_values;

final class MemoryCoverageCatalog
{
    private const REST_SCENARIOS = [
        [
            'id' => 'cross_cutting_auth_guards',
            'loadScripts' => ['signin'],
            'features' => [
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
            ],
        ],
        [
            'id' => 'health_check',
            'loadScripts' => ['apiDocs', 'health'],
            'features' => ['health_check'],
        ],
        [
            'id' => 'api_platform_surface',
            'loadScripts' => [
                'apiContextUser',
                'apiEntrypoint',
                'apiErrors400',
                'apiValidationErrors',
                'apiWellKnownGenid',
            ],
            'features' => ['error_format', 'security_headers'],
        ],
        [
            'id' => 'user_registration_and_confirmation',
            'loadScripts' => ['createUser', 'confirmUser'],
            'features' => ['user_operations'],
        ],
        [
            'id' => 'user_batch_creation',
            'loadScripts' => ['createUserBatch'],
            'features' => ['user_operations'],
        ],
        [
            'id' => 'user_query_and_cache',
            'loadScripts' => ['cachePerformance', 'getUser', 'getUsers'],
            'features' => ['user_localization', 'user_operations'],
        ],
        [
            'id' => 'user_mutation_and_resend',
            'loadScripts' => [
                'deleteUser',
                'replaceUser',
                'resendEmailToUser',
                'updateUser',
            ],
            'features' => ['user_localization', 'user_operations'],
        ],
        [
            'id' => 'session_lifecycle',
            'loadScripts' => ['refreshToken', 'signout', 'signoutAll'],
            'features' => [
                'password_reset_auth_integration',
                'session_lifecycle',
                'token_refresh',
            ],
        ],
        [
            'id' => 'two_factor_lifecycle',
            'loadScripts' => [
                'confirmTwoFactor',
                'disableTwoFactor',
                'regenerateRecoveryCodes',
                'setupTwoFactor',
                'signinTwoFactor',
            ],
            'features' => [
                'signin_story_2_1',
                'signin_story_2_2',
                'two_factor_auth',
            ],
        ],
        [
            'id' => 'password_reset_lifecycle',
            'loadScripts' => ['resetPassword', 'resetPasswordConfirm'],
            'features' => ['password_reset', 'password_reset_auth_integration'],
        ],
    ];

    private const GRAPH_QL_SCENARIOS = [
        [
            'id' => 'auth_session_lifecycle',
            'loadScripts' => [
                'graphQLRefreshToken',
                'graphQLSignin',
                'graphQLSignout',
                'graphQLSignoutAll',
            ],
            'features' => ['graphql_authentication', 'token_refresh'],
        ],
        [
            'id' => 'complete_two_factor',
            'loadScripts' => ['graphQLCompleteTwoFactor'],
            'features' => ['graphql_authentication'],
        ],
        [
            'id' => 'two_factor_lifecycle',
            'loadScripts' => [
                'graphQLConfirmTwoFactor',
                'graphQLDisableTwoFactor',
                'graphQLRegenerateRecoveryCodes',
                'graphQLSetupTwoFactor',
            ],
            'features' => ['graphql_authentication', 'two_factor_auth'],
        ],
        [
            'id' => 'user_lifecycle',
            'loadScripts' => [
                'graphQLConfirmUser',
                'graphQLCreateUser',
                'graphQLDeleteUser',
                'graphQLGetUser',
                'graphQLGetUsers',
                'graphQLResendEmailToUser',
                'graphQLUpdateUser',
            ],
            'features' => [
                'graphql_authentication',
                'user_graphql_localization',
                'user_graphql_operations',
            ],
        ],
        [
            'id' => 'password_reset_lifecycle',
            'loadScripts' => [
                'graphQLConfirmPasswordReset',
                'graphQLRequestPasswordReset',
            ],
            'features' => ['graphql_password_reset'],
        ],
        [
            'id' => 'hardening',
            'loadScripts' => [],
            'features' => ['graphql_authentication'],
        ],
    ];

    private const OAUTH_SCENARIOS = [
        [
            'id' => 'client_credentials',
            'loadScripts' => ['oauth', 'oauthAuthorize'],
            'features' => ['oauth'],
        ],
        [
            'id' => 'social_lifecycle',
            'loadScripts' => ['oauthSocialCallback', 'oauthSocialInitiate'],
            'features' => ['oauth_social'],
        ],
    ];

    /**
     * @return list<array{id: string, loadScripts: list<string>, features: list<string>}>
     */
    public static function restScenarios(): array
    {
        return self::REST_SCENARIOS;
    }

    /**
     * @return list<array{id: string, loadScripts: list<string>, features: list<string>}>
     */
    public static function graphQlScenarios(): array
    {
        return self::GRAPH_QL_SCENARIOS;
    }

    /**
     * @return list<array{id: string, loadScripts: list<string>, features: list<string>}>
     */
    public static function oauthScenarios(): array
    {
        return self::OAUTH_SCENARIOS;
    }

    /**
     * @return list<string>
     */
    public static function coveredRestLoadScripts(): array
    {
        return self::flattenScripts(self::restScenarios());
    }

    /**
     * @return list<string>
     */
    public static function coveredGraphQlLoadScripts(): array
    {
        return self::flattenScripts(self::graphQlScenarios());
    }

    /**
     * @return list<string>
     */
    public static function coveredOAuthLoadScripts(): array
    {
        return self::flattenScripts(self::oauthScenarios());
    }

    /**
     * @return list<string>
     */
    public static function coveredFeatureFiles(): array
    {
        return self::flattenFeatures(
            array_merge(
                self::restScenarios(),
                self::graphQlScenarios(),
                self::oauthScenarios(),
            ),
        );
    }

    /**
     * @param list<array{id: string, loadScripts: list<string>, features: list<string>}> $scenarios
     *
     * @return list<string>
     */
    private static function flattenScripts(array $scenarios): array
    {
        $scripts = [];
        foreach ($scenarios as $scenario) {
            $scripts = array_merge($scripts, $scenario['loadScripts']);
        }

        return array_values(array_unique($scripts));
    }

    /**
     * @param list<array{id: string, loadScripts: list<string>, features: list<string>}> $scenarios
     *
     * @return list<string>
     */
    private static function flattenFeatures(array $scenarios): array
    {
        $features = [];
        foreach ($scenarios as $scenario) {
            $features = array_merge($features, $scenario['features']);
        }

        return array_values(array_unique($features));
    }
}
