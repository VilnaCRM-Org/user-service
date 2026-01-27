<?php

declare(strict_types=1);

use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenDefineFunctions;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenNormalClasses;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineLengthSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Formatting\SpaceAfterNotSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Strings\UnnecessaryStringConcatSniff;
use SlevomatCodingStandard\Sniffs\Classes\ForbiddenPublicPropertySniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousExceptionNamingSniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousInterfaceNamingSniff;
use SlevomatCodingStandard\Sniffs\Functions\FunctionLengthSniff;
use SlevomatCodingStandard\Sniffs\Functions\UnusedParameterSniff;
use SlevomatCodingStandard\Sniffs\Namespaces\AlphabeticallySortedUsesSniff;
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
        AlphabeticallySortedUsesSniff::class,
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
            ],
        ],
        LineLengthSniff::class => [
            'exclude' => [
                'phpinsights',
                'tests/Behat/OAuthContext/OAuthContext.php',
                'src/User/Infrastructure/Repository/MongoDBPasswordResetTokenRepository.php',
                'src/Shared/Infrastructure/DoctrineType/DomainUuidType.php',
            ],
            'ignoreComments' => true,
            'lineLimit' => 100,
        ],
        ForbiddenNormalClasses::class => [
            'exclude' => [
                'src/Shared/Infrastructure/Bus/Command/InMemorySymfonyCommandBus',
                'src/Shared/Infrastructure/Bus/Event/InMemorySymfonyEventBus',
                'src/User/Domain/Entity/User',
                // OAuth DTOs cannot be final - Doctrine ODM needs non-final classes for proxy generation
                'src/OAuth/Domain/Entity/AccessTokenDocument',
                'src/OAuth/Domain/Entity/AuthorizationCodeDocument',
                'src/OAuth/Domain/Entity/ClientDocument',
                'src/OAuth/Domain/Entity/RefreshTokenDocument',
                'src/User/Domain/Entity/PasswordResetToken',
            ],
        ],
        ForbiddenDefineFunctions::class => [
            'exclude' => [
                'src/Shared/Application/OpenApi/Enum/AllowEmptyValue',
                'src/Shared/Application/OpenApi/Enum/Requirement',
            ],
        ],
        UnnecessaryStringConcatSniff::class => [
            'exclude' => [
                'src/Shared/Application/OpenApi/Factory/Response/UnsupportedTypeFactory',
                'src/Shared/Infrastructure/Bus/Command/CommandNotRegisteredException',
                'src/Shared/Infrastructure/Bus/Event/EventNotRegisteredException.php',
                'tests/Unit/Shared/Application/OpenApi/Factory/Response/OAuthRedirectResponseFactoryTest',
                'tests/Unit/Shared/Application/OpenApi/Factory/Response/UnsupportedGrantTypeResponseFactoryTest',
                'tests/Behat/OAuthContext/OAuthContext',
            ],
        ],
        ForbiddenPublicPropertySniff::class => [
            'exclude' => [
                // OAuth DTOs must have public properties - Doctrine ODM PropertyAccessor requires public access
                'src/OAuth/Domain/Entity/AccessTokenDocument',
                'src/OAuth/Domain/Entity/AuthorizationCodeDocument',
                'src/OAuth/Domain/Entity/ClientDocument',
                'src/OAuth/Domain/Entity/RefreshTokenDocument',
            ],
        ],
        FunctionLengthSniff::class => [
            'exclude' => [
                // DTO conversion methods legitimately exceed 20 lines due to value object conversions
                'src/OAuth/Infrastructure/Manager/AccessTokenManager',
                'src/OAuth/Infrastructure/Manager/AuthorizationCodeManager',
                'src/OAuth/Infrastructure/Manager/ClientManager',
                'src/OAuth/Infrastructure/Service/CredentialsRevoker',
            ],
        ],
    ],
    'requirements' => [
        'min-quality' => 100,
        'min-complexity' => 94,
        'min-architecture' => 100,
        'min-style' => 100,
    ],
    'threads' => null,
];
