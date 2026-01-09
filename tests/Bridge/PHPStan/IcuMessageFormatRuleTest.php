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
        $this->expectNotToPerformAssertions();

        new IcuMessageFormatRule(
            enabled: true,
            paths: ['/path1', '/path2'],
            defaultLocale: 'en',
            defaultDomain: 'messages',
            cacheDir: '/cache',
        );
    }

    public function test_constructor_with_default_parameters(): void
    {
        $this->expectNotToPerformAssertions();

        new IcuMessageFormatRule();
    }

    public function test_constructor_with_partial_parameters(): void
    {
        $this->expectNotToPerformAssertions();

        new IcuMessageFormatRule(
            enabled: true,
            paths: ['/custom/path'],
            defaultLocale: 'fr',
        );
    }

    public function test_constructor_with_empty_paths(): void
    {
        $this->expectNotToPerformAssertions();

        new IcuMessageFormatRule(
            enabled: true,
            paths: [],
        );
    }

    public function test_constructor_with_null_cache_dir(): void
    {
        $this->expectNotToPerformAssertions();

        new IcuMessageFormatRule(
            enabled: true,
            paths: ['/path'],
            cacheDir: null,
        );
    }

    public function test_disabled_rule_returns_no_errors(): void
    {
        $this->expectNotToPerformAssertions();

        new IcuMessageFormatRule(false);
    }

    public function test_enabled_rule(): void
    {
        $this->expectNotToPerformAssertions();

        new IcuMessageFormatRule(true);
    }

    public function test_rule_with_various_path_formats(): void
    {
        $this->expectNotToPerformAssertions();

        $paths = [
            '/absolute/path',
            'relative/path',
            './current/dir',
            '../parent/dir',
            '/path/with spaces',
            '/path/with-dashes',
            '/path/with_underscores',
        ];

        new IcuMessageFormatRule(true, $paths);
    }

    public function test_rule_with_duplicate_paths(): void
    {
        $this->expectNotToPerformAssertions();

        $paths = [
            '/path1',
            '/path2',
            '/path1', // duplicate
            '/path3',
            '/path2', // duplicate
        ];

        new IcuMessageFormatRule(true, $paths);
    }

    public function test_rule_with_empty_and_null_paths(): void
    {
        $this->expectNotToPerformAssertions();

        $paths = [
            '/valid/path',
            '', // empty string
            '/another/valid/path',
        ];

        new IcuMessageFormatRule(true, $paths);
    }

    public function test_rule_with_different_locales(): void
    {
        $this->expectNotToPerformAssertions();

        new IcuMessageFormatRule(
            enabled: true,
            paths: ['/path'],
            defaultLocale: 'fr_FR',
        );
    }

    public function test_rule_with_different_domains(): void
    {
        $this->expectNotToPerformAssertions();

        new IcuMessageFormatRule(
            enabled: true,
            paths: ['/path'],
            defaultDomain: 'custom_domain',
        );
    }
}
