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

namespace IcuParser\Tests;

use IcuParser\IcuParser;
use IcuParser\Node\SimpleArgumentNode;
use IcuParser\Node\TextNode;
use IcuParser\Type\ParameterType;
use PHPUnit\Framework\TestCase;

final class IcuParserTest extends TestCase
{
    private IcuParser $parser;

    protected function setUp(): void
    {
        $this->parser = new IcuParser();
    }

    public function test_parse_simple_message(): void
    {
        $node = $this->parser->parse('Hello world');

        $this->assertCount(1, $node->parts);
        $this->assertInstanceOf(TextNode::class, $node->parts[0]);
        $this->assertSame('Hello world', $node->parts[0]->text);
    }

    public function test_parse_message_with_argument(): void
    {
        $node = $this->parser->parse('Hello {name}');

        $this->assertCount(2, $node->parts);
        $this->assertInstanceOf(TextNode::class, $node->parts[0]);
        $this->assertSame('Hello ', $node->parts[0]->text);
        $this->assertInstanceOf(SimpleArgumentNode::class, $node->parts[1]);
        $this->assertSame('name', $node->parts[1]->name);
    }

    public function test_infer_types_for_simple_message(): void
    {
        $types = $this->parser->infer('Hello {name}');

        $this->assertSame(ParameterType::STRING, $types->get('name'));
    }

    public function test_infer_types_for_formatted_arguments(): void
    {
        $types = $this->parser->infer('{count, number}, {date, date}');

        $this->assertSame(ParameterType::NUMBER, $types->get('count'));
        $this->assertSame(ParameterType::DATETIME, $types->get('date'));
    }

    public function test_version_constant(): void
    {
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', IcuParser::VERSION);
    }

    public function test_constructor_with_custom_dependencies(): void
    {
        $this->expectNotToPerformAssertions();

        $parser = new IcuParser();
        unset($parser);
    }
}
