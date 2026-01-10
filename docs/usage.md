# ICU Parser Usage Guide

This guide shows you how to use ICU Parser in PHP projects. It includes practical examples, error handling, and common use cases.

If you are new to ICU Parser, start with the [Quick Start](QUICK_START.md) guide.

## Table of Contents

- [Basic Operations](#basic-operations)
- [Parsing Messages](#parsing-messages)
- [Type Inference](#type-inference)
- [Validation](#validation)
- [Formatting and Highlighting](#formatting-and-highlighting)
- [Error Handling](#error-handling)
- [CLI Usage](#cli-usage)
- [Practical Use Cases](#practical-use-cases)

## Basic Operations

### Parse a message

```php
use IcuParser\IcuParser;

$parser = new IcuParser();
$ast = $parser->parse('Hello {name}, you have {count, plural, one {# item} other {# items}}');
```

The AST is a structured representation of the message that you can traverse, validate, or transform.

### Infer parameter types

```php
$types = $parser->infer('{count, number} {date, date, short}')->all();
// Returns: ['count' => ParameterType::NUMBER, 'date' => ParameterType::DATETIME]
```

This tells you what parameters the message expects and their types.

### Validate semantics

```php
use IcuParser\Validation\SemanticValidator;

$validator = new SemanticValidator();
$result = $validator->validate($ast, 'Hello {name}', 'en');

if (!$result->isValid()) {
    foreach ($result->getErrors() as $error) {
        echo $error->getMessage() . "\n";
    }
}
```

### Reformat a message

```php
$pretty = $parser->format('{gender,select,male{He}other{They}}');
// Output:
// {gender, select,
//     male {He}
//     other {They}
// }
```

### Highlight a message

```php
use IcuParser\Highlight\HighlightTheme;

$highlighted = $parser->highlight('{count, plural, one {# item} other {# items}}', HighlightTheme::ansi());
echo $highlighted; // Colorized output
```

## Parsing Messages

### Simple placeholder

```php
$ast = $parser->parse('Hello {name}');
```

### Number formatting

```php
$ast = $parser->parse('Balance: {balance, number, currency}');
```

### Date formatting

```php
$ast = $parser->parse('Today is {date, date, long}');
```

### Plural rules

```php
$ast = $parser->parse('{count, plural, one {# item} other {# items}}');
```

### Select rules

```php
$ast = $parser->parse('{gender, select, male {He} female {She} other {They}}');
```

### Selectordinal rules

```php
$ast = $parser->parse('{position, selectordinal, one {#st} two {#nd} few {#rd} other {#th}}');
```

### Choice rules

```php
$ast = $parser->parse('{age, choice, 0#Baby|18#Adult|65#Senior}');
```

### Nested messages

```php
$ast = $parser->parse('{count, plural, one {You have # item.} other {You have # items.}}');
```

## Type Inference

Type inference extracts parameter types from messages:

```php
// Simple inference
$types = $parser->infer('Hello {name}')->all();
// Returns: ['name' => ParameterType::STRING]

// Number type
$types = $parser->infer('{price, number}')->all();
// Returns: ['price' => ParameterType::NUMBER]

// Date type
$types = $parser->infer('{date, date, short}')->all();
// Returns: ['date' => ParameterType::DATETIME]

// Plural implies number
$types = $parser->infer('{count, plural, one {# item} other {# items}}')->all();
// Returns: ['count' => ParameterType::NUMBER]

// Multiple parameters
$types = $parser->infer('{name} has {count} items worth {total, number, currency}')->all();
// Returns: ['name' => ParameterType::STRING, 'count' => ParameterType::NUMBER, 'total' => ParameterType::NUMBER]
```

`infer()` returns a `TypeMap`; use `->all()` when you want a name → `ParameterType` array.

### Practical use: API documentation

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
```

## Validation

### Basic validation

```php
use IcuParser\Validation\SemanticValidator;

$validator = new SemanticValidator();
$result = $validator->validate($ast, 'Hello {name}', 'en');

if (!$result->isValid()) {
    echo "Validation failed:\n";
    foreach ($result->getErrors() as $error) {
        echo "- {$error->getMessage()}\n";
    }
}
```

### Validation with locale

```php
// Validate with English plural rules
$result = $validator->validate($ast, 'Hello {name}', 'en');

// Validate with Arabic plural rules
$result = $validator->validate($ast, 'مرحبا {name}', 'ar');
```

### Common validation errors

Missing plural categories:

```php
$message = '{count, plural, one {# item}}';
$result = $validator->validate($ast, $message, 'en');
// Error: Missing required plural category: "other"
```

Duplicate options:

```php
$message = '{gender, select, male {He} male {Him} other {They}}';
$result = $validator->validate($ast, $message, 'en');
// Error: Duplicate option in select: "male"
```

Empty message variants:

```php
$message = '{count, plural, one {} other {# items}}';
$result = $validator->validate($ast, $message, 'en');
// Error: Empty message variant for plural category: "one"
```

### Validate user input

```php
function validateIcuMessage(string $message, string $locale = 'en'): bool
{
    $parser = new IcuParser();
    $validator = new SemanticValidator();

    try {
        $ast = $parser->parse($message);
        $result = $validator->validate($ast, $message, $locale);
        return $result->isValid();
    } catch (Exception $e) {
        return false;
    }
}
```

## Formatting and Highlighting

### Pretty formatting

Normalize ICU messages for consistency:

```php
// Before
$message = '{gender,select,male{He}female{She}other{They}}';

// After
$pretty = $parser->format($message);
// Output:
// {gender, select,
//     male {He}
//     female {She}
//     other {They}
// }
```

### Syntax highlighting

Colorize messages for documentation:

```php
use IcuParser\Highlight\HighlightTheme;
use IcuParser\NodeVisitor\HtmlHighlighterVisitor;

// ANSI coloring (for terminal)
$highlighted = $parser->highlight('{count, plural, one {# item} other {# items}}', HighlightTheme::ansi());

// HTML coloring (for web)
$ast = $parser->parse('{count, plural, one {# item} other {# items}}');
$html = $ast->accept(new HtmlHighlighterVisitor());
```

## Error Handling

### Parsing errors

```php
use IcuParser\Exception\ParserException;

try {
    $ast = $parser->parse('{unclosed');
} catch (ParserException $e) {
    echo "Parse error: " . $e->getMessage() . "\n";
    echo "Position: " . $e->getPosition() . "\n";
    echo "Snippet:\n" . $e->getSnippet() . "\n";
}
```

### Validation errors

```php
$result = $validator->validate($ast, 'Hello {name}', 'en');

if (!$result->isValid()) {
    foreach ($result->getErrors() as $error) {
        echo "Error: {$error->getMessage()}\n";
        echo "Code: {$error->getErrorCode()}\n";
        echo "Position: {$error->position}\n";
    }
}
```

### Custom error handling

```php
function safeParse(string $message): ?NodeInterface
{
    $parser = new IcuParser();

    try {
        return $parser->parse($message);
    } catch (ParserException $e) {
        // Log error or notify user
        error_log("Failed to parse message: " . $e->getMessage());
        return null;
    }
}
```

## CLI Usage

The CLI provides quick access to common operations.

### Lint translation files

```bash
# Lint all translation files
bin/icu lint translations/

# Lint specific directory
bin/icu lint translations/en/
```

### Audit translation catalogs

```bash
# Full audit (syntax, semantics, and cross-locale consistency)
bin/icu audit translations/
```

### Debug messages

```bash
# Parse and show AST
bin/icu debug "{count, plural, one {# item} other {# items}}"

```

### Highlight messages

```bash
# Highlight message in terminal
bin/icu highlight "{count, plural, one {# item} other {# items}}"

# Output HTML highlighting
bin/icu highlight "{count, plural, one {# item} other {# items}}" --format=html
```

## Practical Use Cases

### 1. Validate translation files

```php
function validateTranslations(string $directory, string $locale = 'en'): array
{
    $validator = new SemanticValidator();
    $parser = new IcuParser();
    $errors = [];

    $loader = new TranslationLoader([$directory], $locale);
    foreach ($loader->scan() as $entry) {
        try {
            $ast = $parser->parse($entry->message);
            $result = $validator->validate($ast, $entry->message, $entry->locale);

            if (!$result->isValid()) {
                $errors[$entry->id] = $result->getErrors();
            }
        } catch (ParserException $e) {
            $errors[$entry->id] = [$e->getMessage()];
        }
    }

    return $errors;
}
```

### 2. Generate API documentation

```php
function generateApiDocs(string $message): array
{
    $parser = new IcuParser();
    $types = $parser->infer($message)->all();

    $docs = [
        'message' => $message,
        'parameters' => [],
    ];

    foreach ($types as $name => $type) {
        $docs['parameters'][] = [
            'name' => $name,
            'type' => $type->value,
            'description' => ucfirst($type->value) . ' parameter',
        ];
    }

    return $docs;
}
```

### 3. Normalize translation files

```php
function normalizeTranslations(array $messages): array
{
    $parser = new IcuParser();
    $normalized = [];

    foreach ($messages as $key => $message) {
        try {
            $normalized[$key] = $parser->format($message);
        } catch (ParserException $e) {
            $normalized[$key] = $message; // Keep original if parse fails
        }
    }

    return $normalized;
}
```

### 4. Extract parameters from template

```php
function extractParametersFromTemplate(string $template): array
{
    $parser = new IcuParser();
    $types = $parser->infer($template)->all();

    return array_keys($types);
}

// Usage
$params = extractParametersFromTemplate('Hello {name}, you have {count} items');
// Returns: ['name', 'count']
```

### 5. Validate user-submitted messages

```php
function validateUserMessage(string $message): array
{
    $parser = new IcuParser();
    $validator = new SemanticValidator();

    $result = [
        'valid' => true,
        'errors' => [],
        'parameters' => [],
    ];

    try {
        $ast = $parser->parse($message);
        $validation = $validator->validate($ast, $message, 'en');

        if (!$validation->isValid()) {
            $result['valid'] = false;
            $result['errors'] = array_map(
                fn($e) => $e->getMessage(),
                $validation->getErrors()
            );
        }

        $result['parameters'] = array_keys($parser->infer($message)->all());
    } catch (ParserException $e) {
        $result['valid'] = false;
        $result['errors'][] = $e->getMessage();
    }

    return $result;
}
```

### 6. Compare parameter usage across translations

```php
function compareParameters(array $messages): array
{
    $parser = new IcuParser();
    $parameterSets = [];

    foreach ($messages as $locale => $message) {
        try {
            $parameterSets[$locale] = array_keys($parser->infer($message)->all());
        } catch (ParserException $e) {
            $parameterSets[$locale] = [];
        }
    }

    return $parameterSets;
}

// Usage
$messages = [
    'en' => 'Hello {name}, you have {count} items',
    'fr' => 'Bonjour {name}, vous avez {count} articles',
    'de' => 'Hallo {name}, Sie haben {count} Elemente',
];

$params = compareParameters($messages);
// Returns: ['en' => ['name', 'count'], 'fr' => ['name', 'count'], 'de' => ['name', 'count']]
```

## Performance Tips

1. **Reuse parser instances:** Create one `IcuParser` instance and reuse it.
2. **Cache parsed ASTs:** If you parse the same messages repeatedly, cache the results.
3. **Validate during development:** Use linting to catch errors early, not in production.
4. **Use the CLI for bulk operations:** The CLI is optimized for processing many files.
5. **Scope linting runs:** Lint only the directories you need during development to keep feedback fast.

---

Previous: [Overview](overview.md) | Next: [ICU Support](icu-support.md)
