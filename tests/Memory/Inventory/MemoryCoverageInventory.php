<?php

declare(strict_types=1);

namespace App\Tests\Memory\Inventory;

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

    public const IMPLEMENTED_MEMORY_TESTS = [
        'feature:health_check' => [
            'class' => MemoryLeakToolingSmokeTest::class,
            'method' => 'testHealthEndpointDoesNotRetainKernelState',
        ],
        'rest:health' => [
            'class' => MemoryLeakToolingSmokeTest::class,
            'method' => 'testHealthEndpointDoesNotRetainKernelState',
        ],
    ];
}
