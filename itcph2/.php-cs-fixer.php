<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/dist/browser')
    ->exclude([
        'vendor',
        'node_modules',
        'storage',
        'bootstrap/cache',
    ]);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(false)
    ->setRules([
        // Base standard
        '@PSR12' => true,

        // Arrays
        'array_syntax' => [
            'syntax' => 'short',
        ],
        'trim_array_spaces' => true,
        'whitespace_after_comma_in_array' => true,

        // Formatting / structure
        'no_multiple_statements_per_line' => true,
        'no_extra_blank_lines' => [
            'tokens' => ['extra'],
        ],
        'no_trailing_whitespace' => true,
        'no_trailing_whitespace_in_comment' => true,

        // Functions / arguments spacing
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
        ],

        // Naming / constants
        'constant_case' => [
            'case' => 'lower',
        ],

        // Control structures (approximation of Squiz rules)
        'control_structure_braces' => true,
        'control_structure_continuation_position' => [
            'position' => 'same_line',
        ],

        // Visibility for constants (PSR12)
        'visibility_required' => [
            'elements' => ['const'],
        ],
    ])
    ->setLineEnding("\n")
    ->setFinder($finder);
