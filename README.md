<p align="center">
    <img src="art/banner.svg?v=1" alt="ICU Parser" width="100%">
</p>

<p align="center">
    <a href="https://www.linkedin.com/in/younes--ennaji"><img src="https://img.shields.io/badge/author-@yoeunes-blue.svg" alt="Author Badge"></a>
    <a href="https://github.com/yoeunes/icu-parser/releases"><img src="https://img.shields.io/github/tag/yoeunes/icu-parser.svg" alt="GitHub Release Badge"></a>
    <a href="https://github.com/yoeunes/icu-parser/blob/main/LICENSE"><img src="https://img.shields.io/badge/license-MIT-brightgreen.svg" alt="License Badge"></a>
    <a href="https://packagist.org/packages/yoeunes/icu-parser"><img src="https://img.shields.io/packagist/dt/yoeunes/icu-parser.svg" alt="Packagist Downloads Badge"></a>
    <a href="https://github.com/yoeunes/icu-parser"><img src="https://img.shields.io/github/stars/yoeunes/icu-parser.svg" alt="GitHub Stars Badge"></a>
    <a href="https://packagist.org/packages/yoeunes/icu-parser"><img src="https://img.shields.io/packagist/php-v/yoeunes/icu-parser.svg" alt="Supported PHP Version Badge"></a>
</p>

# ICU Parser: Static Analysis, Linter & AST Generator

ICU Parser is a PHP 8.2+ library that treats ICU MessageFormat strings as code.

Unlike simple wrappers around `MessageFormatter`, ICU Parser implements a **compiler-style pipeline** (Lexer → Parser → AST) and provides **static analysis** for ICU messages.

This architecture allows for advanced tooling:
- **Parsing:** Parse ICU messages into a structured, typed AST.
- **Validation:** Detect common syntax and semantic errors.
- **Type Inference:** Extract parameter types from messages.
- **Linting:** Validate translation catalogs across multiple locales.
- **Analysis:** Syntax highlighting, pretty-formatting, and debugging.

Built for validation, analysis, and robust tooling in i18n workflows.

If you are new to ICU Parser, start with the [Quick Start Guide](docs/QUICK_START.md). If you want a short overview, see [docs/overview.md](docs/overview.md).

## Getting started

```bash
# Install the library
composer require yoeunes/icu-parser

# Optional: improve locale support
composer require symfony/intl

# Try the CLI
bin/icu lint translations/
```

Requirements: PHP 8.2+ and `ext-intl` (the `MessageFormatter` class).

## What ICU Parser provides

- 🏗️ **Deep Parsing:** Parse ICU MessageFormat strings into a structured, typed AST.
- 🔍 **Type Inference:** Extract parameter types and their expected formats from messages.
- ✅ **Validation:** Detect syntax errors, missing plural categories, and semantic issues.
- 📊 **Catalog Analysis:** Analyze translation files for consistency and completeness.
- 🎨 **Syntax Highlighting:** Colorize ICU messages for better readability.
- 🔧 **Visitor API:** A flexible API for building custom ICU analysis tools.
- 🛠️ **CLI Tooling:** Lint, audit, debug, and highlight messages from the command line.

## Philosophy & Scope

ICU Parser is a **static analysis tool**, not a runtime formatter.

### What it does

- **Parse:** Converts ICU MessageFormat strings into an AST.
- **Validate:** Checks for syntax errors and common semantic issues.
- **Analyze:** Infers types, formats messages, and highlights syntax.
- **Lint:** Scans translation files for errors and inconsistencies.

### What it does NOT do

- **Runtime Formatting:** It does not format messages at runtime. Use `MessageFormatter` from `ext-intl` for that.
- **Complete ICU Coverage:** It supports the most common ICU constructs but does not cover every edge case or locale-specific nuance.
- **MessageFormat 2 (MF2):** It targets ICU MessageFormat 1.x only.

### Validation notes

- **Well-tested behavior:** Parsing, AST structure, error offsets, and syntax validation for the supported ICU syntax.
- **Heuristic:** Semantic validation is conservative and may miss edge cases. Treat it as a helpful tool, not a complete validator.
- **Context matters:** Locale-specific rules (e.g., plural categories) vary. Use `symfony/intl` for better locale support.

## How it works

- `IcuParser::parse()` converts an ICU string into an AST.
- The lexer produces a token stream.
- The parser builds an AST (`MessageNode`).
- Visitors walk the AST to validate, infer types, highlight, or format.

For the full architecture, see [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

## CLI quick tour

```bash
# Parse and validate a message
bin/icu debug "{count, plural, one {# item} other {# items}}"

# Highlight a message
bin/icu highlight "{name}, you have {count, number} items"

# Lint translation files
bin/icu lint translations/

# Audit translation catalogs
bin/icu audit translations/
```

## PHP API at a glance

```php
use IcuParser\IcuParser;
use IcuParser\Validation\SemanticValidator;
use IcuParser\Highlight\HighlightTheme;
use IcuParser\Type\ParameterType;

$parser = new IcuParser();

// Parse a message into AST
$ast = $parser->parse('Hello {name}, you have {count, plural, one {# item} other {# items}}');

// Infer parameter types
$types = $parser->infer('{count, number} {date, date, short}')->all();
// Returns: ['count' => ParameterType::NUMBER, 'date' => ParameterType::DATETIME]

// Validate semantics
$validator = new SemanticValidator();
$result = $validator->validate($ast, 'Hello {name}', 'en');

if (!$result->isValid()) {
    foreach ($result->getErrors() as $error) {
        echo $error->getMessage() . "\n";
    }
}

// Format for readability
$pretty = $parser->format('{gender, select, male {He} other {They}}');
// Output:
// {gender, select,
//     male {He}
//     other {They}
// }

// Highlight syntax
$highlighted = $parser->highlight('{count, number}', HighlightTheme::ansi());
```

## Integrations

ICU Parser integrates with common PHP tooling:

- **Symfony bundle**: See [docs/overview.md](docs/overview.md)
- **PHPStan**: PHPStan rule (early/experimental)
- **Twig**: Twig extractor (used by the audit command; requires Twig)
- **GitHub Actions**: Use `bin/icu lint` in your CI pipeline

## Documentation

Start here:
- [Quick Start](docs/QUICK_START.md) - Get started in a few minutes
- [Overview](docs/overview.md) - Scope and capabilities
- [Usage Guide](docs/usage.md) - Detailed API usage
- [ICU Support](docs/icu-support.md) - Supported constructs

Key references:
- [Architecture](docs/ARCHITECTURE.md) - Internal design
- [Formatting](docs/formatting.md) - Formatting and highlighting

## Contributing

Contributions are welcome! See [`CONTRIBUTING.md`](CONTRIBUTING.md) to get started.

```bash
# Set up development environment
composer install

# Run tests
composer phpunit

# Check code style
composer phpcs

# Run static analysis
composer phpstan

# Run full lint (includes all of the above)
composer lint
```

## License

Released under the [MIT License](LICENSE).

## Support

If you run into issues or have questions, please open an issue on GitHub: <https://github.com/yoeunes/icu-parser/issues>.
