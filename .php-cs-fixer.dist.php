<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'phpdoc_align' => false,
        'yoda_style' => [
            'equal' => false,
            'identical' => false,
            'less_and_greater' => false
        ],
        'nullable_type_declaration' => true,
        'class_definition' => [
            'multi_line_extends_each_single_line' => true,
            'single_line' => false,
            'single_item_single_line' => false
        ],
    ])
    ->setFinder($finder);
