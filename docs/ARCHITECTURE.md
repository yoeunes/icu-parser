# ICU Parser Architecture

This document explains how ICU Parser works under the hood. It is written for future maintainers and contributors who want to understand the AST, the parsing pipeline, and the analysis algorithms.

## Pipeline Overview

ICU Parser treats an ICU MessageFormat string as structured input:

- The lexer scans the message and produces a token stream.
- The parser builds an AST (`MessageNode`) from tokens.
- Visitors walk the AST to validate, infer types, highlight, or format.

```
Message String → Lexer → Token Stream → Parser → AST → Visitors → Output
```

## Step 1: Lexer (Tokenization)

`src/Lexer/Lexer.php` scans the ICU message string and emits tokens with start/end positions.

The lexer is a single-pass scanner. It emits punctuation tokens (such as `{`, `}`, `,`, `:`, `#`, `=`, `|`, `<`), identifiers, numbers, whitespace, and text. It does **not** try to interpret nesting; the parser handles structure.

### Token Types

The lexer produces tokens for:

- Text literals
- Identifiers and numbers
- Whitespace
- Punctuation (`{`, `}`, `,`, `:`, `#`, `=`, `|`, `<`)

Format keywords like `plural` or `select` are emitted as identifiers; the parser interprets them based on position and context.

The lexer output is a linear sequence of `Token` objects. Tokens are positional, and offsets are character-based so diagnostics line up with the original string.

### Lexer Contexts

Quoted literals use ICU-style single quotes to escape special characters (for example, `'{` or `'}'`). Unterminated quoted literals raise a `LexerException`.

## Step 2: Parser (Recursive Descent)

`src/Parser/Parser.php` is a handwritten recursive descent parser. It walks the `TokenStream` and builds an AST that reflects ICU MessageFormat structure.

The parser entry point is `parse()`, which delegates to smaller methods such as:

- `parseMessage()`
- `parseArgument()`
- `parseComplexArgument()`
- `parseChoiceArgument()`

### Parser Precedence and Flow

The parser implements precedence by control flow:

- `parseMessage()` builds the root `MessageNode` from a sequence of text tokens and arguments.
- `parseArgument()` handles both simple placeholders `{name}` and formatted arguments `{name, type, ...}`.
- `parseComplexArgument()` parses `select`, `plural`, and `selectordinal` blocks.
- `parseChoiceArgument()` parses choice format blocks.

Errors raised here become `ParserException` and include character offsets for diagnostics.

### AST Structure

Every node:

- Is immutable (`readonly`)
- Holds `startPosition` and `endPosition` character offsets
- Implements `NodeInterface::accept()`

The root node is `MessageNode`, which wraps a sequence of child nodes.

Example AST shape:

```
Message: "Hello {name}, you have {count, plural, one {# item} other {# items}}"

MessageNode
├── TextNode("Hello ")
├── SimpleArgumentNode("name")
├── TextNode(", you have ")
└── PluralNode("count")
    ├── OptionNode(selector: "one")
    │   └── MessageNode
    │       ├── PoundNode
    │       └── TextNode(" item")
    └── OptionNode(selector: "other")
        └── MessageNode
            ├── PoundNode
            └── TextNode(" items")
```

Node definitions live in `src/Node/`. The full node reference includes:

- `MessageNode`: Root node, contains a sequence of child nodes
- `TextNode`: Literal text content
- `SimpleArgumentNode`: Simple placeholder `{name}`
- `FormattedArgumentNode`: Formatted argument `{name, type, ...}`
- `PluralNode`: Plural format structure
- `SelectNode`: Select format structure
- `SelectOrdinalNode`: Selectordinal format structure
- `ChoiceNode`: Choice format structure
- `OptionNode`: Option key-value pair (e.g., `one {# item}`)
- `PoundNode`: `#` placeholder in plural/selectordinal

## Step 3: Visitors and Traversal

Visitors encapsulate behavior. Each node calls the correct method on the visitor, enabling double-dispatch:

```
$node->accept($visitor)
  -> $visitor->visitXxx($node)
```

### Base Visitor

`src/NodeVisitor/AbstractNodeVisitor.php` provides the base visitor pattern:

- Each node type has a corresponding `visitXxx()` method
- Default implementation visits child nodes recursively
- Concrete visitors override methods to implement specific behavior

### Built-in Visitors

Built-in visitors live in `src/NodeVisitor/` and include:

- `AstDumper`: Converts the AST to an array representation (for JSON/debugging)
- `ConsoleHighlighterVisitor` / `HtmlHighlighterVisitor`: Syntax highlighting
- `PrettyPrintVisitor`: Pretty formatting (used by `PrettyFormatter`)

Other visitors live in their respective namespaces:

- `TypeInferer` (`src/Type/TypeInferer.php`): Extracts parameter types
- `SemanticValidator` (`src/Validation/SemanticValidator.php`): Validates semantics

### Visitor Traversal

Visitors walk the AST depth-first, processing each node in order. This allows for:

- Collecting information (e.g., parameter names, types)
- Validating constraints (e.g., required plural categories)
- Rendering output (e.g., formatting, highlighting)

## Step 4: Type Inference

Type inference extracts parameter types from messages using the `TypeInferer` visitor.

The visitor walks the AST and records:

- Simple placeholders are inferred as `string` type
- Formatted arguments with `number` type are inferred as `number`
- Formatted arguments with `date`/`time` types are inferred as `datetime`
- Formatted arguments with `plural`/`selectordinal`/`choice` types are inferred as `number`

Example:

```php
$message = '{name} has {count, number} items worth {total, number, currency}';
$types = $parser->infer($message)->all();
// Returns: ['name' => ParameterType::STRING, 'count' => ParameterType::NUMBER, 'total' => ParameterType::NUMBER]
```

## Step 5: Semantic Validation

Semantic validation checks for common errors in ICU messages using the `SemanticValidator` visitor.

### Validation Checks

The validator checks for:

- **Duplicate options:** Multiple options with the same keyword in select/plural
- **Missing options:** Required categories missing for the locale (e.g., `other` in plural)
- **Empty messages:** Message variants that contain no text
- **Invalid format patterns:** Basic validation of number/date/time patterns
- **Argument usage:** Tracks which arguments are used in the message

### Locale-Specific Validation

The validator uses locale-specific rules when available:

- Plural categories vary by locale (e.g., English has `one` and `other`, Arabic has `zero`, `one`, `two`, `few`, `many`, `other`)
- With `symfony/intl` installed, the library uses full ICU locale data
- Without `symfony/intl`, it uses a small internal rule set

Example:

```php
// English plural rules
$message = '{count, plural, one {# item} other {# items}}';
$result = $validator->validate($ast, $message, 'en');
// Valid

// Arabic plural rules (requires more categories)
$message = '{count, plural, one {# عنصر} other {# عناصر}}';
$result = $validator->validate($ast, $message, 'ar');
// Error: Missing required plural categories: "zero", "two", "few", "many"
```

## Step 6: Formatting and Highlighting

### Pretty Formatting

The formatter normalizes ICU messages for consistent formatting:

- Adds spacing around structure elements (commas, braces)
- Normalizes keyword spacing
- Ensures consistent indentation for nested structures

`PrettyFormatter` delegates to `PrettyPrintVisitor`, which renders formatted output from the AST.

Example:

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

### Syntax Highlighting

Highlighting colorizes ICU messages for better readability:

- ANSI highlighting for terminal output (used by `IcuParser::highlight()` and the CLI)
- HTML highlighting via `HtmlHighlighterVisitor` (used by the CLI `highlight --format=html`)

## CLI Architecture

The CLI (`src/Cli/`) provides a command-line interface for common operations.

### Command Structure

All commands implement `CommandInterface`:

- `LintCommand`: Validate ICU messages in YAML/XLIFF files
- `AuditCommand`: Analyze translation catalogs
- `DebugCommand`: Show AST and type info
- `HighlightCommand`: Syntax highlighting
- `FormatCommand`: Pretty formatting (with optional ANSI highlighting)
- `HelpCommand`, `VersionCommand`

### Global Options

`GlobalOptionsParser` handles:

- ANSI control (disable for non-terminal output)
- Quiet mode
- Visual banners/sections

### Translation Catalogs

The CLI works with translation catalogs through:

- `TranslationLoader`: Scans directories for ICU messages
- `Extractors`: YAML and XLIFF format support
- `Catalog`: In-memory translation representation
- Optional file-based caching (when a cache directory is provided)

## Error Handling and Diagnostics

Errors include position and visual snippets showing the error location:

```php
$parser->parse('{unclosed');
// Error: ...
// Position: ...
// Snippet:
//   {unclosed
//           ^
```

This makes errors easy to identify and fix, especially in large messages.

## Performance Considerations

### Caching

Translation catalogs can be cached when you pass a cache directory to `TranslationLoader`:

- File-based cache under the provided directory
- Cache key includes file mtimes, cache version, and defaults
- Disabled by default unless you provide a cache path

### Optimization Strategies

1. **Lexer:** Single-pass scanning with small token types
2. **Parser:** Handwritten recursive descent for predictability
3. **Visitors:** Single-pass traversal where possible

## Extension Points

When adding new ICU constructs, you typically update:

- `src/Node/*` to define a node
- `src/Parser/Parser.php` and `src/Lexer/Lexer.php` to recognize syntax
- `src/NodeVisitor/*` to support traversal
- Tests and fixtures for valid/invalid cases

### Adding a New Format Type

1. Add token types to `Lexer` if needed
2. Add parser logic in `Parser::parseFormattedArgument()`
3. Create node class in `src/Node/`
4. Add visitor method to `AbstractNodeVisitor`
5. Add validation logic to `SemanticValidator` if needed
6. Add tests for valid and invalid cases

## Testing Structure

Tests mirror the `src` structure:

- `tests/Lexer/`: Lexer tests
- `tests/Parser/`: Parser tests
- `tests/NodeVisitor/`: Visitor tests
- `tests/Validation/`: Validation tests
- `tests/Cli/`: CLI command tests

Test fixtures are in `tests/Fixtures/`.

## Design Principles

1. **Immutability:** Many classes are `readonly` to avoid accidental mutation
2. **Strict Types:** All code uses `declare(strict_types=1)`
3. **Visitor Pattern:** For AST traversal and analysis
4. **Error Context:** Exceptions include position and visual snippets
5. **Separation of Concerns:** Lexer, parser, and visitors are independent

## Future Enhancements

Potential areas for improvement:

- Support for MessageFormat 2 (MF2)
- More comprehensive pattern validation
- Additional output formats (JSON, XML)
- More advanced semantic analysis
- Integration with more translation management systems

---

Previous: [Overview](overview.md) | Next: [Usage Guide](usage.md)
