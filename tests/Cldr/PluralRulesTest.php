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

        if (class_exists(\Symfony\Component\Intl\Locales::class)) {
            $this->assertSame(['one', 'other'], $categories);
        } else {
            $this->assertSame(['other'], $categories);
        }
    }

    public function test_defaults_to_english(): void
    {
        $categories = PluralRules::getCategories('unknown-locale');

        $this->assertSame(['one', 'other'], $categories);
    }

    public function test_fractional_numbers(): void
    {
        // English: fractional numbers always use 'other'
        $this->assertSame('other', PluralRules::select(1.5, 'en'));
        $this->assertSame('other', PluralRules::select(0.1, 'en'));
    }
}
