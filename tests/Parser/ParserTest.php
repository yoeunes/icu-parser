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

namespace IcuParser\Tests\Parser;

use IcuParser\Exception\ParserException;
use IcuParser\Node\FormattedArgumentNode;
use IcuParser\Node\MessageNode;
use IcuParser\Node\PluralNode;
use IcuParser\Node\SelectNode;
use IcuParser\Node\SimpleArgumentNode;
use IcuParser\Node\TextNode;
use IcuParser\Parser\Parser;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase
{
    private Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Parser();
    }

    public function test_parses_simple_message(): void
    {
        $node = $this->parser->parse('Hello world');

        $this->assertInstanceOf(MessageNode::class, $node);
        $this->assertCount(1, $node->parts);
        $this->assertInstanceOf(TextNode::class, $node->parts[0]);
        $this->assertSame('Hello world', $node->parts[0]->text);
    }

    public function test_parses_simple_argument(): void
    {
        $node = $this->parser->parse('Hello {name}');

        $this->assertInstanceOf(MessageNode::class, $node);
        $this->assertCount(2, $node->parts);
        $this->assertInstanceOf(TextNode::class, $node->parts[0]);
        $this->assertSame('Hello ', $node->parts[0]->text);
        $this->assertInstanceOf(SimpleArgumentNode::class, $node->parts[1]);
        $this->assertSame('name', $node->parts[1]->name);
    }

    public function test_parses_formatted_argument(): void
    {
        $node = $this->parser->parse('Count: {count, number}');

        $this->assertInstanceOf(MessageNode::class, $node);
        $this->assertCount(2, $node->parts);
        $this->assertInstanceOf(TextNode::class, $node->parts[0]);
        $this->assertSame('Count: ', $node->parts[0]->text);
        $this->assertInstanceOf(FormattedArgumentNode::class, $node->parts[1]);
        $this->assertSame('count', $node->parts[1]->name);
        $this->assertSame('number', $node->parts[1]->format);
    }

    public function test_parses_plural_argument(): void
    {
        $node = $this->parser->parse('{count, plural, one {# item} other {# items}}');

        $this->assertInstanceOf(MessageNode::class, $node);
        $this->assertCount(1, $node->parts);
        $this->assertInstanceOf(PluralNode::class, $node->parts[0]);
        $this->assertSame('count', $node->parts[0]->name);
        $this->assertCount(2, $node->parts[0]->options);
    }

    public function test_parses_select_argument(): void
    {
        $node = $this->parser->parse('{gender, select, male {Mr.} female {Ms.} other {}}');

        $this->assertInstanceOf(MessageNode::class, $node);
        $this->assertCount(1, $node->parts);
        $this->assertInstanceOf(SelectNode::class, $node->parts[0]);
        $this->assertSame('gender', $node->parts[0]->name);
        $this->assertCount(3, $node->parts[0]->options);
    }

    public function test_parses_complex_message(): void
    {
        $node = $this->parser->parse('Hello {name}, you have {count, plural, one {# item} other {# items}} in your cart.');

        $this->assertInstanceOf(MessageNode::class, $node);
        $this->assertCount(5, $node->parts);
        $this->assertInstanceOf(TextNode::class, $node->parts[0]);
        $this->assertSame('Hello ', $node->parts[0]->text);
        $this->assertInstanceOf(SimpleArgumentNode::class, $node->parts[1]);
        $this->assertSame('name', $node->parts[1]->name);
        $this->assertInstanceOf(TextNode::class, $node->parts[2]);
        $this->assertSame(', you have ', $node->parts[2]->text);
        $this->assertInstanceOf(PluralNode::class, $node->parts[3]);
        $this->assertInstanceOf(TextNode::class, $node->parts[4]);
        $this->assertSame(' in your cart.', $node->parts[4]->text);
    }

    public function test_throws_on_unexpected_rbrace(): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('Unexpected "}".');

        $this->parser->parse('Hello }');
    }

    public function test_throws_on_invalid_syntax(): void
    {
        $this->expectException(ParserException::class);

        $this->parser->parse('{name');
    }
}
