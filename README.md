# icu-parser

ICU MessageFormat parser and validator for PHP. This library focuses on fast parsing, a clean AST, and pragmatic validation rather than runtime formatting.

## Features

- Lexer + parser that produces a structured AST for ICU MessageFormat.
- Visitors (AST dumper, type inference, semantic validation).
- Plural/select/selectordinal/choice support with `#` handling.
- Basic number/date/time pattern validation for common mistakes.
- Pretty formatter for normalizing ICU messages for readability.
- Syntax highlighting for ICU message strings.
- CLI helpers for linting, auditing, and highlighting translation catalogs.

## Installation

```
composer require yoeunes/icu-parser
```

Optional (improves locale fallback and plural category detection):

```
composer require symfony/intl
```

## Usage

Parse a message:

```php
use IcuParser\IcuParser;

$parser = new IcuParser();
$ast = $parser->parse('Hello {name}, you have {count, plural, one {# item} other {# items}}');
```

Infer parameter types:

```php
$types = $parser->infer('{count, number} {date, date}');
```

Validate semantics:

```php
use IcuParser\Validation\SemanticValidator;

$validator = new SemanticValidator();
$result = $validator->validate($ast, 'Hello {name}', 'en');
```

Reformat a message for readability:

```php
$pretty = $parser->format('{gender, select, male {He} other {They}}');
```

Highlight a message:

```php
use IcuParser\Highlight\HighlightTheme;

$highlighted = $parser->highlight('{count, number}', HighlightTheme::ansi());
```

## Documentation

- `docs/overview.md`
- `docs/usage.md`
- `docs/icu-support.md`
- `docs/formatting.md`

## Notes

- This library does not format messages at runtime. It only reformats ICU strings for readability. For runtime formatting, use `MessageFormatter` from `ext-intl`.
- Pattern validation is intentionally conservative and may not catch every ICU edge case.
