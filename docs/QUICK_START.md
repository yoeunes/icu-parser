# Quick Start Guide

This guide gets you from installation to a first analysis in a few steps. It is intentionally brief; the [Usage Guide](usage.md) covers features in depth.

If you are new to ICU MessageFormat, start here and follow the examples. If you already know ICU, you can jump to [Advanced Features](#advanced-features).

## What this guide covers

- Install ICU Parser.
- Use the CLI for quick analysis.
- Parse and validate messages in PHP.
- Infer parameter types.
- Highlight and format messages.
- Lint translation catalogs.

## Installation

```bash
composer require yoeunes/icu-parser
```

Optional (improves locale support):

```bash
composer require symfony/intl
```

Requires PHP 8.2+ and the `ext-intl` extension (Composer will warn if it is missing).

## How ICU Parser works (short version)

- The lexer produces a token stream.
- The parser builds an AST.
- Visitors walk the AST to validate, infer types, highlight, or format.

You do not need these details to use the API. For background, see [Architecture](ARCHITECTURE.md).

## CLI quick start

The CLI gives you direct feedback. Try these commands:

```bash
# 1. Parse and validate a message
bin/icu debug "{count, plural, one {# item} other {# items}}"

# 2. Highlight a message for readability
bin/icu highlight "{name}, you have {count, number} items"

# 3. Lint translation files
bin/icu lint translations/

# 4. Audit translation catalogs (syntax, semantics, consistency, usage)
bin/icu audit translations/

# 5. Debug a message (AST + inferred parameter types)
bin/icu debug "{count, number} {date, date}"
```

Example output (trimmed; exact fields may evolve):
```
$ bin/icu debug "{count, plural, one {# item} other {# items}}"
AST
{
  "type": "Message",
  "parts": [
    {
      "type": "Plural",
      "name": "count",
      "options": [
        { "type": "Option", "selector": "one", ... },
        { "type": "Option", "selector": "other", ... }
      ]
    }
  ]
}

Parameters
  count : number
```

## PHP API: five essential operations

### 1. Parse a message (turn ICU string into structured data)

```php
use IcuParser\IcuParser;

$parser = new IcuParser();
$ast = $parser->parse('Hello {name}, you have {count, plural, one {# item} other {# items}}');
```

Use when you need to understand or analyze message structure.

### 2. Infer parameter types (extract what parameters the message expects)

```php
$types = $parser->infer('{count, number} {date, date, short}')->all();
// Returns: ['count' => ParameterType::NUMBER, 'date' => ParameterType::DATETIME]
```

`infer()` returns a `TypeMap`; call `->all()` when you want a simple name → `ParameterType` array.
Use `$type->value` if you want plain strings.

Use when generating API documentation or validating input.

### 3. Validate a message (check for syntax and semantic errors)

```php
use IcuParser\Validation\SemanticValidator;

$validator = new SemanticValidator();
$result = $validator->validate($ast, 'Hello {name}', 'en');

if (!$result->isValid()) {
    foreach ($result->getErrors() as $error) {
        echo "Error: " . $error->getMessage() . "\n";
    }
}
```

Checks performed:
- Syntax errors (unclosed braces, missing commas)
- Missing plural categories
- Duplicate select options
- Empty message variants

### 4. Format a message (normalize for readability)

```php
$pretty = $parser->format('{gender,select,male{He}other{They}}');
// Output:
// {gender, select,
//     male {He}
//     other {They}
// }
```

Use when normalizing translation files or improving code readability.

### 5. Highlight a message (colorize for better readability)

```php
use IcuParser\Highlight\HighlightTheme;

$highlighted = $parser->highlight('{count, plural, one {# item} other {# items}}', HighlightTheme::ansi());
echo $highlighted;
```

Use when documenting messages, doing code reviews, or teaching ICU.

## Practical use cases

### 1. Parse and Understand Complex Messages

```php
$message = '{count, plural, =0 {No items} =1 {One item} other {# items}}';
$ast = $parser->parse($message);

// Now you can analyze the message structure
```

### 2. Validate User-Submitted Messages

```php
$userMessage = $_POST['icu_message'];
$validator = new SemanticValidator();

try {
    $ast = $parser->parse($userMessage);
    $result = $validator->validate($ast, $userMessage, 'en');

    if (!$result->isValid()) {
        die("Invalid message: " . $result->getErrors()[0]->getMessage());
    }
} catch (Exception $e) {
    die("Parse error: " . $e->getMessage());
}
```

### 3. Extract Parameters for API Documentation

```php
function extractParameters(string $message): array
{
    $parser = new IcuParser();
    $types = $parser->infer($message)->all();

    $parameters = [];
    foreach ($types as $name => $type) {
        $parameters[] = [
            'name' => $name,
            'type' => $type->value,
            'required' => true,
        ];
    }

    return $parameters;
}

$params = extractParameters('Hello {name}, you have {count} items');
// Returns: [['name' => 'name', 'type' => 'string', 'required' => true], ...]
```

### 4. Lint Translation Files

```bash
# Scan your entire project
bin/icu lint translations/

# Audit catalog consistency and usage issues
bin/icu audit translations/
```

### 5. Normalize Translation Files

```php
function normalizeTranslations(array $messages): array
{
    $parser = new IcuParser();
    $normalized = [];

    foreach ($messages as $key => $message) {
        try {
            $normalized[$key] = $parser->format($message);
        } catch (Exception $e) {
            $normalized[$key] = $message;
        }
    }

    return $normalized;
}

$messages = [
    'greeting' => '{gender,select,male{He}other{They}}',
];

$normalized = normalizeTranslations($messages);
// $normalized['greeting'] = "{gender, select,\n    male {He}\n    other {They}\n}"
```

## Common message examples

### Simple placeholder

```php
$message = 'Hello {name}';
$ast = $parser->parse($message);
```

### Number formatting

```php
$message = 'Balance: {balance, number, currency}';
$ast = $parser->parse($message);
```

### Date formatting

```php
$message = 'Today is {date, date, long}';
$ast = $parser->parse($message);
```

### Plural rules

```php
$message = '{count, plural, one {# item} other {# items}}';
$ast = $parser->parse($message);
```

### Select rules

```php
$message = '{gender, select, male {He} female {She} other {They}}';
$ast = $parser->parse($message);
```

### Nested messages

```php
$message = '{count, plural, one {You have # item in your {folder}.} other {You have # items in your {folder}.}}';
$ast = $parser->parse($message);
```

## ⚠️ Error Handling

```php
use IcuParser\Exception\ParserException;

$parser = new IcuParser();

try {
    $ast = $parser->parse('{unclosed');
} catch (ParserException $e) {
    echo "Parse error: " . $e->getMessage() . "\n";
    echo "Position: " . $e->getPosition() . "\n";
    echo "Snippet:\n" . $e->getSnippet() . "\n";
}
```

## ⚡ Performance Tips

1. **Parse Once, Reuse AST:** Don't re-parse the same message repeatedly.
2. **Validate Early:** Check messages during development, not in production.
3. **Cache Results:** Store parsed ASTs and validation results.
4. **Reuse Parser Instance:** Create one `IcuParser` instance and reuse it.
5. **Use the CLI for Bulk Operations:** The CLI is optimized for processing many files.

## Advanced features

### Custom validation rules

```php
use IcuParser\Node\FormattedArgumentNode;
use IcuParser\NodeVisitor\AbstractNodeVisitor;
use IcuParser\Validation\SemanticValidator;

class CustomValidator extends AbstractNodeVisitor
{
    public function visitFormattedArgument(FormattedArgumentNode $node): void
    {
        if ($node->format === 'number' && $node->style === 'currency') {
            // Custom validation for currency formatting
        }
    }
}

$ast = $parser->parse('{price, number, currency}');
$ast->accept(new CustomValidator());
```

### Working with locales

```php
$validator = new SemanticValidator();

// Validate with English plural rules
$result = $validator->validate($ast, 'Hello {name}', 'en');

// Validate with Arabic plural rules
$result = $validator->validate($ast, 'مرحبا {name}', 'ar');
```

### Symfony Intl integration

```bash
composer require symfony/intl
```

With `symfony/intl` installed, ICU Parser uses accurate locale data for plural categories and other locale-specific rules.

## Next steps

Now that you've seen what ICU Parser can do, here's where to go next:

For beginners:
- [Usage Guide](usage.md) - Detailed API usage
- [Overview](overview.md) - Scope and capabilities

For users:
- [ICU Support](icu-support.md) - Supported ICU constructs
- [CLI Reference](../README.md#cli-quick-tour) - Command reference

For developers:
- [Architecture](ARCHITECTURE.md) - Internal design
- [Overview](overview.md) - Architecture details

## Getting help

- Issues and bug reports: <https://github.com/yoeunes/icu-parser/issues>
- Real-world examples: See `tests/` for usage patterns
- Documentation: See the `docs/` directory

---

Previous: [Overview](overview.md) | Next: [Usage Guide](usage.md)
