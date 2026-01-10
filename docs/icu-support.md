# ICU Support

This document explains what ICU MessageFormat constructs ICU Parser supports and how it validates messages. It is written for developers who want to understand the library's capabilities and limitations.

## What is Supported

ICU Parser aims to support the most common ICU MessageFormat 1.x constructs while remaining lightweight and practical.

### Supported Constructs

#### Arguments

Simple placeholders with names or indices:

```php
// Named argument
'{name}'

// Indexed argument
'{0}'
```

#### Number Formatting

Format numbers with various patterns:

```php
// Simple number
'{price, number}'

// Currency
'{price, number, currency}'

// Percent
'{rate, number, percent}'

// Custom pattern
'{value, number, #,##0.00}'
```

#### Date and Time Formatting

Format dates and times:

```php
// Date
'{date, date}'

// Time
'{time, time}'

// With style
'{date, date, short}'
'{time, time, long}'

// With pattern
'{date, date, yyyy-MM-dd}'
```

#### Plural

Format messages based on plural rules:

```php
'{count, plural, one {# item} other {# items}}'
```

Plural rules vary by locale. English uses `one` and `other`, while other languages have more categories (e.g., Arabic has `zero`, `one`, `two`, `few`, `many`, `other`).

The `#` symbol is a special placeholder that represents the number being formatted.

#### Select

Format messages based on keyword selection:

```php
'{gender, select, male {He} female {She} other {They}}'
```

#### Selectordinal

Format messages based on ordinal numbers:

```php
'{position, selectordinal, one {#st} two {#nd} few {#rd} other {#th}}'
```

This is similar to plural but uses ordinal categories (e.g., "1st", "2nd", "3rd").

#### Choice

Legacy choice format (less common):

```php
'{value, choice, 0#none|1<one|2<many}'
```

Note: Choice format is not recommended for new code. Use `plural` or `select` instead.

## What is NOT Supported

ICU Parser does not support some ICU constructs and features:

### MessageFormat 2 (MF2)

ICU Parser targets ICU MessageFormat 1.x only. MessageFormat 2 is a newer format with different syntax and is not supported.

### Limited Validation for Some Number Formats

ICU Parser parses these formats and infers them as numbers, but custom rule validation is limited:

- **Spellout:** `{n, spellout}` - Parsed; custom rule sets are not validated
- **Ordinal:** `{n, ordinal}` - Parsed; custom rule sets are not validated
- **Duration:** `{n, duration}` - Parsed; custom rule sets are not validated

These constructs are less common and may have limited validation.

### All Locale-Specific Edge Cases

ICU Parser uses a simplified rule set for plural categories without `symfony/intl`. With `symfony/intl`, it uses full ICU data, but some locale-specific edge cases may still not be covered.

### Pattern Validation Limits

Number, date, and time pattern validation is conservative and may not catch every ICU edge case. It focuses on common mistakes.

## Validation Behavior

ICU Parser validates messages for common errors and issues.

### Syntax Validation

The parser catches syntax errors:

- Unclosed placeholders
- Missing commas
- Invalid format types
- Malformed patterns

Example:

```php
use IcuParser\Exception\ParserException;

try {
    $ast = $parser->parse('{unclosed');
} catch (ParserException $e) {
    echo "Error: " . $e->getMessage();
    // Output includes the error message and position context
}
```

### Semantic Validation

The `SemanticValidator` checks for semantic issues:

#### Missing Options

Required plural/select categories may be missing:

```php
// Error: Missing required plural category: "other"
'{count, plural, one {# item}}'
```

#### Duplicate Options

Options with the same keyword:

```php
// Error: Duplicate option in select: "male"
'{gender, select, male {He} male {Him} other {They}}'
```

#### Empty Messages

Message variants with no content:

```php
// Error: Empty message variant for plural category: "one"
'{count, plural, one {} other {# items}}'
```

#### Invalid Patterns

Basic validation of number/date/time patterns:

```php
// Error: Invalid date pattern
'{date, date, invalid-pattern}'
```

### Locale-Specific Validation

Validation varies by locale:

- **Plural categories:** Different locales require different categories
- **Keyword validation:** Some keywords are locale-specific

Example with `symfony/intl`:

```php
use IcuParser\Validation\SemanticValidator;

$validator = new SemanticValidator();

// English (requires: one, other)
$message = '{count, plural, one {# item} other {# items}}';
$result = $validator->validate($ast, $message, 'en');
// Valid

// Arabic (requires: zero, one, two, few, many, other)
$message = '{count, plural, one {# عنصر} other {# عناصر}}';
$result = $validator->validate($ast, $message, 'ar');
// Error: Missing required plural categories: "zero", "two", "few", "many"
```

## Locale Data

ICU Parser uses locale data for plural categories and validation rules.

### With symfony/intl

With `symfony/intl` installed:

```bash
composer require symfony/intl
```

- Uses full ICU locale data
- Accurate plural categories for all supported locales
- Better locale fallback behavior
- More comprehensive validation

### Without symfony/intl

Without `symfony/intl`, ICU Parser:

- Uses a small internal rule set for common locales
- Falls back to basic categories (`one`, `other`)
- May not validate all locale-specific rules correctly

**Recommendation:** Install `symfony/intl` for production use, especially if you support multiple locales.

## Accuracy and Limitations

### Well-tested behavior

These areas are the most exercised in tests and real usage:

- Parsing of ICU MessageFormat 1.x syntax
- AST structure and traversal
- Syntax error detection with accurate position reporting
- Basic semantic validation
- Number/date/time pattern validation for common cases

### Heuristic Functionality

These features use heuristics and may have limitations:

- **Semantic validation:** Checks for common issues but may miss edge cases
- **Pattern validation:** Validates common patterns but not all ICU format strings
- **Locale rules:** Without `symfony/intl`, locale rules are simplified

### Context-Dependent Behavior

Some features depend on context:

- **Plural categories:** Vary by locale (use `symfony/intl` for accuracy)
- **Pattern validation:** ICU patterns have locale-specific variations
- **Keyword validation:** Some keywords are locale-specific

## Comparison with ICU

### ICU Parser vs ICU Library

| Feature | ICU Parser | ICU Library |
|---------|-----------|-------------|
| **Purpose** | Static analysis | Runtime formatting |
| **Parsing** | Yes | Yes |
| **Validation** | Yes | No |
| **Formatting** | Pretty formatting (static) | Runtime formatting |
| **AST** | Yes | No |
| **Coverage** | Common constructs | Full ICU |

### When to Use ICU Parser

Use ICU Parser when you need to:

- Parse ICU messages into an AST
- Validate messages for errors
- Extract parameter types
- Format messages for readability
- Lint translation files

### When to Use ICU Library

Use ICU Library (via `ext-intl`) when you need to:

- Format messages at runtime
- Format numbers, dates, and times
- Use full ICU functionality
- Support all ICU edge cases

They complement each other. Use ICU Parser for validation and analysis, ICU Library for runtime formatting.

## Reporting Issues

If you discover a gap between ICU behavior and ICU Parser:

1. Check if it's a supported construct (see "What is Supported")
2. Check if `symfony/intl` is installed (for locale-specific issues)
3. Open an issue with a minimal example:
   - The ICU message
   - Expected behavior (per ICU spec)
   - Actual behavior (ICU Parser output)
   - Locale (if applicable)

See <https://github.com/yoeunes/icu-parser/issues> for issue tracking.

## Future Enhancements

Potential areas for improvement:

- Better support for spellout, ordinal, and duration formats
- More comprehensive pattern validation
- Support for more locales without `symfony/intl`
- MessageFormat 2 (MF2) support

## Getting Help

- Issues and bug reports: <https://github.com/yoeunes/icu-parser/issues>
- Documentation: See the `docs/` directory
- Examples: See `tests/` for usage patterns

---

Previous: [Formatting](formatting.md) | Next: [Architecture](ARCHITECTURE.md)
