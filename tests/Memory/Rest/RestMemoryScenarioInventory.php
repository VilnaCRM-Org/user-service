<?php

declare(strict_types=1);

namespace App\Tests\Memory\Rest;

final class RestMemoryScenarioInventory
{
    public const COVERED_LOAD_SCENARIOS = [
        'apiContextUser' => [
            'class' => PublicApiSurfaceMemoryTest::class,
            'method' => 'testApiPlatformSurfaceScenariosStayStableAcrossRepeatedSameKernelRequests',
        ],
        'apiDocs' => [
            'class' => PublicApiSurfaceMemoryTest::class,
            'method' => 'testApiPlatformSurfaceScenariosStayStableAcrossRepeatedSameKernelRequests',
        ],
        'apiEntrypoint' => [
            'class' => PublicApiSurfaceMemoryTest::class,
            'method' => 'testApiPlatformSurfaceScenariosStayStableAcrossRepeatedSameKernelRequests',
        ],
        'apiErrors400' => [
            'class' => PublicApiSurfaceMemoryTest::class,
            'method' => 'testApiPlatformSurfaceScenariosStayStableAcrossRepeatedSameKernelRequests',
        ],
        'apiValidationErrors' => [
            'class' => PublicApiSurfaceMemoryTest::class,
            'method' => 'testApiPlatformSurfaceScenariosStayStableAcrossRepeatedSameKernelRequests',
        ],
        'apiWellKnownGenid' => [
            'class' => PublicApiSurfaceMemoryTest::class,
            'method' => 'testApiPlatformSurfaceScenariosStayStableAcrossRepeatedSameKernelRequests',
        ],
        'cachePerformance' => [
            'class' => PublicReadMemoryTest::class,
            'method' => 'testCachePerformanceScenarioReusesSameKernelAcrossRepeatedRequests',
        ],
        'confirmTwoFactor' => [
            'class' => TwoFactorMemoryTest::class,
            'method' => 'testConfirmTwoFactorScenarioReusesSameKernelAcrossRepeatedRequests',
        ],
        'confirmUser' => [
            'class' => UserLifecycleMemoryTest::class,
            'method' => 'testConfirmUserScenarioReusesSameKernelAcrossRepeatedRequests',
        ],
        'createUser' => [
            'class' => UserLifecycleMemoryTest::class,
            'method' => 'testCreateUserScenarioReusesSameKernelAcrossRepeatedRequests',
        ],
        'createUserBatch' => [
            'class' => UserLifecycleMemoryTest::class,
            'method' => 'testCreateUserBatchScenarioReusesSameKernelAcrossRepeatedRequests',
        ],
        'deleteUser' => [
            'class' => UserLifecycleMemoryTest::class,
            'method' => 'testDeleteUserScenarioReusesSameKernelAcrossRepeatedRequests',
        ],
        'disableTwoFactor' => [
            'class' => TwoFactorMemoryTest::class,
            'method' => 'testDisableTwoFactorScenarioReusesSameKernelAcrossRepeatedRequests',
        ],
        'getUser' => [
            'class' => PublicReadMemoryTest::class,
            'method' => 'testGetUserScenarioReusesSameKernelAcrossRepeatedRequests',
        ],
        'getUsers' => [
            'class' => UserLifecycleMemoryTest::class,
            'method' => 'testGetUsersScenarioReusesSameKernelAcrossRepeatedRequests',
        ],
        'health' => [
            'class' => PublicReadMemoryTest::class,
            'method' => 'testHealthScenarioReusesSameKernelAcrossRepeatedRequests',
        ],
        'oauth' => [
            'class' => OAuthClientCredentialsMemoryTest::class,
            'method' => 'testOauthClientCredentialsScenarioReusesSameKernelAcrossRepeatedRequests',
        ],
        'oauthAuthorize' => [
            'class' => PublicApiSurfaceMemoryTest::class,
            'method' => 'testApiPlatformSurfaceScenariosStayStableAcrossRepeatedSameKernelRequests',
        ],
        'refreshToken' => [
            'class' => AuthTokenMemoryTest::class,
            'method' => 'testRefreshTokenScenarioReusesSameKernelAcrossRepeatedRequests',
        ],
        'regenerateRecoveryCodes' => [
            'class' => TwoFactorMemoryTest::class,
            'method' => 'testRegenerateRecoveryCodesScenarioReusesKernelAcrossRepeatedRequests',
        ],
        'replaceUser' => [
            'class' => UserLifecycleMemoryTest::class,
            'method' => 'testReplaceUserScenarioReusesSameKernelAcrossRepeatedRequests',
        ],
        'resendEmailToUser' => [
            'class' => UserLifecycleMemoryTest::class,
            'method' => 'testResendEmailToUserScenarioReusesSameKernelAcrossRepeatedRequests',
        ],
        'resetPassword' => [
            'class' => PasswordResetMemoryTest::class,
            'method' => 'testResetPasswordScenarioReusesSameKernelAcrossRepeatedRequests',
        ],
        'resetPasswordConfirm' => [
            'class' => PasswordResetMemoryTest::class,
            'method' => 'testResetPasswordConfirmScenarioReusesSameKernelAcrossRepeatedRequests',
        ],
        'setupTwoFactor' => [
            'class' => TwoFactorMemoryTest::class,
            'method' => 'testSetupTwoFactorScenarioReusesSameKernelAcrossRepeatedRequests',
        ],
        'signin' => [
            'class' => AuthTokenMemoryTest::class,
            'method' => 'testSigninScenarioReusesSameKernelAcrossRepeatedRequests',
        ],
        'signinTwoFactor' => [
            'class' => TwoFactorMemoryTest::class,
            'method' => 'testSigninTwoFactorScenarioReusesSameKernelAcrossRepeatedRequests',
        ],
        'signout' => [
            'class' => AuthTokenMemoryTest::class,
            'method' => 'testSignoutScenarioReusesSameKernelAcrossRepeatedRequests',
        ],
        'signoutAll' => [
            'class' => AuthTokenMemoryTest::class,
            'method' => 'testSignoutAllScenarioReusesSameKernelAcrossRepeatedRequests',
        ],
        'updateUser' => [
            'class' => UserLifecycleMemoryTest::class,
            'method' => 'testUpdateUserScenarioReusesSameKernelAcrossRepeatedRequests',
        ],
    ];

    public const DEFERRED_LOAD_SCENARIOS = [
        'oauthSocialCallback',
        'oauthSocialInitiate',
    ];
}
