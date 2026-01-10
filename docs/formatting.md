# Formatting and Highlighting

This document explains how ICU Parser formats and highlights ICU MessageFormat strings. It is written for developers who want to normalize message formatting or improve readability.

## What This Does

ICU Parser can:
- **Format:** Normalize ICU messages for consistent spacing and structure
- **Highlight:** Colorize ICU messages for better readability

It does **not**:
- Evaluate messages (runtime formatting is done by `ext-intl`)
- Change message meaning
- Optimize messages for performance

## Pretty Formatting

The formatter rewrites a message using consistent spacing and structure. This is useful for:

- Normalizing translation files
- Improving code readability
- Reviewing complex messages
- Catching syntax errors

### Basic Example

```php
use IcuParser\IcuParser;

$parser = new IcuParser();

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

### Nested Formatting

The formatter handles nested structures with appropriate indentation:

```php
$message = '{count, plural, one {You have # item in your {folder}.} other {You have # items in your {folder}.}}';
$pretty = $parser->format($message);
```

Output:

```
{count, plural,
    one {You have # item in your {folder}.}
    other {You have # items in your {folder}.}
}
```

### Escaping

The formatter escapes special characters to keep the formatted string valid:

- `{` and `}` are escaped when they appear as literals
- `#` in plural/selectordinal contexts is preserved as the special placeholder
- Single quotes are used to escape when needed

```php
$message = '{count, plural, one {You have # item.} other {You have # items.}}';
$pretty = $parser->format($message);
// Escaping is preserved where needed
```

### Format Options

`FormatOptions` lets you tune formatting:

```php
use IcuParser\Formatter\FormatOptions;

$options = new FormatOptions(
    indent: '  ',           // Indentation string (default: four spaces)
    lineBreak: "\n",         // Line separator (default: "\n")
    alignSelectors: true,   // Pad option selectors to same width (default: true)
);

$pretty = $parser->format($message, $options);
```

## Syntax Highlighting

Highlighting colorizes ICU tokens for better visual inspection. This is useful for:

- Documentation
- Code reviews
- Teaching ICU syntax
- Debugging complex messages

### ANSI Highlighting

For terminal output:

```php
use IcuParser\Highlight\HighlightTheme;

$highlighted = $parser->highlight(
    '{count, plural, one {# item} other {# items}}',
    HighlightTheme::ansi()
);

echo $highlighted; // Colorized output
```

### HTML Highlighting

For web display, use the HTML visitor (or the CLI with `--format=html`):

```php
use IcuParser\NodeVisitor\HtmlHighlighterVisitor;

$ast = $parser->parse('{count, plural, one {# item} other {# items}}');
$html = $ast->accept(new HtmlHighlighterVisitor());

echo "<div class='icu-message'>$html</div>";
```

### Plain (no ANSI)

If you want raw text without ANSI colors:

```php
use IcuParser\Highlight\HighlightTheme;

$plain = $parser->highlight('{name}', HighlightTheme::plain());
```

## YAML Integration

When storing ICU messages in YAML, consider using a folded block scalar (`>-`) to avoid introducing literal newlines:

```yaml
greeting: >-
  {gender, select,
    male {He}
    female {She}
    other {They}
  }
```

This preserves the formatted message without adding extra newlines to the rendered output.

## Limitations

Formatting and highlighting have some limitations:

### Formatting

- **Whitespace normalization:** The formatter may change whitespace. It is intended for authoring and review, not round-tripping.
- **No semantic changes:** The formatter does not optimize or change message meaning.
- **Escape handling:** The formatter may add quotes compared to the original input, but the message remains equivalent.

### Highlighting

- **Visual only:** Highlighting is for visual inspection. It does not change message content.
- **Theme dependent:** Colors depend on the terminal theme or CSS for HTML.

## When to Use Formatting

Use formatting when you need to:

- Normalize translation files for consistency
- Improve readability of complex messages
- Review messages for syntax errors
- Generate documentation
- Teach ICU syntax to team members

## When NOT to Use Formatting

Don't use formatting when you need to:

- Preserve exact whitespace in messages (e.g., for alignment)
- Optimize messages for performance (formatting doesn't change performance)
- Round-trip messages without any changes

## Practical Examples

### Normalizing a Translation File

```php
function normalizeTranslations(array $messages): array
{
    $parser = new IcuParser();
    $normalized = [];

    foreach ($messages as $key => $message) {
        try {
            $normalized[$key] = $parser->format($message);
        } catch (Exception $e) {
            $normalized[$key] = $message; // Keep original if parse fails
        }
    }

    return $normalized;
}

$messages = [
    'greeting' => '{gender,select,male{He}female{She}other{They}}',
];

$normalized = normalizeTranslations($messages);
```

### Generating Documentation

```php
use IcuParser\IcuParser;

function generateDocs(array $messages): string
{
    $parser = new IcuParser();
    $docs = "";

    foreach ($messages as $key => $message) {
        $pretty = $parser->format($message);

        $docs .= "### $key\n\n";
        $docs .= "```\n$pretty\n```\n\n";
    }

    return $docs;
}
```

### Debugging Complex Messages

```php
use IcuParser\Highlight\HighlightTheme;
use IcuParser\IcuParser;

function debugMessage(string $message): void
{
    $parser = new IcuParser();

    echo "Original:\n$message\n\n";
    echo "Formatted:\n" . $parser->format($message) . "\n\n";
    echo "Highlighted:\n" . $parser->highlight($message, HighlightTheme::ansi()) . "\n";
}

debugMessage('{count, plural, one {# item} other {# items}}');
```

## Performance

- Formatting is fast and suitable for processing many messages
- Highlighting adds minimal overhead
- Consider caching formatted messages if used repeatedly

## Getting Help

- Issues and bug reports: <https://github.com/yoeunes/icu-parser/issues>
- Documentation: See the `docs/` directory
- Examples: See `tests/` for usage patterns

---

Previous: [Usage](usage.md) | Next: [ICU Support](icu-support.md)
