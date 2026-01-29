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
    ],
    'add' => [],
    'remove' => [
        UnusedParameterSniff::class,
        SuperfluousInterfaceNamingSniff::class,
        SuperfluousExceptionNamingSniff::class,
        SpaceAfterNotSniff::class,
        NunoMaduro\PhpInsights\Domain\Sniffs\ForbiddenSetterSniff::class,
        UseSpacingSniff::class,
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
                'tests/Unit/OAuth/Infrastructure/Service/CredentialsRevokerTest.php',
                'tests/Unit/OAuth/Infrastructure/Manager/BuilderMockFactoryTrait.php',
            ],
        ],
        LineLengthSniff::class => [
            'exclude' => [
                'phpinsights',
                'tests/Behat/OAuthContext/OAuthContext.php',
                'tests/Unit/OAuth/Infrastructure/Service/CredentialsRevokerTest.php',
                'tests/Unit/OAuth/Infrastructure/Manager/AccessTokenManagerTest.php',
                'tests/Unit/OAuth/Infrastructure/Manager/BuilderMockFactoryTrait.php',
                'src/User/Infrastructure/Repository/MariaDBPasswordResetTokenRepository.php',
                'tests/Integration/User/Infrastructure/Repository/CachePerformanceTest.php',
                'tests/Integration/Internal/HealthCheck/Infrastructure/EventSubscriber/DBCheckSubscriberTest.php',
                'tests/Unit/DataFixtures/Command/SeedSchemathesisDataCommandTest.php',
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
                // Behat contexts legitimately need many step definitions
                'tests/Behat/HealthCheckContext/HealthCheckContext.php',
                'tests/Behat/OAuthContext/Input/ObtainAuthorizeCodeInput.php',
                'tests/Behat/OAuthContext/OAuthContext.php',
            ],
        ],
        FunctionLengthSniff::class => [
            'exclude' => [
                'tests/Unit/User/Infrastructure/Repository/CachedUserRepositoryTest.php',
                'tests/Unit/User/Application/EventSubscriber/ConfirmationEmailSendEventSubscriberTest.php',
                'tests/Unit/User/Application/EventSubscriber/EmailChangedEventSubscriberTest.php',
                'tests/Unit/User/Application/EventSubscriber/UserRegisteredEventSubscriberTest.php',
                'tests/Unit/OAuth/Infrastructure/Manager/ClientManagerTest.php',
                'tests/Unit/OAuth/Infrastructure/Service/CredentialsRevokerTest.php',
                'tests/Unit/Shared/Infrastructure/Fixture/Seeder/SchemathesisOAuthSeederTest.php',
                // Complex test helper trait with 83-line mock builder setup
                'tests/Unit/OAuth/Infrastructure/Manager/BuilderMockFactoryTrait.php',
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
        // Legitimate use of trait for test helper methods shared across multiple test classes
        ForbiddenTraits::class => [
            'exclude' => [
                'tests/Unit/OAuth/Infrastructure/Manager/BuilderMockFactoryTrait.php',
            ],
        ],
        SuperfluousTraitNamingSniff::class => [
            'exclude' => [
                'tests/Unit/OAuth/Infrastructure/Manager/BuilderMockFactoryTrait.php',
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
