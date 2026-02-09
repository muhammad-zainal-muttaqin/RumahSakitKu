<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(__DIR__)
    ->exclude([
        'vendor',
        'storage',
        'bootstrap/cache',
        'node_modules',
        'public',
        'resources/views',
    ])
    ->name('*.php')
    ->notName([
        '*.blade.php',
        'index.php',
        'server.php',
    ])
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new Config())
    ->setRules([
        // PSR-12 standard
        '@PSR12' => true,

        // Laravel PER (PHP Extended Recommendation)
        '@PER-CS2.0' => true,

        // Array notation
        'array_syntax' => ['syntax' => 'short'],
        'no_multiline_whitespace_around_double_arrow' => true,
        'trim_array_spaces' => true,
        'whitespace_after_comma_in_array' => ['ensure_single_space' => true],
        'no_trailing_comma_in_singleline_array' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays']],

        // Basic
        'encoding' => true,
        'full_opening_tag' => true,

        // Casing
        'class_reference_name_casing' => true,
        'constant_case' => ['case' => 'lower'],
        'integer_literal_case' => true,
        'lowercase_keywords' => true,
        'lowercase_static_reference' => true,
        'magic_constant_casing' => true,
        'magic_method_casing' => true,
        'native_function_casing' => true,
        'native_type_declaration_casing' => true,

        // Cast notation
        'cast_spaces' => ['space' => 'single'],
        'lowercase_cast' => true,
        'no_short_bool_cast' => true,
        'short_scalar_cast' => true,

        // Class notation
        'class_attributes_separation' => [
            'elements' => [
                'const' => 'one',
                'method' => 'one',
                'property' => 'one',
                'trait_import' => 'none',
                'case' => 'none',
            ],
        ],
        'no_null_property_initialization' => true,
        'no_php4_constructor' => true,
        'no_unneeded_final_method' => true,
        'ordered_class_elements' => [
            'order' => [
                'use_trait',
                'case',
                'constant',
                'constant_public',
                'constant_protected',
                'constant_private',
                'property_public',
                'property_protected',
                'property_private',
                'construct',
                'destruct',
                'magic',
                'phpunit',
                'method_public',
                'method_protected',
                'method_private',
            ],
            'sort_algorithm' => 'none',
        ],
        'ordered_types' => [
            'null_adjustment' => 'always_last',
            'sort_algorithm' => 'none',
        ],
        'self_static_accessor' => true,
        'single_class_element_per_statement' => [
            'elements' => ['const', 'property'],
        ],
        'single_trait_insert_per_statement' => true,
        'visibility_required' => [
            'elements' => ['const', 'method', 'property'],
        ],

        // Comment
        'multiline_comment_opening_closing' => true,
        'no_empty_comment' => true,
        'no_trailing_whitespace_in_comment' => true,
        'single_line_comment_spacing' => true,
        'single_line_comment_style' => [
            'comment_types' => ['asterisk', 'hash'],
        ],

        // Control structures
        'control_structure_braces' => true,
        'control_structure_continuation_position' => [
            'position' => 'same_line',
        ],
        'elseif' => true,
        'include' => true,
        'no_alternative_syntax' => true,
        'no_superfluous_elseif' => true,
        'no_unneeded_control_parentheses' => [
            'statements' => [
                'break',
                'clone',
                'continue',
                'echo_print',
                'negative_instanceof',
                'others',
                'return',
                'switch_case',
                'yield',
                'yield_from',
            ],
        ],
        'no_unneeded_curly_braces' => [
            'namespaces' => true,
        ],
        'no_useless_else' => true,
        'simplified_if_return' => true,
        'switch_case_semicolon_to_colon' => true,
        'switch_case_space' => true,
        'switch_continue_to_break' => true,

        // Function notation
        'combine_nested_dirname' => true,
        'fopen_flag_order' => true,
        'fopen_flags' => [
            'b_mode' => false,
        ],
        'function_declaration' => [
            'closure_function_spacing' => 'one',
            'closure_fn_spacing' => 'one',
            'trailing_comma_single_line' => false,
        ],
        'lambda_not_used_import' => true,
        'method_argument_space' => [
            'after_heredoc' => true,
            'keep_multiple_spaces_after_comma' => false,
            'on_multiline' => 'ensure_fully_multiline',
        ],
        'no_spaces_after_function_name' => true,
        'nullable_type_declaration' => [
            'syntax' => 'question_mark',
        ],
        'nullable_type_declaration_for_default_null_value' => true,
        'return_type_declaration' => [
            'space_before' => 'none',
        ],
        'single_line_throw' => false,
        'static_lambda' => false,
        'use_arrow_functions' => true,
        'void_return' => true,

        // Import
        'fully_qualified_strict_types' => [
            'import_symbols' => false,
            'leading_backslash_in_global_namespace' => false,
            'phpdoc_tags' => [],
        ],
        'global_namespace_import' => [
            'import_classes' => false,
            'import_constants' => false,
            'import_functions' => false,
        ],
        'group_import' => false,
        'no_leading_import_slash' => true,
        'no_unused_imports' => true,
        'ordered_imports' => [
            'imports_order' => [
                'class',
                'function',
                'const',
            ],
            'sort_algorithm' => 'alpha',
        ],
        'single_import_per_statement' => true,
        'single_line_after_imports' => true,

        // List notation
        'list_syntax' => [
            'syntax' => 'short',
        ],

        // Namespace notation
        'blank_line_after_namespace' => true,
        'blank_lines_before_namespace' => [
            'max_line_breaks' => 2,
            'min_line_breaks' => 2,
        ],
        'clean_namespace' => true,
        'no_leading_namespace_whitespace' => true,
        'single_blank_line_before_namespace' => false,

        // Operators
        'binary_operator_spaces' => [
            'default' => 'single_space',
            'operators' => [
                '=' => 'single_space',
                '=>' => 'single_space',
            ],
        ],
        'concat_space' => ['spacing' => 'one'],
        'increment_style' => ['style' => 'post'],
        'logical_operators' => true,
        'new_with_parentheses' => [
            'anonymous_class' => true,
            'named_class' => true,
        ],
        'no_space_around_double_colon' => true,
        'not_operator_with_space' => false,
        'not_operator_with_successor_space' => false,
        'object_operator_without_whitespace' => true,
        'operator_linebreak' => [
            'only_booleans' => true,
            'position' => 'beginning',
        ],
        'standardize_increment' => true,
        'standardize_not_equals' => true,
        'ternary_to_elvis_operator' => true,
        'ternary_to_null_coalescing' => true,
        'unary_operator_spaces' => true,

        // PHPDoc
        'align_multiline_comment' => [
            'comment_type' => 'all_multiline',
        ],
        'general_phpdoc_annotation_remove' => [
            'annotations' => [],
        ],
        'general_phpdoc_tag_rename' => true,
        'no_blank_lines_after_phpdoc' => true,
        'no_empty_phpdoc' => true,
        'phpdoc_add_missing_param_annotation' => [
            'only_untyped' => false,
        ],
        'phpdoc_align' => [
            'align' => 'vertical',
            'spacing' => [
                'param' => 2,
            ],
            'tags' => [
                'method',
                'param',
                'property',
                'return',
                'throws',
                'type',
                'var',
            ],
        ],
        'phpdoc_annotation_without_dot' => true,
        'phpdoc_indent' => true,
        'phpdoc_inline_tag_normalizer' => true,
        'phpdoc_line_span' => [
            'const' => 'single',
            'method' => 'multi',
            'property' => 'single',
        ],
        'phpdoc_no_access' => true,
        'phpdoc_no_alias_tag' => true,
        'phpdoc_no_empty_return' => true,
        'phpdoc_no_package' => true,
        'phpdoc_no_useless_inheritdoc' => true,
        'phpdoc_order' => [
            'order' => [
                'param',
                'return',
                'throws',
            ],
        ],
        'phpdoc_return_self_reference' => true,
        'phpdoc_scalar' => true,
        'phpdoc_separation' => [
            'groups' => [
                ['deprecated', 'link', 'see', 'since'],
                ['author', 'copyright', 'license'],
                ['category', 'package', 'subpackage'],
                ['property', 'property-read', 'property-write'],
                ['param'],
                ['return'],
                ['throws'],
            ],
        ],
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_summary' => true,
        'phpdoc_tag_casing' => true,
        'phpdoc_tag_type' => [
            'tags' => [
                'inheritdoc' => 'inline',
            ],
        ],
        'phpdoc_to_comment' => true,
        'phpdoc_trim' => true,
        'phpdoc_trim_consecutive_blank_line_separation' => true,
        'phpdoc_types' => true,
        'phpdoc_types_order' => [
            'null_adjustment' => 'always_last',
            'sort_algorithm' => 'none',
        ],
        'phpdoc_var_annotation_correct_order' => true,
        'phpdoc_var_without_name' => true,

        // Return notation
        'no_useless_return' => true,
        'return_assignment' => true,
        'simplified_null_return' => false,

        // Semicolon
        'multiline_whitespace_before_semicolons' => [
            'strategy' => 'no_multi_line',
        ],
        'no_empty_statement' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'semicolon_after_instruction' => true,
        'space_after_semicolon' => [
            'remove_in_empty_for_expressions' => true,
        ],

        // Strict mode
        'declare_strict_types' => false,
        'strict_comparison' => false,
        'strict_param' => false,

        // String notation
        'escape_implicit_backslashes' => [
            'double_quoted' => true,
            'heredoc_syntax' => true,
            'single_quoted' => false,
        ],
        'explicit_string_variable' => false,
        'heredoc_to_nowdoc' => true,
        'no_binary_string' => true,
        'no_trailing_whitespace_in_string' => false,
        'simple_to_complex_string_variable' => true,
        'single_quote' => [
            'strings_containing_single_quote_chars' => false,
        ],
        'string_implicit_backslashes' => [
            'double_quoted' => 'escape',
            'heredoc' => 'escape',
            'single_quoted' => 'ignore',
        ],
        'string_length_to_empty' => true,

        // Whitespace
        'array_indentation' => true,
        'blank_line_after_opening_tag' => true,
        'blank_line_before_statement' => [
            'statements' => [
                'break',
                'case',
                'continue',
                'declare',
                'default',
                'do',
                'exit',
                'for',
                'foreach',
                'goto',
                'if',
                'include',
                'include_once',
                'phpdoc',
                'require',
                'require_once',
                'return',
                'switch',
                'throw',
                'try',
                'while',
                'yield',
                'yield_from',
            ],
        ],
        'blank_line_between_import_groups' => true,
        'compact_nullable_type_declaration' => true,
        'heredoc_indentation' => true,
        'indentation_type' => true,
        'line_ending' => true,
        'method_chaining_indentation' => true,
        'no_extra_blank_lines' => [
            'tokens' => [
                'attribute_group',
                'break',
                'case',
                'continue',
                'curly_brace_block',
                'default',
                'extra',
                'parenthesis_brace_block',
                'return',
                'square_brace_block',
                'switch',
                'throw',
                'use',
                'use_trait',
            ],
        ],
        'no_horizontal_whitespace_before_close_tag' => true,
        'no_mixed_echo_print' => [
            'use' => 'echo',
        ],
        'no_spaces_around_offset' => [
            'positions' => ['inside', 'outside'],
        ],
        'no_trailing_whitespace' => true,
        'no_trailing_whitespace_in_comment' => true,
        'no_whitespace_before_comma_in_array' => [
            'after_heredoc' => false,
        ],
        'no_whitespace_in_blank_line' => true,
        'single_blank_line_at_eof' => true,
        'spaces_inside_parentheses' => [
            'space' => 'none',
        ],
        'statement_indentation' => true,
        'type_declaration_spaces' => [
            'elements' => ['function', 'property'],
        ],
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setUsingCache(true)
    ->setCacheFile(__DIR__ . '/storage/framework/cache/.php-cs-fixer.cache');
