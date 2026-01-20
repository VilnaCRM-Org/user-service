<?php

declare(strict_types=1);

use NunoMaduro\PhpInsights\Domain\Insights\CyclomaticComplexityIsHigh;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenNormalClasses;
use PHP_CodeSniffer\Standards\Generic\Sniffs\CodeAnalysis\UselessOverridingMethodSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineLengthSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Formatting\SpaceAfterNotSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Strings\UnnecessaryStringConcatSniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousExceptionNamingSniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousInterfaceNamingSniff;
use SlevomatCodingStandard\Sniffs\Functions\FunctionLengthSniff;
use SlevomatCodingStandard\Sniffs\Functions\UnusedParameterSniff;
use SlevomatCodingStandard\Sniffs\Namespaces\UseSpacingSniff;
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
                'tests/Behat/HealthCheckContext/HealthCheckContext.php',
            ],
        ],
        LineLengthSniff::class => [
            'exclude' => [
                'phpinsights',
                'tests/Behat/OAuthContext/OAuthContext.php',
                'src/User/Infrastructure/Repository/MariaDBPasswordResetTokenRepository.php',
                'tests/Integration/User/Infrastructure/Repository/CachePerformanceTest.php',
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
                'tests/Unit/User/Application/EventSubscriber/PasswordChangedCacheInvalidationSubscriberTest.php',
                'tests/Unit/User/Application/EventSubscriber/UserConfirmedCacheInvalidationSubscriberTest.php',
                'tests/Unit/User/Application/EventSubscriber/UserRegisteredCacheInvalidationSubscriberTest.php',
                'tests/Unit/User/Application/EventSubscriber/EmailChangedCacheInvalidationSubscriberTest.php',
                'tests/Integration/User/Infrastructure/Repository/CachePerformanceTest.php',
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
