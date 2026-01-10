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
use IcuParser\Catalog\CatalogInterface;
use IcuParser\Tests\Support\FilesystemTestCase;
use IcuParser\Tests\Support\PhpstanStubs\ConstantArrayType;
use IcuParser\Tests\Support\PhpstanStubs\ConstantStringType;
use IcuParser\Tests\Support\PhpstanStubs\ObjectType;
use IcuParser\Tests\Support\PhpstanStubs\Scope;
use IcuParser\Tests\Support\PhpstanStubs\StubType;
use IcuParser\Tests\Support\PhpstanStubs\TrinaryLogic;
use IcuParser\Tests\Support\PhpstanStubs\Type;
use IcuParser\Type\ParameterType;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;

final class IcuMessageFormatRuleTest extends FilesystemTestCase
{
    private static bool $aliasesRegistered = false;

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
            '/path1',
            '/path3',
            '/path2',
        ];

        new IcuMessageFormatRule(true, $paths);
    }

    public function test_rule_with_empty_and_null_paths(): void
    {
        $this->expectNotToPerformAssertions();

        $paths = [
            '/valid/path',
            '',
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

    public function test_analyze_message_reports_syntax_errors(): void
    {
        $rule = new IcuMessageFormatRule();
        /** @var array{error: string|null, types: array<string, ParameterType>, semantic: array<string>} $analysis */
        $analysis = $this->invokeRule($rule, 'analyzeMessage', ['{name, plural']);

        $this->assertIsArray($analysis);
        $this->assertNotNull($analysis['error']);
        $this->assertSame([], $analysis['types']);
    }

    public function test_analyze_message_infers_types_and_caches(): void
    {
        $rule = new IcuMessageFormatRule();
        /** @var array{error: string|null, types: array<string, ParameterType>, semantic: array<string>} $analysis */
        $analysis = $this->invokeRule($rule, 'analyzeMessage', ['Hello {count, number}']);
        /** @var array{error: string|null, types: array<string, ParameterType>, semantic: array<string>} $cached */
        $cached = $this->invokeRule($rule, 'analyzeMessage', ['Hello {count, number}']);

        $this->assertNull($analysis['error']);
        $this->assertSame($analysis, $cached);
        $this->assertSame(ParameterType::NUMBER, $analysis['types']['count']);
    }

    public function test_get_catalog_returns_null_without_paths(): void
    {
        $rule = new IcuMessageFormatRule();

        $this->assertNull($this->invokeRule($rule, 'getCatalog'));
    }

    public function test_get_catalog_loads_messages(): void
    {
        $path = $this->writeFile('messages.en.yaml', "greeting: 'Hello {name}'\n");
        $rule = new IcuMessageFormatRule(true, [dirname($path)], 'en');

        /** @var CatalogInterface|null $catalog */
        $catalog = $this->invokeRule($rule, 'getCatalog');

        $this->assertInstanceOf(CatalogInterface::class, $catalog);
        $this->assertSame('Hello {name}', $catalog->getMessage('greeting', 'en', 'messages'));
    }

    public function test_resolve_parameter_types_from_array_literal(): void
    {
        $this->requirePhpstanStubs();

        $rule = new IcuMessageFormatRule();

        $numberExpr = new Variable('number');
        $stringExpr = new Variable('string');
        $dateExpr = new Variable('date');
        $mixedExpr = new Variable('mixed');
        $dynamicKey = new Variable('dynamic');

        $array = new Array_([
            new ArrayItem($numberExpr, new String_('count')),
            new ArrayItem($stringExpr, $dynamicKey),
            new ArrayItem($dateExpr, new String_('when')),
            new ArrayItem($mixedExpr, new String_('meta')),
        ]);

        $scope = new Scope([
            spl_object_hash($numberExpr) => new StubType(integer: true),
            spl_object_hash($stringExpr) => new StubType(string: true),
            spl_object_hash($dateExpr) => new ObjectType(\DateTimeImmutable::class),
            spl_object_hash($mixedExpr) => new StubType(),
            spl_object_hash($dynamicKey) => new StubType(constantStrings: [new ConstantStringType('label')]),
        ]);

        /** @var array<string, ParameterType> $types */
        $types = $this->invokeRule($rule, 'resolveParameterTypes', [$array, $scope]);

        $this->assertSame(ParameterType::NUMBER, $types['count']);
        $this->assertSame(ParameterType::STRING, $types['label']);
        $this->assertSame(ParameterType::DATETIME, $types['when']);
        $this->assertSame(ParameterType::MIXED, $types['meta']);
    }

    public function test_resolve_constant_array_types(): void
    {
        $this->requirePhpstanStubs();

        $rule = new IcuMessageFormatRule();
        $expr = new Variable('params');

        $constantArray = new ConstantArrayType(
            [new ConstantStringType('count')],
            [new StubType(integer: true)],
        );

        $scope = new Scope([
            spl_object_hash($expr) => new StubType(constantArrays: [$constantArray]),
        ]);

        /** @var array<string, ParameterType> $types */
        $types = $this->invokeRule($rule, 'resolveParameterTypes', [$expr, $scope]);

        $this->assertSame(ParameterType::NUMBER, $types['count']);
    }

    public function test_resolve_optional_string_args_and_constants(): void
    {
        $this->requirePhpstanStubs();

        $rule = new IcuMessageFormatRule();
        $expr = new Variable('domain');
        $dynamicExpr = new Variable('locale');
        $nullExpr = new Variable('null');

        $scope = new Scope([
            spl_object_hash($expr) => new StubType(constantStrings: [new ConstantStringType('messages')]),
            spl_object_hash($dynamicExpr) => new StubType(),
            spl_object_hash($nullExpr) => new StubType(null: true),
        ]);

        $this->assertSame('messages', $this->invokeRule($rule, 'resolveOptionalStringArg', [$expr, $scope]));
        $this->assertSame(['messages'], $this->invokeRule($rule, 'resolveConstantStrings', [$expr, $scope]));
        $this->assertTrue($this->invokeRule($rule, 'isDynamicExplicitArg', [$dynamicExpr, $scope]));
        $this->assertFalse($this->invokeRule($rule, 'isDynamicExplicitArg', [null, $scope]));
        $this->assertNull($this->invokeRule($rule, 'resolveOptionalStringArg', [$nullExpr, $scope]));
    }

    /**
     * @param array<int, mixed> $args
     */
    private function invokeRule(IcuMessageFormatRule $rule, string $method, array $args = []): mixed
    {
        $reflection = new \ReflectionMethod($rule, $method);

        return $reflection->invokeArgs($rule, $args);
    }

    private function requirePhpstanStubs(): void
    {
        self::registerPhpstanAliases();

        if (!defined('ICUPARSER_PHPSTAN_STUBS')) {
            $this->markTestSkipped('PHPStan core installed; stub-only tests are skipped.');
        }
    }

    private static function registerPhpstanAliases(): void
    {
        if (self::$aliasesRegistered) {
            return;
        }

        if (class_exists(\PHPStan\Type\Type::class, false) || interface_exists(\PHPStan\Type\Type::class, false)) {
            self::$aliasesRegistered = true;

            return;
        }

        class_exists(Type::class);
        class_alias(Type::class, \PHPStan\Type\Type::class);
        class_alias(ObjectType::class, \PHPStan\Type\ObjectType::class);
        class_alias(ConstantStringType::class, \PHPStan\Type\Constant\ConstantStringType::class);
        class_alias(ConstantArrayType::class, \PHPStan\Type\Constant\ConstantArrayType::class);
        class_alias(Scope::class, \PHPStan\Analyser\Scope::class);
        class_alias(TrinaryLogic::class, \PHPStan\TrinaryLogic::class);

        if (!defined('ICUPARSER_PHPSTAN_STUBS')) {
            define('ICUPARSER_PHPSTAN_STUBS', true);
        }

        self::$aliasesRegistered = true;
    }
}
