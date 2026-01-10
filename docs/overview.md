# ICU Parser Overview

This document explains what ICU Parser does, what it doesn't do, and how it fits into your i18n workflow. It is written for developers evaluating the library and for those who want to understand its capabilities and limitations.

## What ICU Parser is

ICU Parser is a **static analysis tool** for ICU MessageFormat strings. It parses ICU messages into a structured Abstract Syntax Tree (AST) and provides tools to validate, analyze, and understand your internationalization messages.

Think of ICU Parser as a linter and analyzer for your translation files, not a runtime formatting engine.

## Core Capabilities

### 1. Parsing

Converts ICU MessageFormat strings into a structured AST:

```php
use IcuParser\IcuParser;

$parser = new IcuParser();
$ast = $parser->parse('Hello {name}, you have {count, plural, one {# item} other {# items}}');
```

The AST represents every part of the message: text literals, placeholders, plural/select rules, and nested structures.

### 2. Validation

Detects common errors in ICU messages:

- **Syntax errors:** Unclosed braces, missing commas, invalid format types
- **Semantic errors:** Missing plural categories, duplicate options, empty message variants
- **Pattern errors:** Invalid number/date/time format patterns

### 3. Type Inference

Extracts parameter types from messages:

```php
$types = $parser->infer('{count, number} {date, date, short}')->all();
// Returns: ['count' => ParameterType::NUMBER, 'date' => ParameterType::DATETIME]
```

This helps you understand what parameters your messages expect, which is useful for code generation, API documentation, and runtime type checking.

### 4. Linting

Scans translation catalogs (YAML, XLIFF) for errors and inconsistencies:

```bash
bin/icu lint translations/
```

Reports issues like:
- Missing translations for keys
- Inconsistent parameter usage across translations
- Invalid ICU syntax
- Missing required plural categories

### 5. Syntax Highlighting

Colorizes ICU messages for better readability:

```php
use IcuParser\Highlight\HighlightTheme;

$highlighted = $parser->highlight('{count, plural, one {# item} other {# items}}', HighlightTheme::ansi());
```

### 6. Pretty Formatting

Normalizes ICU messages for consistent formatting:

```php
$pretty = $parser->format('{gender,select,male{He}other{They}}');
// Output:
// {gender, select,
//     male {He}
//     other {They}
// }
```

## What ICU Parser is NOT

### 1. NOT a Runtime Formatter

ICU Parser does **not** format messages at runtime. It analyzes and validates messages, but it does not substitute values.

For runtime formatting, use `MessageFormatter` from `ext-intl`:

```php
$formatter = new MessageFormatter('en', 'Hello {name}');
echo $formatter->format(['name' => 'World']); // "Hello World"
```

### 2. NOT a Complete ICU Implementation

ICU Parser supports the most common ICU MessageFormat 1.x constructs, but it does not cover every edge case or locale-specific rule nuance.

- **Supported:** Plural, select, selectordinal, choice, number/date/time formatting, nested messages
- **Not supported:** MessageFormat 2 (MF2), some obscure pattern modifiers, all locale-specific edge cases

See [ICU Support](icu-support.md) for a complete list of supported constructs.

### 3. NOT a Translation Management System

ICU Parser does not manage translations. It only analyzes and validates them. For translation management, use a dedicated TMS like Crowdin, Lokalise, or Weblate.

## Scope and Limitations

### Well-tested behavior

These areas are the most exercised in tests and real usage:

- Parsing of ICU MessageFormat 1.x syntax
- AST structure and traversal
- Syntax error detection with accurate position reporting
- Basic semantic validation (missing categories, duplicates, empty messages)
- Type inference for simple cases
- Pattern validation for common number/date/time formats

### Heuristic behavior

These features use heuristics and may have limitations:

- **Semantic validation:** Checks for common issues but may miss edge cases
- **Type inference:** Works well for typical cases but may not handle complex nested messages
- **Pattern validation:** Validates common patterns but not every ICU format string

### Context-Dependent Behavior

Some features depend on context:

- **Locale-specific rules:** Plural categories vary by locale. Use `symfony/intl` for accurate locale data.
- **Pattern validation:** ICU format patterns have locale-specific variations. The library validates for common mistakes but not all locale nuances.

## When to Use ICU Parser

Use ICU Parser when you need to:

- Validate translation files before deployment
- Detect missing translations or inconsistent parameter usage
- Extract parameter types for API documentation
- Understand complex ICU message structures
- Format ICU messages for consistency
- Lint codebases for ICU syntax errors
- Build custom tooling for i18n workflows

## When NOT to Use ICU Parser

Don't use ICU Parser when you need to:

- Format messages at runtime (use `ext-intl` instead)
- Support MessageFormat 2 (MF2)
- Translate or manage translations (use a TMS)
- Validate every ICU edge case (may need additional testing)

## Dependencies and optional extras

### ext-intl (required)

ICU Parser depends on `ext-intl` for plural category detection via `MessageFormatter`. This extension is required by `composer.json`.

### symfony/intl (optional)

Improves locale support and plural category detection:

```bash
composer require symfony/intl
```

Without `symfony/intl`, ICU Parser falls back to a smaller internal rule set for plural categories. With `symfony/intl`, it uses fuller ICU data, which is more accurate and supports more locales.

## Architecture

ICU Parser follows a layered architecture:

1. **Lexer:** Tokenizes ICU message strings
2. **Parser:** Builds an AST from tokens
3. **Visitors:** Traverse the AST for analysis, validation, formatting, and highlighting

For details, see [ARCHITECTURE.md](ARCHITECTURE.md).

## Performance Considerations

- Parsing is fast and suitable for linting large catalogs
- Caching is optional; the `TranslationLoader` can use a filesystem cache when a cache directory is provided
- Many AST classes are immutable (`readonly`) to avoid accidental mutation
- No runtime formatting overhead (it's not a runtime formatter)

## Security Considerations

ICU Parser does not execute messages or substitute values, so it does not introduce execution of user-provided content. However:

- Input messages are not sanitized for malicious content
- Large messages may cause memory issues (consider limiting input size)
- File-based linters read files from disk; validate file paths

## Comparison with Other Tools

### vs ext-intl

- **ext-intl:** Runtime formatting, full ICU implementation
- **ICU Parser:** Static analysis, validation, and AST generation

They complement each other. Use ICU Parser for validation and analysis, `ext-intl` for runtime formatting.

### vs Other ICU Parsers

ICU Parser is designed for:
- Static analysis (not runtime)
- PHP 8.2+ with modern typing
- Clear AST structure
- Visitor pattern for extensibility

Other parsers may focus on runtime formatting or different language ecosystems.

## Getting Help

- Issues and bug reports: <https://github.com/yoeunes/icu-parser/issues>
- Documentation: See the `docs/` directory
- Examples: See `tests/` for usage patterns

---

Previous: [Main README](../README.md) | Next: [Usage Guide](usage.md)
