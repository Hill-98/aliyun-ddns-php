<?php

/**
 * It's just a habit.
 *
 * @author Hill-98
 */

declare(strict_types=1);

$config ??= new PhpCsFixer\Config();

$rules = [
    '@PSR12' => true,
    // Alias
    'backtick_to_shell_exec' => true,
    'no_alias_language_construct_call' => true,
    'no_mixed_echo_print' => ['use' => 'echo'],
    // Array Notation
    'array_syntax' => ['syntax' => 'short'],
    'no_multiline_whitespace_around_double_arrow' => true,
    'no_trailing_comma_in_singleline_array' => true,
    'no_whitespace_before_comma_in_array' => true,
    'normalize_index_brace' => true,
    'trim_array_spaces' => true,
    'whitespace_after_comma_in_array' => true,
    // Casing
    'magic_constant_casing' => true,
    'magic_method_casing' => true,
    'native_function_casing' => true,
    'native_function_type_declaration_casing' => true,
    // Cast Notation
    'cast_spaces' => true,
    'no_short_bool_cast' => true,
    'no_unset_cast' => true,
    // Class Notation
    'class_attributes_separation' => true,
    'no_null_property_initialization' => true,
    'self_static_accessor' => true,
    // Comment
    'multiline_comment_opening_closing' => true,
    'no_empty_comment' => true,
    'single_line_comment_style' => true,
    // Control Structure
    'include' => true,
    'no_superfluous_elseif' => true,
    'no_trailing_comma_in_list_call' => true,
    'no_unneeded_control_parentheses' => true,
    'no_unneeded_curly_braces' => true,
    'no_useless_else' => true,
    'simplified_if_return' => true,
    'switch_continue_to_break' => true,
    'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters']],
    // Function Notation
    'function_typehint_space' => true,
    'lambda_not_used_import' => true,
    // Import
    'fully_qualified_strict_types' => true,
    'no_unused_imports' => true,
    // Language Construct
    'combine_consecutive_issets' => true,
    'combine_consecutive_unsets' => true,
    'single_space_after_construct' => true,
    // List Notation
    'list_syntax' => true,
    // Namespace Notation
    'clean_namespace' => true,
    'no_leading_namespace_whitespace' => true,
    // Operator
    'binary_operator_spaces' => true,
    'concat_space' => true,
    'object_operator_without_whitespace' => true,
    'operator_linebreak' => ['only_booleans' => true],
    'standardize_increment' => true,
    'standardize_not_equals' => true,
    'ternary_to_null_coalescing' => true,
    'unary_operator_spaces' => true,
    // PHP Tag
    'linebreak_after_opening_tag' => true,
    // PHP Unit
    'php_unit_method_casing' => true,
    // PHP Doc
    'align_multiline_comment' => true,
    'general_phpdoc_tag_rename' => ['replacements' => ['inheritDocs' => 'inheritDoc']],
    'no_blank_lines_after_phpdoc' => true,
    'no_empty_phpdoc' => true,
    // 'phpdoc_align' => true,
    'phpdoc_indent' => true,
    'phpdoc_inline_tag_normalizer' => true,
    'phpdoc_no_access' => true,
    'phpdoc_no_alias_tag' => true,
    'phpdoc_no_package' => true,
    'phpdoc_no_useless_inheritdoc' => true,
    'phpdoc_return_self_reference' => true,
    'phpdoc_scalar' => true,
    'phpdoc_single_line_var_spacing' => true,
    'phpdoc_tag_type' => ['tags' => ['inheritDoc' => 'inline']],
    'phpdoc_to_comment' => true,
    'phpdoc_trim_consecutive_blank_line_separation' => true,
    'phpdoc_trim' => true,
    'phpdoc_types' => true,
    'phpdoc_types_order' => true,
    // Return Notation
    'no_useless_return' => true,
    'return_assignment' => true,
    // Semicolon
    'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
    'no_empty_statement' => true,
    'no_singleline_whitespace_before_semicolons' => true,
    'semicolon_after_instruction' => true,
    'space_after_semicolon' => ['remove_in_empty_for_expressions' => true],
    // String Notation
    'no_binary_string' => true,
    'single_quote' => true,
    // Whitespace
    'array_indentation' => true,
    'no_extra_blank_lines' => [
        'tokens' => [
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
    'no_spaces_around_offset' => true,
];
$riskyRules = [
    // Alias
    'array_push' => true,
    'no_alias_functions' => ['sets' => ['@all']],
    // Basic
    'psr_autoloading' => true,
    // Cast Notation
    'modernize_types_casting' => true,
    // Comment
    'comment_to_phpdoc' => true,
    // Function Notation
    'combine_nested_dirname' => true,
    'implode_call' => true,
    'phpdoc_to_param_type' => true,
    'phpdoc_to_property_type' => true,
    'phpdoc_to_return_type' => true,
    'use_arrow_functions' => true,
    'void_return' => true,
    // Language Construct
    'dir_constant' => true,
    // Operator
    'ternary_to_elvis_operator' => true,
    // Strict
    'declare_strict_types' => true,
    'strict_comparison' => true,
    'strict_param' => true,
    // String Notation
    'string_line_ending' => true,
];

if (defined('RISKY') && constant('RISKY')) {
    $config = $config->setRiskyAllowed(true);
    $rules = array_merge($rules, $riskyRules);
}

return $config->setRules($rules);
