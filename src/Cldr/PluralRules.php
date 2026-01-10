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

namespace IcuParser\Cldr;

use Symfony\Component\Intl\Locales;

/**
 * Plural rules for different locales.
 *
 * Uses ICU \MessageFormatter for full locale coverage with a legacy fallback.
 */
final class PluralRules
{
    private const CATEGORY_NAMES = ['zero', 'one', 'two', 'few', 'many', 'other'];

    private const RULES = [
        // English: one (1), other (0, 2-∞)
        'en' => [
            'categories' => ['one', 'other'],
            'rule' => 'i == 1 && v == 0',
        ],
        // French: one (0, 1), other (2-∞)
        'fr' => [
            'categories' => ['one', 'other'],
            'rule' => '(i == 0 || i == 1) && v == 0',
        ],
        // German: one (1), other (0, 2-∞)
        'de' => [
            'categories' => ['one', 'other'],
            'rule' => 'i == 1 && v == 0',
        ],
        // Spanish: one (1), other (0, 2-∞)
        'es' => [
            'categories' => ['one', 'other'],
            'rule' => 'i == 1 && v == 0',
        ],
        // Arabic: zero (0), one (1), two (2), few (3-10), many (11-99), other (100-∞)
        'ar' => [
            'categories' => ['zero', 'one', 'two', 'few', 'many', 'other'],
            'rule' => 'n == 0 ? zero : n == 1 ? one : n == 2 ? two : (n % 100 >= 3 && n % 100 <= 10) ? few : (n % 100 >= 11 && n % 100 <= 99) ? many : other',
        ],
        // Russian: one (1, 21, 31...), few (2-4, 22-24, 32-34...), many (5-20, 25-30, 35-40...), other (0)
        'ru' => [
            'categories' => ['one', 'few', 'many', 'other'],
            'rule' => 'v == 0 ? i % 10 == 1 && i % 100 != 11 ? one : i % 10 >= 2 && i % 10 <= 4 && (i % 100 < 12 || i % 100 > 14) ? few : i % 10 == 0 || i % 10 >= 5 && i % 10 <= 9 || i % 100 >= 11 && i % 100 <= 14 ? many : other : other',
        ],
        // Japanese: other (all numbers)
        'ja' => [
            'categories' => ['other'],
            'rule' => 'true',
        ],
        // Chinese: other (all numbers)
        'zh' => [
            'categories' => ['other'],
            'rule' => 'true',
        ],
        // Korean: other (all numbers)
        'ko' => [
            'categories' => ['other'],
            'rule' => 'true',
        ],
        // Turkish: one (1), other (0, 2-∞)
        'tr' => [
            'categories' => ['one', 'other'],
            'rule' => 'n == 1 ? one : other',
        ],
        // Polish: one (1), few (2-4, 22-24...), many (0, 5-21, 25-31...), other
        'pl' => [
            'categories' => ['one', 'few', 'many', 'other'],
            'rule' => '(i == 1 && v == 0) ? one : (v == 0 && (i % 10 >= 2 && i % 10 <= 4) && (i % 100 < 12 || i % 100 > 14)) ? few : (v == 0 && i != 1 && (i % 10 == 0 || i % 10 >= 5) || (v == 0 && (i % 100 >= 12 && i % 100 <= 14)) ? many : other',
        ],
        // Portuguese: one (1), other (0, 2-∞)
        'pt' => [
            'categories' => ['one', 'other'],
            'rule' => 'i == 1 && v == 0 ? one : other',
        ],
        // Italian: one (1), other (0, 2-∞)
        'it' => [
            'categories' => ['one', 'other'],
            'rule' => 'i == 1 && v == 0 ? one : other',
        ],
        // Hindi: one (0, 1), other (2-∞)
        'hi' => [
            'categories' => ['one', 'other'],
            'rule' => '(i == 0 || i == 1) ? one : other',
        ],
        // Indonesian: other (all numbers)
        'id' => [
            'categories' => ['other'],
            'rule' => 'true',
        ],
        // Vietnamese: other (all numbers)
        'vi' => [
            'categories' => ['other'],
            'rule' => 'true',
        ],
        // Hebrew: one (1), two (2), many (0, 20-∞), other (3-19)
        'he' => [
            'categories' => ['one', 'two', 'many', 'other'],
            'rule' => 'i == 1 && v == 0 ? one : i == 2 && v == 0 ? two : (v == 0 && (i == 0 || (i % 10 != 0 && i % 10 <= 9))) || (v != 0 && (i % 100 != 0 && i % 100 <= 9)) ? many : other',
        ],
    ];

    private const ORDINAL_RULES = [
        // English: one (1, 21, 31...), two (2, 22, 32...), few (3, 4, 23, 24...), other (0, 5-20, 25-30...)
        'en' => [
            'categories' => ['one', 'two', 'few', 'other'],
            'rule' => 'n % 10 == 1 && n % 100 != 11 ? one : n % 10 == 2 && n % 100 != 12 ? two : n % 10 == 3 && n % 100 != 13 ? few : other',
        ],
        // French: other (all numbers)
        'fr' => [
            'categories' => ['other'],
            'rule' => 'true',
        ],
        // Spanish: other (all numbers)
        'es' => [
            'categories' => ['other'],
            'rule' => 'true',
        ],
        // German: other (all numbers)
        'de' => [
            'categories' => ['other'],
            'rule' => 'true',
        ],
        // Italian: other (all numbers)
        'it' => [
            'categories' => ['other'],
            'rule' => 'true',
        ],
        // Portuguese: other (all numbers)
        'pt' => [
            'categories' => ['other'],
            'rule' => 'true',
        ],
    ];

    /**
     * @var array<string, \MessageFormatter>
     */
    private static array $formatterCache = [];

    /**
     * @var array<string, list<string>>
     */
    private static array $categoryCache = [];

    /**
     * Get valid plural categories for a locale.
     *
     * @return list<string>
     */
    public static function getCategories(string $locale, bool $ordinal = false): array
    {
        $resolvedLocale = self::resolveLocale($locale);
        $cacheKey = ($ordinal ? 'ordinal:' : 'cardinal:').$resolvedLocale;

        if (isset(self::$categoryCache[$cacheKey])) {
            return self::$categoryCache[$cacheKey];
        }

        $categories = self::detectCategories($resolvedLocale, $ordinal);
        if ([] !== $categories) {
            self::$categoryCache[$cacheKey] = $categories;

            return $categories;
        }

        $legacy = self::getLegacyRuleSet($locale, $ordinal)['categories'];
        self::$categoryCache[$cacheKey] = $legacy;

        return $legacy;
    }

    /**
     * Evaluate plural rule for a number.
     */
    public static function select(int|float $number, string $locale, bool $ordinal = false): string
    {
        $resolvedLocale = self::resolveLocale($locale);
        $category = self::selectWithIcu($number, $resolvedLocale, $ordinal);
        if (null !== $category) {
            return $category;
        }

        $ruleSet = self::getLegacyRuleSet($locale, $ordinal);
        $rule = $ruleSet['rule'];
        $categories = $ruleSet['categories'];

        // Extract integer and fractional parts
        $i = (int) $number;
        $v = 0;
        if (is_float($number)) {
            $fractional = (string) abs($number - $i);
            $v = strlen(rtrim($fractional, '0'));
        }
        $n = $number;
        $f = abs($number - $i);
        $t = (int) $f;

        // Evaluate rule
        if (!str_contains($rule, '?')) {
            $primary = $categories[0];
            if (self::evaluateCondition($rule, $i, $v, $f, $n, $t)) {
                return $primary;
            }

            return 'other';
        }

        return self::evaluateRule($rule, $i, $v, $f, $n, $t);
    }

    private static function resolveLocale(string $locale): string
    {
        $canonical = \Locale::canonicalize($locale);
        $candidate = (!is_string($canonical) || '' === $canonical) ? $locale : $canonical;
        $candidate = str_replace('-', '_', $candidate);

        if (!class_exists(Locales::class)) {
            return '' !== $candidate ? $candidate : 'en';
        }

        $seen = [];
        while (null !== $candidate) {
            if (isset($seen[$candidate])) {
                break;
            }
            $seen[$candidate] = true;

            if (Locales::exists($candidate)) {
                return $candidate;
            }

            $candidate = self::fallbackLocale($candidate);
        }

        return 'en';
    }

    private static function fallbackLocale(string $locale): ?string
    {
        if (false !== $pos = strrpos($locale, '_')) {
            $fallback = substr($locale, 0, $pos);

            return '' !== $fallback ? $fallback : null;
        }

        if (false !== $pos = strrpos($locale, '-')) {
            $fallback = substr($locale, 0, $pos);

            return '' !== $fallback ? $fallback : null;
        }

        return null;
    }

    /**
     * @return array{categories: list<string>, rule: string}
     */
    private static function getLegacyRuleSet(string $locale, bool $ordinal): array
    {
        $rules = $ordinal ? self::ORDINAL_RULES : self::RULES;
        $language = self::normalizeLanguage($locale);

        return $rules[$language] ?? $rules['en'];
    }

    private static function normalizeLanguage(string $locale): string
    {
        $normalized = strtolower(str_replace('-', '_', $locale));

        return explode('_', $normalized)[0];
    }

    private static function selectWithIcu(int|float $number, string $locale, bool $ordinal): ?string
    {
        $formatter = self::getFormatter($locale, $ordinal);
        if (null === $formatter) {
            return null;
        }

        $result = $formatter->format(['n' => $number]);
        if (false === $result) {
            return null;
        }

        return (string) $result;
    }

    /**
     * @return list<string>
     */
    private static function detectCategories(string $locale, bool $ordinal): array
    {
        $formatter = self::getFormatter($locale, $ordinal);
        if (null === $formatter) {
            return [];
        }

        $found = [];
        foreach (self::sampleNumbers($ordinal) as $number) {
            $result = $formatter->format(['n' => $number]);
            if (false === $result) {
                continue;
            }

            $found[(string) $result] = true;
        }

        if ([] === $found) {
            return [];
        }

        if (!isset($found['other'])) {
            $found['other'] = true;
        }

        $ordered = [];
        foreach (self::CATEGORY_NAMES as $category) {
            if (isset($found[$category])) {
                $ordered[] = $category;
            }
        }

        return $ordered;
    }

    /**
     * @return list<int|float>
     */
    private static function sampleNumbers(bool $ordinal): array
    {
        $samples = range(0, 200);
        if ($ordinal) {
            return $samples;
        }

        return array_merge($samples, [0.1, 1.1, 2.1, 5.5]);
    }

    private static function getFormatter(string $locale, bool $ordinal): ?\MessageFormatter
    {
        $cacheKey = ($ordinal ? 'ordinal:' : 'cardinal:').$locale;
        if (isset(self::$formatterCache[$cacheKey])) {
            return self::$formatterCache[$cacheKey];
        }

        $formatter = new \MessageFormatter($locale, self::buildPattern($ordinal));
        if (0 !== $formatter->getErrorCode()) {
            return null;
        }

        self::$formatterCache[$cacheKey] = $formatter;

        return $formatter;
    }

    private static function buildPattern(bool $ordinal): string
    {
        $type = $ordinal ? 'selectordinal' : 'plural';
        $chunks = [];
        foreach (self::CATEGORY_NAMES as $category) {
            $chunks[] = sprintf('%s{%s}', $category, $category);
        }

        return sprintf('{n, %s, %s}', $type, implode(' ', $chunks));
    }

    private static function evaluateRule(string $rule, int $i, int $v, float $f, float|int $n, int $t): string
    {
        return self::evaluateTernary($rule, $i, $v, $f, $n, $t);
    }

    private static function evaluateTernary(string $rule, int $i, int $v, float $f, float|int $n, int $t): string
    {
        // Find first '?' not in quotes and split
        $depth = 0;
        $conditionEnd = -1;

        for ($pos = 0; $pos < strlen($rule); $pos++) {
            $char = $rule[$pos];

            if ("'" === $char) {
                $depth = 1 - $depth;
            } elseif (0 === $depth && '?' === $char) {
                $conditionEnd = $pos;

                break;
            }
        }

        if (-1 === $conditionEnd) {
            return trim($rule);
        }

        $condition = trim(substr($rule, 0, $conditionEnd));
        $rest = trim(substr($rule, $conditionEnd + 1));

        // Split on ':' for true/false branches
        $colonPos = self::findColon($rest);

        if (false === $colonPos) {
            $trueExpr = $rest;
            $falseExpr = '';
        } else {
            $trueExpr = trim(substr($rest, 0, $colonPos));
            $falseExpr = trim(substr($rest, $colonPos + 1));
        }

        if (self::evaluateCondition($condition, $i, $v, $f, $n, $t)) {
            return '' !== $trueExpr && str_contains($trueExpr, '?')
                ? self::evaluateTernary($trueExpr, $i, $v, $f, $n, $t)
                : trim($trueExpr);
        }

        return '' !== $falseExpr && str_contains($falseExpr, '?')
            ? self::evaluateTernary($falseExpr, $i, $v, $f, $n, $t)
            : trim($falseExpr);
    }

    private static function findColon(string $str): int|false
    {
        $inQuote = false;
        $ternaryDepth = 0;
        for ($pos = 0; $pos < strlen($str); $pos++) {
            $char = $str[$pos];

            if ("'" === $char) {
                $inQuote = !$inQuote;

                continue;
            }

            if ($inQuote) {
                continue;
            }

            if ('?' === $char) {
                $ternaryDepth++;

                continue;
            }

            if (':' === $char) {
                if (0 === $ternaryDepth) {
                    return $pos;
                }

                $ternaryDepth--;
            }
        }

        return false;
    }

    private static function evaluateCondition(string $condition, int $i, int $v, float $f, float|int $n, int $t): bool
    {
        $condition = trim($condition);
        if ('' === $condition) {
            return false;
        }

        if ('true' === $condition) {
            return true;
        }

        if ('false' === $condition) {
            return false;
        }

        $tokens = self::tokenizeExpression($condition);
        $position = 0;
        $vars = [
            'i' => $i,
            'v' => $v,
            'f' => $f,
            'n' => $n,
            't' => $t,
        ];

        return self::parseOrExpression($tokens, $position, $vars);
    }

    /**
     * @return array<int, array{type: string, value: float|string}>
     */
    private static function tokenizeExpression(string $expression): array
    {
        $tokens = [];
        $length = \strlen($expression);
        $position = 0;

        while ($position < $length) {
            $char = $expression[$position];

            if (ctype_space($char)) {
                $position++;

                continue;
            }

            $twoChar = $char.($expression[$position + 1] ?? '');
            if (\in_array($twoChar, ['&&', '||', '==', '!=', '>=', '<='], true)) {
                $tokens[] = ['type' => 'op', 'value' => $twoChar];
                $position += 2;

                continue;
            }

            if (\in_array($char, ['<', '>', '+', '-', '*', '/', '%'], true)) {
                $tokens[] = ['type' => 'op', 'value' => $char];
                $position++;

                continue;
            }

            if ('(' === $char || ')' === $char) {
                $tokens[] = ['type' => 'paren', 'value' => $char];
                $position++;

                continue;
            }

            if (ctype_digit($char) || ('.' === $char && isset($expression[$position + 1]) && ctype_digit($expression[$position + 1]))) {
                $start = $position;
                $position++;
                while ($position < $length && ctype_digit($expression[$position])) {
                    $position++;
                }
                if ($position < $length && '.' === $expression[$position]) {
                    $position++;
                    while ($position < $length && ctype_digit($expression[$position])) {
                        $position++;
                    }
                }
                $tokens[] = ['type' => 'number', 'value' => (float) substr($expression, $start, $position - $start)];

                continue;
            }

            if (ctype_alpha($char)) {
                $tokens[] = ['type' => 'var', 'value' => $char];
                $position++;

                continue;
            }

            $position++;
        }

        return $tokens;
    }

    /**
     * @param array<int, array{type: string, value: float|string}> $tokens
     * @param array<string, float|int>                             $vars
     */
    private static function parseOrExpression(array $tokens, int &$position, array $vars): bool
    {
        $value = self::parseAndExpression($tokens, $position, $vars);

        while (self::matchOperator($tokens, $position, '||')) {
            $right = self::parseAndExpression($tokens, $position, $vars);
            $value = $value || $right;
        }

        return $value;
    }

    /**
     * @param array<int, array{type: string, value: float|string}> $tokens
     * @param array<string, float|int>                             $vars
     */
    private static function parseAndExpression(array $tokens, int &$position, array $vars): bool
    {
        $value = self::parseComparisonExpression($tokens, $position, $vars);

        while (self::matchOperator($tokens, $position, '&&')) {
            $right = self::parseComparisonExpression($tokens, $position, $vars);
            $value = $value && $right;
        }

        return $value;
    }

    /**
     * @param array<int, array{type: string, value: float|string}> $tokens
     * @param array<string, float|int>                             $vars
     */
    private static function parseComparisonExpression(array $tokens, int &$position, array $vars): bool
    {
        $left = self::parseAdditiveExpression($tokens, $position, $vars);
        $token = $tokens[$position] ?? null;

        if (null !== $token && 'op' === $token['type']) {
            $op = (string) $token['value'];

            switch ($op) {
                case '==':
                    $position++;
                    $right = self::parseAdditiveExpression($tokens, $position, $vars);

                    return $left === $right;
                case '!=':
                    $position++;
                    $right = self::parseAdditiveExpression($tokens, $position, $vars);

                    return $left !== $right;
                case '>':
                    $position++;
                    $right = self::parseAdditiveExpression($tokens, $position, $vars);

                    return $left > $right;
                case '<':
                    $position++;
                    $right = self::parseAdditiveExpression($tokens, $position, $vars);

                    return $left < $right;
                case '>=':
                    $position++;
                    $right = self::parseAdditiveExpression($tokens, $position, $vars);

                    return $left >= $right;
                case '<=':
                    $position++;
                    $right = self::parseAdditiveExpression($tokens, $position, $vars);

                    return $left <= $right;
            }
        }

        return 0.0 !== $left;
    }

    /**
     * @param array<int, array{type: string, value: float|string}> $tokens
     * @param array<string, float|int>                             $vars
     */
    private static function parseAdditiveExpression(array $tokens, int &$position, array $vars): float
    {
        $value = self::parseMultiplicativeExpression($tokens, $position, $vars);

        while (true) {
            if (self::matchOperator($tokens, $position, '+')) {
                $value += self::parseMultiplicativeExpression($tokens, $position, $vars);

                continue;
            }

            if (self::matchOperator($tokens, $position, '-')) {
                $value -= self::parseMultiplicativeExpression($tokens, $position, $vars);

                continue;
            }

            break;
        }

        return $value;
    }

    /**
     * @param array<int, array{type: string, value: float|string}> $tokens
     * @param array<string, float|int>                             $vars
     */
    private static function parseMultiplicativeExpression(array $tokens, int &$position, array $vars): float
    {
        $value = self::parseUnaryExpression($tokens, $position, $vars);

        while (true) {
            if (self::matchOperator($tokens, $position, '*')) {
                $value *= self::parseUnaryExpression($tokens, $position, $vars);

                continue;
            }

            if (self::matchOperator($tokens, $position, '/')) {
                $divisor = self::parseUnaryExpression($tokens, $position, $vars);
                $value = 0.0 === $divisor ? 0.0 : $value / $divisor;

                continue;
            }

            if (self::matchOperator($tokens, $position, '%')) {
                $divisor = self::parseUnaryExpression($tokens, $position, $vars);
                $value = 0.0 === $divisor ? 0.0 : fmod($value, $divisor);

                continue;
            }

            break;
        }

        return $value;
    }

    /**
     * @param array<int, array{type: string, value: float|string}> $tokens
     * @param array<string, float|int>                             $vars
     */
    private static function parseUnaryExpression(array $tokens, int &$position, array $vars): float
    {
        if (self::matchOperator($tokens, $position, '+')) {
            return self::parseUnaryExpression($tokens, $position, $vars);
        }

        if (self::matchOperator($tokens, $position, '-')) {
            return -self::parseUnaryExpression($tokens, $position, $vars);
        }

        return self::parsePrimaryExpression($tokens, $position, $vars);
    }

    /**
     * @param array<int, array{type: string, value: float|string}> $tokens
     * @param array<string, float|int>                             $vars
     */
    private static function parsePrimaryExpression(array $tokens, int &$position, array $vars): float
    {
        $token = $tokens[$position] ?? null;
        if (null === $token) {
            return 0.0;
        }

        if ('number' === $token['type']) {
            $position++;

            return (float) $token['value'];
        }

        if ('var' === $token['type']) {
            $position++;

            return (float) ($vars[(string) $token['value']] ?? 0.0);
        }

        if ('paren' === $token['type'] && '(' === $token['value']) {
            $position++;
            $value = self::parseOrExpression($tokens, $position, $vars);

            if (($tokens[$position]['type'] ?? null) === 'paren' && ')' === ($tokens[$position]['value'] ?? null)) {
                $position++;
            }

            return $value ? 1.0 : 0.0;
        }

        $position++;

        return 0.0;
    }

    /**
     * @param array<int, array{type: string, value: float|string}> $tokens
     */
    private static function matchOperator(array $tokens, int &$position, string $operator): bool
    {
        if (!isset($tokens[$position]) || 'op' !== $tokens[$position]['type']) {
            return false;
        }

        if ($tokens[$position]['value'] !== $operator) {
            return false;
        }

        $position++;

        return true;
    }
}
