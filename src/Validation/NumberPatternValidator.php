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
 * Validates ICU number format patterns.
 *
 * ICU number pattern syntax:
 * - Positive and negative patterns separated by ';'
 * - Symbols: 0 # . , % ¤ E
 */
final class NumberPatternValidator
{
    public function validate(string $pattern): ValidationResult
    {
        $result = new ValidationResult();

        if ('' === trim($pattern)) {
            return $result;
        }

        // Check for multiple patterns (positive;negative)
        $patterns = explode(';', $pattern);
        if (count($patterns) > 2) {
            $result->addError(new ValidationError(
                'Number pattern can have at most 2 sub-patterns (positive;negative).',
                0,
                $pattern,
                'validator.too_many_subpatterns',
            ));

            return $result;
        }

        foreach ($patterns as $index => $subPattern) {
            $this->validateSubPattern(trim($subPattern), $index, $pattern, $result);
        }

        return $result;
    }

    private function validateSubPattern(string $pattern, int $index, string $source, ValidationResult $result): void
    {
        if ('' === $pattern) {
            return;
        }

        $hasDigit = false;
        $hasDecimal = false;
        $hasExponent = false;
        $inQuote = false;
        $charCount = 0;

        for ($i = 0; $i < mb_strlen($pattern, 'UTF-8'); $i++) {
            $char = mb_substr($pattern, $i, 1, 'UTF-8');
            $charCount++;

            // Handle quoted literals
            if ("'" === $char) {
                if ($inQuote && isset($pattern[$i + 1]) && "'" === $pattern[$i + 1]) {
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

            // Track pattern structure
            if ($this->isDigit($char) || '#' === $char) {
                $hasDigit = true;
            }

            if ('.' === $char) {
                if ($hasDecimal) {
                    $result->addError(new ValidationError(
                        'Number pattern cannot have more than one decimal point.',
                        0,
                        $source,
                        'validator.multiple_decimals',
                    ));

                    return;
                }
                $hasDecimal = true;
            }

            if ('E' === mb_strtoupper($char, 'UTF-8')) {
                if ($hasExponent) {
                    $result->addError(new ValidationError(
                        'Number pattern cannot have more than one exponent.',
                        0,
                        $source,
                        'validator.multiple_exponents',
                    ));

                    return;
                }
                $hasExponent = true;
            }
        }

        if (!$hasDigit && !str_contains($pattern, '@')) {
            $result->addError(new ValidationError(
                'Number pattern must contain at least one digit placeholder (0, #, or @).',
                0,
                $source,
                'validator.no_digit_placeholder',
            ));
        }

        if ($inQuote) {
            $result->addError(new ValidationError(
                'Unterminated quoted literal in number pattern.',
                0,
                $source,
                'validator.unterminated_quote',
            ));
        }
    }

    private function isDigit(string $char): bool
    {
        return $char >= '0' && $char <= '9';
    }
}
