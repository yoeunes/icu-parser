# ICU Parser Documentation

This documentation covers ICU MessageFormat fundamentals and how to use ICU Parser in PHP projects. It is written for both newcomers and experienced developers.

Start here:
- [Quick Start](QUICK_START.md) - Get started in a few minutes
- [Overview](overview.md) - Scope and capabilities
- [Usage Guide](usage.md) - Detailed API usage

## Documentation map

### Learning path (beginners)

- [Quick Start](QUICK_START.md) - Get started in a few steps.
- [Overview](overview.md) - What ICU Parser does and doesn't do.
- [Usage Guide](usage.md) - Detailed API usage and examples.

### Using ICU Parser

- [Formatting and Highlighting](formatting.md) - Pretty formatting and syntax highlighting.
- [ICU Support](icu-support.md) - Supported ICU constructs and validation.

### For developers and contributors

- [Architecture](ARCHITECTURE.md) - Internal design and pipeline.
- [Quick Start](QUICK_START.md) - Get started with the API.

### Reference materials

- [Overview](overview.md) - Scope, limitations, and transparency.
- [ICU Support](icu-support.md) - Supported constructs and validation behavior.

## How ICU Parser works in brief

ICU Parser treats an ICU MessageFormat string as structured input:

- The lexer produces a token stream.
- The parser builds an AST.
- Visitors walk the AST to validate, infer types, highlight, or format.

Every example in these docs uses ICU Parser as the reference implementation.

## Tips for newcomers

- Validate messages during development or CI before using them in production.
- Use the CLI to lint translation files.
- Install `symfony/intl` for better locale support.
- Use `SemanticValidator` to catch common errors.
- Use `format()` to normalize message formatting.

## Getting help

- Issues and bug reports: <https://github.com/yoeunes/icu-parser/issues>
- Real-world examples: See `tests/` for usage patterns
- Interactive examples: Use the CLI to experiment

## Key concepts

- [What is an AST?](ARCHITECTURE.md#step-2-parser-recursive-descent)
- [Visitors](ARCHITECTURE.md#step-3-visitors-and-traversal)
- [Validation](overview.md#2-validation)
- [Type Inference](ARCHITECTURE.md#step-4-type-inference)

---

Previous: [Main README](../README.md) | Next: [Quick Start](QUICK_START.md)
