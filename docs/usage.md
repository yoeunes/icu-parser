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

### CLI

The CLI is useful for linting translation catalogs.

```
bin/icu lint translations/
```

Use `bin/icu help` to see available commands.
