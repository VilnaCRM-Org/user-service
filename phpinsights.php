<?php

declare(strict_types=1);

use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenNormalClasses;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineLengthSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Formatting\SpaceAfterNotSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Strings\UnnecessaryStringConcatSniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousExceptionNamingSniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousInterfaceNamingSniff;
use SlevomatCodingStandard\Sniffs\Functions\UnusedParameterSniff;
use  SlevomatCodingStandard\Sniffs\Namespaces\UseSpacingSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\ReturnTypeHintSniff;

return [
    'preset' => 'symfony',
    'ide' => 'phpstorm',
    'exclude' => [
        'vendor',
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
        LineLengthSniff::class => [
            'exclude' => [
                'phpinsights',
            ],
        ],
        ForbiddenNormalClasses::class => [
            'exclude' => [
                'src/Shared/Infrastructure/Bus/Command/InMemorySymfonyCommandBus',
                'src/Shared/Infrastructure/Bus/Event/InMemorySymfonyEventBus',
                'src/Shared/OpenApi/Factory/Response/DuplicateEmailResponseFactory',
                'src/User/Domain/Entity/User',
            ],
        ],
        UnnecessaryStringConcatSniff::class => [
            'exclude' => [
                'src/Shared/OpenApi/Factory/Response/UnsupportedGrantTypeResponseFactory',
                'src/User/Application/Exception/DuplicateEmailException',
                'src/Shared/OpenApi/Factory/Response/DuplicateEmailResponseFactory',
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
