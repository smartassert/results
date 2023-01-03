<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__ . '/bin')
    ->in(__DIR__ . '/public')
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        '@PhpCsFixer' => true,
        'concat_space' => [
            'spacing' => 'one',
        ],
        'php_unit_internal_class' => false,
        'php_unit_test_class_requires_covers' => false,
        'types_spaces' => [
            'space_multiple_catch' => 'single',
        ],
    ])
    ->setFinder($finder)
    ;
