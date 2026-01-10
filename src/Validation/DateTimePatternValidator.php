<?php

declare(strict_types=1);

/*
 * This file is part of the IcuParser package.
 *
 * (c) Younes ENNAJI <younes.ennaji.pro@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IcuParser\Validation;

/**
 * Validates ICU date/time format patterns.
 *
 * ICU date/time pattern symbols:
 * - Era: G
 * - Year: y
 * - Quarter: Q, q
 * - Month: M, L
 * - Week: w, W
 * - Day: d, D, F, E, e, c
 * - Period: a
 * - Hour: h, H, k, K
 * - Minute: m
 * - Second: s, S, A
 * - Zone: z, Z, O, X, v, V
 */
final class DateTimePatternValidator
{
    private const VALID_SYMBOLS = [
        // Era
        'G',
        // Year
        'y', 'Y',
        // Quarter
        'Q', 'q',
        // Month
        'M', 'L',
        // Week
        'w', 'W',
        // Day
        'd', 'D', 'F', 'E', 'e', 'c',
        // Period (AM/PM)
        'a',
        // Hour
        'h', 'H', 'k', 'K',
        // Minute
        'm',
        // Second
        's', 'S', 'A',
        // Timezone
        'z', 'Z', 'O', 'X', 'v', 'V',
    ];

    public function validate(string $pattern): ValidationResult
    {
        $result = new ValidationResult();

        if ('' === trim($pattern)) {
            return $result;
        }

        $this->validatePattern($pattern, $result);

        return $result;
    }

    private function validatePattern(string $pattern, ValidationResult $result): void
    {
        $inQuote = false;
        $charCount = 0;
        $symbolCounts = [];

        for ($i = 0; $i < mb_strlen($pattern, 'UTF-8'); $i++) {
            $char = mb_substr($pattern, $i, 1, 'UTF-8');
            $charCount++;

            // Handle quoted literals
            if ("'" === $char) {
                if ($inQuote && isset($pattern[$i + 1]) && "'" === mb_substr($pattern, $i + 1, 1, 'UTF-8')) {
                    // Escaped quote
                    $i++;
                    $charCount++;
                } else {
                    $inQuote = !$inQuote;
                }

                continue;
            }

            if ($inQuote) {
                continue;
            }

            // Check for valid symbols (only count consecutive symbols)
            if (in_array($char, self::VALID_SYMBOLS, true)) {
                $symbolCounts[$char] = ($symbolCounts[$char] ?? 0) + 1;
            }
        }

        // Validate specific symbol rules
        $this->validateSymbolCounts($symbolCounts, $pattern, $result);
    }

    /**
     * @param array<string, int> $counts
     */
    private function validateSymbolCounts(array $counts, string $pattern, ValidationResult $result): void
    {
        // Day of year (D) and month (M) combination
        if (isset($counts['D']) && isset($counts['M'])) {
            $result->addWarning(new ValidationError(
                'Pattern contains both day of year (D) and month (M). Use with caution as D can exceed month days.',
                0,
                $pattern,
                'validator.day_year_month_conflict',
            ));
        }

        // Week-based year (Y) with month/day
        if (isset($counts['Y']) && (isset($counts['M']) || isset($counts['d']))) {
            $result->addWarning(new ValidationError(
                'Pattern contains both week-based year (Y) and month/day. Week-based year may not align with calendar months.',
                0,
                $pattern,
                'validator.week_year_calendar_conflict',
            ));
        }
    }
}
