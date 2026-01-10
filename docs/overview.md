## Overview

This project provides a parser and validator for ICU MessageFormat strings in PHP. It focuses on correctness, clear AST modeling, and predictable validation rather than full runtime formatting.

### Scope

- Parse MessageFormat strings into an AST.
- Provide visitors for inspection, type inference, and semantic validation.
- Validate common pattern mistakes in number/date/time styles.
- Support plural/select/selectordinal/choice constructs, including `#` handling.

### Non-goals (for now)

- Runtime message formatting. For actual formatting, use `MessageFormatter` from `ext-intl`.
- Complete validation of every ICU edge case or locale-specific rule nuance.
- MessageFormat 2 (MF2) support.

### Optional dependencies

- `symfony/intl` improves locale fallback and plural category detection. The library works without it and falls back to a small internal rule set.

If you need capabilities beyond this scope, consider pairing this library with `ext-intl` or using ICU tooling directly.
