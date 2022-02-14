<?php

$header = <<<EOF
EOF;

$finder = PhpCsFixer\Finder::create()
    ->in([ __DIR__.'/src',  __DIR__.'/tests'])
;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        '@PHP74Migration' => true,
        '@PHPUnit60Migration:risky' => true,
        'header_comment' => [
            'header' => $header,
        ],
        'list_syntax' => [
            'syntax' => 'short',
        ],
        'binary_operator_spaces' => [
            'default' => 'align',
        ],
        'method_chaining_indentation' => false,
        'phpdoc_types_order' => [
            'null_adjustment' => 'always_last',
        ],
        'php_unit_internal_class' => false,
        'php_unit_test_class_requires_covers' => false,
        'no_superfluous_phpdoc_tags' => true,
        'static_lambda' => true,
        'global_namespace_import' => [
           'import_classes' => true,
           'import_constants' => false,
           'import_functions' => false,
        ],
        'void_return' => true,
    ])
    ->setFinder($finder);
