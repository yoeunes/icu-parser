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

namespace IcuParser\Tests\Cldr;

use IcuParser\Cldr\PluralRules;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Intl\Locales;

final class PluralRulesTest extends TestCase
{
    public function test_english_plural_categories(): void
    {
        $categories = PluralRules::getCategories('en');

        $this->assertSame(['one', 'other'], $categories);
    }

    public function test_arabic_plural_categories(): void
    {
        $categories = PluralRules::getCategories('ar');

        $this->assertSame(['zero', 'one', 'two', 'few', 'many', 'other'], $categories);
    }

    public function test_japanese_plural_categories(): void
    {
        $categories = PluralRules::getCategories('ja');

        $this->assertSame(['other'], $categories);
    }

    public function test_english_plural_selection(): void
    {
        $this->assertSame('one', PluralRules::select(1, 'en'));
        $this->assertSame('one', PluralRules::select(1.0, 'en'));
        $this->assertSame('other', PluralRules::select(0, 'en'));
        $this->assertSame('other', PluralRules::select(2, 'en'));
        $this->assertSame('other', PluralRules::select(100, 'en'));
    }

    public function test_french_plural_selection(): void
    {
        $this->assertSame('one', PluralRules::select(0, 'fr'));
        $this->assertSame('one', PluralRules::select(1, 'fr'));
        $this->assertSame('other', PluralRules::select(2, 'fr'));
    }

    public function test_arabic_plural_selection(): void
    {
        $this->assertSame('zero', PluralRules::select(0, 'ar'));
        $this->assertSame('one', PluralRules::select(1, 'ar'));
        $this->assertSame('two', PluralRules::select(2, 'ar'));
        $this->assertSame('few', PluralRules::select(5, 'ar'));
        $this->assertSame('many', PluralRules::select(15, 'ar'));
        $this->assertSame('other', PluralRules::select(100, 'ar'));
    }

    public function test_russian_plural_selection(): void
    {
        $this->assertSame('one', PluralRules::select(1, 'ru'));
        $this->assertSame('one', PluralRules::select(21, 'ru'));
        $this->assertSame('few', PluralRules::select(2, 'ru'));
        $this->assertSame('few', PluralRules::select(3, 'ru'));
        $this->assertSame('few', PluralRules::select(4, 'ru'));
        $this->assertSame('many', PluralRules::select(5, 'ru'));
        $this->assertSame('many', PluralRules::select(11, 'ru'));
        $this->assertSame('many', PluralRules::select(0, 'ru'));
    }

    public function test_locale_normalization(): void
    {
        $categories1 = PluralRules::getCategories('en-US');
        $categories2 = PluralRules::getCategories('en');

        $this->assertSame($categories1, $categories2);
    }

    public function test_ordinal_categories(): void
    {
        $categories = PluralRules::getCategories('en', true);

        $this->assertSame(['one', 'two', 'few', 'other'], $categories);
    }

    public function test_english_ordinal_selection(): void
    {
        $this->assertSame('one', PluralRules::select(1, 'en', true));
        $this->assertSame('two', PluralRules::select(2, 'en', true));
        $this->assertSame('few', PluralRules::select(3, 'en', true));
        $this->assertSame('other', PluralRules::select(4, 'en', true));
        $this->assertSame('other', PluralRules::select(5, 'en', true));
        $this->assertSame('other', PluralRules::select(11, 'en', true));
        $this->assertSame('one', PluralRules::select(21, 'en', true));
    }

    public function test_french_ordinal_categories(): void
    {
        $categories = PluralRules::getCategories('fr', true);

        $this->assertSame(['one', 'other'], $categories);
    }

    public function test_defaults_to_english(): void
    {
        $categories = PluralRules::getCategories('unknown-locale');

        if (class_exists(Locales::class)) {
            $this->assertSame(['one', 'other'], $categories);
        } else {
            $this->assertSame(['other'], $categories);
        }
    }

    public function test_fractional_numbers(): void
    {
        // English: fractional numbers always use 'other'
        $this->assertSame('other', PluralRules::select(1.5, 'en'));
        $this->assertSame('other', PluralRules::select(0.1, 'en'));
    }

    public function test_fallback_locale_resolution(): void
    {
        $this->assertSame('en', $this->callPluralRules('fallbackLocale', ['en_US']));
        $this->assertSame('sr-Latn', $this->callPluralRules('fallbackLocale', ['sr-Latn-RS']));
        $this->assertNull($this->callPluralRules('fallbackLocale', ['en']));
    }

    public function test_legacy_rule_set_resolution(): void
    {
        $this->assertSame('pt', $this->callPluralRules('normalizeLanguage', ['PT-BR']));

        /** @var array{categories: list<string>, rule: string} $ruleSet */
        $ruleSet = $this->callPluralRules('getLegacyRuleSet', ['pt_BR', false]);
        $this->assertSame(['one', 'other'], $ruleSet['categories']);

        /** @var array{categories: list<string>, rule: string} $ordinalRuleSet */
        $ordinalRuleSet = $this->callPluralRules('getLegacyRuleSet', ['fr_CA', true]);
        $this->assertSame(['other'], $ordinalRuleSet['categories']);
    }

    public function test_legacy_rule_evaluator_supports_ternaries_and_conditions(): void
    {
        $rule = 'n == 0 ? zero : n == 1 ? one : other';

        $this->assertSame('zero', $this->callPluralRules('evaluateTernary', [$rule, 0, 0, 0.0, 0, 0]));
        $this->assertSame('one', $this->callPluralRules('evaluateTernary', [$rule, 1, 0, 0.0, 1, 0]));
        $this->assertSame('other', $this->callPluralRules('evaluateTernary', [$rule, 2, 0, 0.0, 2, 0]));

        $this->assertSame('one', $this->callPluralRules('evaluateRule', ['i == 1 ? one : other', 1, 0, 0.0, 1, 0]));
    }

    public function test_legacy_condition_parser_handles_operators(): void
    {
        $this->assertTrue($this->callPluralRules('evaluateCondition', ['i == 1 && v == 0', 1, 0, 0.0, 1, 0]));
        $this->assertTrue($this->callPluralRules('evaluateCondition', ['i != 2 || v < 0', 1, 0, 0.0, 1, 0]));
        $this->assertTrue($this->callPluralRules('evaluateCondition', ['i + 1 * 2 >= 3 && i % 2 == 1', 1, 0, 0.0, 1, 0]));
        $this->assertTrue($this->callPluralRules('evaluateCondition', ['i / 0 == 0', 1, 0, 0.0, 1, 0]));
        $this->assertTrue($this->callPluralRules('evaluateCondition', ['i - 1 <= 0', 1, 0, 0.0, 1, 0]));
        $this->assertTrue($this->callPluralRules('evaluateCondition', ['-i == -1', 1, 0, 0.0, 1, 0]));
        $this->assertTrue($this->callPluralRules('evaluateCondition', ['f == 0 && t == 0', 1, 0, 0.0, 1, 0]));
        $this->assertTrue($this->callPluralRules('evaluateCondition', ['(i == 1)', 1, 0, 0.0, 1, 0]));
        $this->assertTrue($this->callPluralRules('evaluateCondition', ['i', 1, 0, 0.0, 1, 0]));
    }

    public function test_legacy_tokenizer_and_colon_detection(): void
    {
        /** @var array<int, array{type: string, value: float|string}> $tokens */
        $tokens = $this->callPluralRules('tokenizeExpression', ['(i + 1.5) * 2 >= 4 && i % 2 == 1']);
        $types = array_column($tokens, 'type');

        $this->assertContains('paren', $types);
        $this->assertContains('op', $types);
        $this->assertContains('number', $types);
        $this->assertContains('var', $types);

        $this->assertSame(2, $this->callPluralRules('findColon', ['b : c']));
        $this->assertFalse($this->callPluralRules('findColon', ['no-colon']));
    }

    /**
     * @param array<int, mixed> $args
     */
    private function callPluralRules(string $method, array $args = []): mixed
    {
        $reflection = new \ReflectionMethod(PluralRules::class, $method);

        return $reflection->invokeArgs(null, $args);
    }
}
