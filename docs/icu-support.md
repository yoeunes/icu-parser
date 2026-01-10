## ICU Support

This library parses ICU MessageFormat syntax and validates common mistakes. It aims to be correct for mainstream usage while remaining lightweight.

### Supported constructs

- Arguments: `{name}`, `{0}`
- Formats: `number`, `date`, `time`, `spellout`, `ordinal`, `duration`
- Select: `{gender, select, male {...} other {...}}`
- Plural: `{count, plural, one {...} other {...}}` with `#`
- Selectordinal: `{n, selectordinal, one {...} other {...}}` with `#`
- Choice (legacy): `{value, choice, 0#none|1<one}`

### Validation behavior

- Detects missing `other` in select/plural/selectordinal.
- Detects duplicate selectors and empty option messages.
- Validates number/date/time patterns for common mistakes.

### Locale data

- With `symfony/intl`, plural categories and locale fallbacks use ICU data.
- Without it, the library uses a small internal rule set and a simple locale fallback.

If you discover a gap between ICU behavior and this parser, please open an issue with a minimal example.
