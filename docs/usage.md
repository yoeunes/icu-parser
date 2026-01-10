## Usage

### Parse a message

```php
use IcuParser\IcuParser;

$parser = new IcuParser();
$ast = $parser->parse('Hello {name}');
```

### Infer parameter types

```php
$types = $parser->infer('{count, number} {date, date}');
```

### Validate semantics

```php
use IcuParser\Validation\SemanticValidator;

$validator = new SemanticValidator();
$result = $validator->validate($ast, 'Hello {name}', 'en');

if ($result->hasErrors()) {
    foreach ($result->getErrors() as $error) {
        echo $error->getMessage().PHP_EOL;
    }
}
```

### Reformat a message

```php
$pretty = $parser->format('{gender, select, male {He} other {They}}');
```

### Highlight a message

```php
use IcuParser\Highlight\HighlightTheme;

$highlighted = $parser->highlight('{count, number}', HighlightTheme::ansi());
```

### CLI

The CLI is useful for linting translation catalogs.

```
bin/icu lint translations/
```

Highlight a message directly:

```
bin/icu highlight '{count, plural, one {# item} other {# items}}'
```

Use `bin/icu help` to see available commands.
