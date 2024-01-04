<?php

declare(strict_types=1);

use SlevomatCodingStandard\Sniffs\Functions\UnusedParameterSniff;

return [
    'preset' => 'symfony',
    'ide' => 'phpstorm',
    'exclude' => [
        'vendor',
    ],
    'add' => [],
    'remove' => [],
    'config' => [
        UnusedParameterSniff::class => ['exclude' => ['src', 'migrations']],
    ],
    'requirements' => [
        'min-quality' => 100,
        'min-complexity' => 100,
        'min-architecture' => 100,
        'min-style' => 100,
    ],
    'threads' => null,
];
