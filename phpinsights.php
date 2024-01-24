<?php

declare(strict_types=1);

use PHP_CodeSniffer\Standards\Generic\Sniffs\Formatting\SpaceAfterNotSniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousExceptionNamingSniff;
use SlevomatCodingStandard\Sniffs\Classes\SuperfluousInterfaceNamingSniff;
use SlevomatCodingStandard\Sniffs\Functions\UnusedParameterSniff;

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
    ],
    'config' => [
        NunoMaduro\PhpInsights\Domain\Insights\ForbiddenNormalClasses::class => ['exclude' => [
            'src/Shared/Infrastructure/Bus/Command/InMemorySymfonyCommandBus',
            'src/Shared/Infrastructure/Bus/Event/InMemorySymfonyEventBus',
            'src/User/Domain/Entity/User',
        ],
        ],
    ],
    'requirements' => [
        'min-quality' => 95,
        'min-complexity' => 95,
        'min-architecture' => 95,
        'min-style' => 95,
    ],
    'threads' => null,
];
