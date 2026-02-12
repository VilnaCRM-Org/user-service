<?php

declare(strict_types=1);

use NunoMaduro\PhpInsights\Domain\Insights\CyclomaticComplexityIsHigh;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenNormalClasses;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenTraits;
use PHP_CodeSniffer\Standards\Generic\Sniffs\CodeAnalysis\UselessOverridingMethodSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineLengthSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Formatting\SpaceAfterNotSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Strings\UnnecessaryStringConcatSniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousExceptionNamingSniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousInterfaceNamingSniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousTraitNamingSniff;
use SlevomatCodingStandard\Sniffs\Functions\FunctionLengthSniff;
use SlevomatCodingStandard\Sniffs\Functions\UnusedParameterSniff;
use SlevomatCodingStandard\Sniffs\Namespaces\UseSpacingSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\DisallowMixedTypeHintSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\ParameterTypeHintSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\ReturnTypeHintSniff;

return [
    'preset' => 'symfony',
    'ide' => 'phpstorm',
    'exclude' => [
        'vendor',
        'CLI/bats/php',
        'tests/Behat/UserContext/AuthenticatedUserContextTrait.php',
    ],
    'add' => [],
    'remove' => [
        UnusedParameterSniff::class,
        SuperfluousInterfaceNamingSniff::class,
        SuperfluousExceptionNamingSniff::class,
        SuperfluousTraitNamingSniff::class,
        SpaceAfterNotSniff::class,
        NunoMaduro\PhpInsights\Domain\Sniffs\ForbiddenSetterSniff::class,
        UseSpacingSniff::class,
        ForbiddenTraits::class,
    ],
    'config' => [
        ReturnTypeHintSniff::class => [
            'exclude' => [
                'src/User/Domain/Repository/UserRepositoryInterface',
            ],
        ],
        ParameterTypeHintSniff::class => [
            'exclude' => [
                'tests/Unit/Shared/Infrastructure/Bus/CallableFirstParameterExtractorTest',
                'tests/Unit/Shared/Infrastructure/Bus/Stub/HandlerWithoutTypeHint',
                'tests/Behat/HealthCheckContext/HealthCheckContext.php',
            ],
        ],
        DisallowMixedTypeHintSniff::class => [
            'exclude' => [
                'tests/Unit/Shared/Infrastructure/Bus/Event/Async/RecordingLogger.php',
            ],
        ],
        LineLengthSniff::class => [
            'exclude' => [
                'phpinsights',
                'tests/Behat/OAuthContext/OAuthContext.php',
                'src/User/Infrastructure/Repository/MariaDBPasswordResetTokenRepository.php',
                'tests/Integration/User/Infrastructure/Repository/CachePerformanceTest.php',
                'tests/Behat/UserContext/UserRequestContext.php',
                'tests/Behat/UserContext/UserResponseContext.php',
                'tests/Integration/Auth/DisablePasswordGrantIntegrationTest.php',
                'tests/Integration/User/Application/CommandHandler/SignInCommandHandlerIntegrationTest.php',
                'tests/Integration/User/Application/CommandHandler/UpdateUserCommandHandlerIntegrationTest.php',
                'tests/Unit/Shared/Auth/Factory/TestAccessTokenFactoryTest.php',
                'tests/Unit/User/Application/CommandHandler/CompleteTwoFactorCommandHandlerTest.php',
                'tests/Unit/User/Application/CommandHandler/RefreshTokenCommandHandlerTest.php',
                'tests/Unit/User/Application/CommandHandler/SetupTwoFactorCommandHandlerTest.php',
                'tests/Unit/User/Application/CommandHandler/SignInCommandHandlerTest.php',
                'tests/Unit/User/Application/Processor/CompleteTwoFactorProcessorTest.php',
                'tests/Unit/User/Application/Processor/RegenerateRecoveryCodesProcessorTest.php',
                'tests/Unit/User/Application/Processor/SignInProcessorTest.php',
                'tests/Unit/User/Application/Service/PasswordChangeSessionRevokerTest.php',
                'tests/Unit/User/Application/Service/UserUpdateApplierTest.php',
                'tests/Unit/User/Infrastructure/Service/RedisAccountLockoutServiceTest.php',
            ],
            'ignoreComments' => true,
            'lineLimit' => 100,
        ],
        ForbiddenNormalClasses::class => [
            'exclude' => [
                'src/Shared/Infrastructure/Bus/Command/InMemorySymfonyCommandBus',
                'src/Shared/Infrastructure/Bus/Event/InMemorySymfonyEventBus',
                'src/Shared/OpenApi/Factory/Response/DuplicateEmailFactory',
                'src/User/Domain/Entity/User',
            ],
        ],
        UnnecessaryStringConcatSniff::class => [
            'exclude' => [
                'src/Shared/Application/OpenApi/Factory/Response/UnsupportedTypeFactory',
                'src/User/Domain/Exception/DuplicateEmailException',
                'src/Shared/Application/OpenApi/Factory/Response/DuplicateEmailFactory',
                'src/Shared/Infrastructure/Bus/Command/CommandNotRegisteredException',
                'src/Shared/Infrastructure/Bus/Event/EventNotRegisteredException.php',
                'tests/Unit/Shared/Application/OpenApi/Factory/Response/OAuthRedirectResponseFactoryTest',
                'tests/Unit/Shared/Application/OpenApi/Factory/Response/UnsupportedGrantTypeResponseFactoryTest',
                'tests/Behat/OAuthContext/OAuthContext',
            ],
        ],
        CyclomaticComplexityIsHigh::class => [
            'exclude' => [
                'src/Shared/Application/Validator/InitialsValidator.php',
                'src/Shared/Application/Validator/PasswordValidator.php',
                'tests/Unit/User/Infrastructure/Repository/CachedUserRepositoryTest.php',
                'tests/Integration/User/Infrastructure/Repository/CachePerformanceTest.php',
            ],
        ],
        FunctionLengthSniff::class => [
            'exclude' => [
                'tests/Unit/User/Infrastructure/Repository/CachedUserRepositoryTest.php',
                'tests/Unit/User/Application/EventSubscriber/ConfirmationEmailSendEventSubscriberTest.php',
                'tests/Unit/User/Application/EventSubscriber/EmailChangedEventSubscriberTest.php',
                'tests/Unit/User/Application/EventSubscriber/UserRegisteredEventSubscriberTest.php',
                'tests/Behat/UserContext/AuthenticatedUserContextTrait.php',
                'tests/Behat/UserContext/UserRequestContext.php',
                'tests/Behat/UserContext/UserResponseContext.php',
                'tests/Behat/UserGraphQLContext/UserGraphQLResponseContext.php',
                'tests/Integration/Auth/ApiRateLimitListenerIntegrationTest.php',
                'tests/Integration/Auth/DisablePasswordGrantIntegrationTest.php',
                'tests/Integration/Auth/RouteAccessControlIntegrationTest.php',
                'tests/Integration/User/Application/CommandHandler/SignInCommandHandlerIntegrationTest.php',
                'tests/Integration/User/Application/CommandHandler/UpdateUserCommandHandlerIntegrationTest.php',
                'tests/Unit/Shared/Application/EventListener/ApiRateLimitListenerTest.php',
                'tests/Unit/Shared/Application/EventListener/SecurityHeadersResponseListenerTest.php',
                'tests/Unit/Shared/Auth/Factory/TestAccessTokenFactoryTest.php',
                'tests/Unit/Shared/Infrastructure/Security/DualAuthenticatorTest.php',
                'tests/Unit/User/Application/Command/UpdateUserCommandTest.php',
                'tests/Unit/User/Application/CommandHandler/CompleteTwoFactorCommandHandlerTest.php',
                'tests/Unit/User/Application/CommandHandler/ConfirmTwoFactorCommandHandlerTest.php',
                'tests/Unit/User/Application/CommandHandler/DisableTwoFactorCommandHandlerTest.php',
                'tests/Unit/User/Application/CommandHandler/RefreshTokenCommandHandlerTest.php',
                'tests/Unit/User/Application/CommandHandler/RegenerateRecoveryCodesCommandHandlerTest.php',
                'tests/Unit/User/Application/CommandHandler/SetupTwoFactorCommandHandlerTest.php',
                'tests/Unit/User/Application/CommandHandler/SignInCommandHandlerTest.php',
                'tests/Unit/User/Application/CommandHandler/UpdateUserCommandHandlerTest.php',
                'tests/Unit/User/Application/EventSubscriber/SignInEventLogSubscriberTest.php',
                'tests/Unit/User/Application/Processor/CompleteTwoFactorProcessorTest.php',
                'tests/Unit/User/Application/Processor/ConfirmTwoFactorProcessorTest.php',
                'tests/Unit/User/Application/Processor/DisableTwoFactorProcessorTest.php',
                'tests/Unit/User/Application/Processor/RefreshTokenProcessorTest.php',
                'tests/Unit/User/Application/Processor/RegenerateRecoveryCodesProcessorTest.php',
                'tests/Unit/User/Application/Processor/ResendEmailProcessorTest.php',
                'tests/Unit/User/Application/Processor/SetupTwoFactorProcessorTest.php',
                'tests/Unit/User/Application/Processor/SignInProcessorTest.php',
                'tests/Unit/User/Application/Processor/UserPatchProcessorTestCase.php',
                'tests/Unit/User/Application/Processor/UserPutProcessorTest.php',
                'tests/Unit/User/Application/Resolver/UserUpdateMutationResolverTest.php',
                'tests/Unit/User/Application/Service/PasswordChangeSessionRevokerTest.php',
                'tests/Unit/User/Application/Service/UserUpdateApplierTest.php',
                'tests/Unit/User/Domain/Event/AllSessionsRevokedEventTest.php',
                'tests/Unit/User/Domain/Event/RefreshTokenTheftDetectedEventTest.php',
                'tests/Unit/User/Infrastructure/Repository/MongoDBRecoveryCodeRepositoryTest.php',
                'tests/Unit/User/Infrastructure/Service/LexikAccessTokenGeneratorTest.php',
                'tests/Unit/User/Infrastructure/Service/RedisAccountLockoutServiceTest.php',
            ],
        ],
        UselessOverridingMethodSniff::class => [
            'exclude' => [
                'tests/Unit/Shared/Infrastructure/Bus/Event/Async/TestDomainEvent.php',
                'tests/Unit/Shared/Infrastructure/Bus/Event/Async/TestEvent.php',
                'tests/Unit/Shared/Infrastructure/Bus/Event/Async/TestOtherDomainEvent.php',
                'tests/Unit/Shared/Infrastructure/Bus/Event/Async/ResilientAsyncEventBusTestEvent.php',
            ],
        ],
    ],
    'requirements' => [
        'min-quality' => 100,
        'min-complexity' => 95,
        'min-architecture' => 100,
        'min-style' => 100,
    ],
    'threads' => null,
];
