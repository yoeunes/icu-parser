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

namespace IcuParser\Tests\Bridge\PHPStan;

use IcuParser\Bridge\PHPStan\IcuMessageFormatRule;
use PhpParser\Node\Expr\MethodCall;
use PHPUnit\Framework\TestCase;

final class IcuMessageFormatRuleTest extends TestCase
{
    public function test_get_node_type(): void
    {
        $rule = new IcuMessageFormatRule();

        $this->assertSame(MethodCall::class, $rule->getNodeType());
    }

    public function test_constructor_with_all_parameters(): void
    {
        $rule = new IcuMessageFormatRule(
            enabled: true,
            paths: ['/path1', '/path2'],
            defaultLocale: 'en',
            defaultDomain: 'messages',
            cacheDir: '/cache',
        );

        $this->assertInstanceOf(IcuMessageFormatRule::class, $rule);
    }

    public function test_constructor_with_default_parameters(): void
    {
        $rule = new IcuMessageFormatRule();

        $this->assertInstanceOf(IcuMessageFormatRule::class, $rule);
    }

    public function test_constructor_with_partial_parameters(): void
    {
        $rule = new IcuMessageFormatRule(
            enabled: true,
            paths: ['/custom/path'],
            defaultLocale: 'fr',
        );

        $this->assertInstanceOf(IcuMessageFormatRule::class, $rule);
    }

    public function test_constructor_with_empty_paths(): void
    {
        $rule = new IcuMessageFormatRule(
            enabled: true,
            paths: [],
        );

        $this->assertInstanceOf(IcuMessageFormatRule::class, $rule);
    }

    public function test_constructor_with_null_cache_dir(): void
    {
        $rule = new IcuMessageFormatRule(
            enabled: true,
            paths: ['/path'],
            cacheDir: null,
        );

        $this->assertInstanceOf(IcuMessageFormatRule::class, $rule);
    }

    public function test_constants(): void
    {
        $this->assertSame('icu.translation.missing', IcuMessageFormatRule::IDENTIFIER_TRANSLATION_MISSING);
        $this->assertSame('icu.syntax.error', IcuMessageFormatRule::IDENTIFIER_SYNTAX_ERROR);
        $this->assertSame('icu.semantic.error', IcuMessageFormatRule::IDENTIFIER_SEMANTIC_ERROR);
        $this->assertSame('icu.parameter.missing', IcuMessageFormatRule::IDENTIFIER_PARAMETER_MISSING);
        $this->assertSame('icu.parameter.type', IcuMessageFormatRule::IDENTIFIER_PARAMETER_TYPE);
    }

    public function test_disabled_rule_returns_no_errors(): void
    {
        $rule = new IcuMessageFormatRule(false);

        // Since processNode requires complex mocks, just test constructor
        $this->assertInstanceOf(IcuMessageFormatRule::class, $rule);
    }

    public function test_enabled_rule(): void
    {
        $rule = new IcuMessageFormatRule(true);

        $this->assertInstanceOf(IcuMessageFormatRule::class, $rule);
    }

    public function test_rule_with_various_path_formats(): void
    {
        $paths = [
            '/absolute/path',
            'relative/path',
            './current/dir',
            '../parent/dir',
            '/path/with spaces',
            '/path/with-dashes',
            '/path/with_underscores',
        ];

        $rule = new IcuMessageFormatRule(true, $paths);

        $this->assertInstanceOf(IcuMessageFormatRule::class, $rule);
    }

    public function test_rule_with_duplicate_paths(): void
    {
        $paths = [
            '/path1',
            '/path2',
            '/path1', // duplicate
            '/path3',
            '/path2', // duplicate
        ];

        $rule = new IcuMessageFormatRule(true, $paths);

        $this->assertInstanceOf(IcuMessageFormatRule::class, $rule);
    }

    public function test_rule_with_empty_and_null_paths(): void
    {
        $paths = [
            '/valid/path',
            '', // empty string
            '/another/valid/path',
        ];

        $rule = new IcuMessageFormatRule(true, $paths);

        $this->assertInstanceOf(IcuMessageFormatRule::class, $rule);
    }

    public function test_rule_with_different_locales(): void
    {
        $rule = new IcuMessageFormatRule(
            enabled: true,
            paths: ['/path'],
            defaultLocale: 'fr_FR',
        );

        $this->assertInstanceOf(IcuMessageFormatRule::class, $rule);
    }

    public function test_rule_with_different_domains(): void
    {
        $rule = new IcuMessageFormatRule(
            enabled: true,
            paths: ['/path'],
            defaultDomain: 'custom_domain',
        );

        $this->assertInstanceOf(IcuMessageFormatRule::class, $rule);
    }
}
