## Formatting and Highlighting

This project can reformat ICU MessageFormat strings to make them easier to read and review. It does not evaluate messages or format runtime output.

### Pretty formatting

The formatter rewrites a message using consistent indentation and one option per line for select/plural/selectordinal blocks.

Example:

```
{organizer_gender, select,
    female   {{organizer_name} has invited you to her party!}
    male     {{organizer_name} has invited you to his party!}
    multiple {{organizer_name} have invited you to their party!}
    other    {{organizer_name} has invited you to their party!}
}
```

If you store messages in YAML, consider using a folded block scalar (`>-`) to avoid introducing literal newlines in the rendered output.

The formatter escapes special characters (`{`, `}`, and `#` in plural contexts) to keep the formatted string valid. That means it may add extra quotes compared to the original input, but the message remains equivalent.

### Highlighting

Highlighting colors ICU tokens (braces, identifiers, numbers) to help with visual inspection. Use `HighlightTheme::ansi()` for ANSI output or `HighlightTheme::plain()` to keep the original string.

### Options

`FormatOptions` lets you tune formatting:

- `indent`: the indentation string used for nested options (default: four spaces).
- `lineBreak`: line separator to use (default: `\n`).
- `alignSelectors`: whether to pad option selectors to the same width (default: `true`).

### Limitations

- Pretty formatting normalizes whitespace and may add line breaks; it is intended for authoring and review, not round-tripping.
- Semantic validation is separate. Use `SemanticValidator` if you need to catch ICU mistakes.
