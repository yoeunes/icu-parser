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
use IcuParser\Node\ChoiceNode;
use IcuParser\Node\TextNode;
use IcuParser\Parser\Parser;
use PHPUnit\Framework\TestCase;

final class ChoiceFormatTest extends TestCase
{
    private Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Parser();
    }

    public function test_parses_simple_choice_format(): void
    {
        $node = $this->parser->parse('{value, choice, 0#none|1#one|2<many}');

        $choice = $node->parts[0];
        $this->assertInstanceOf(ChoiceNode::class, $choice);
        $this->assertSame('value', $choice->name);
        $this->assertCount(3, $choice->options);
    }

    public function test_parses_choice_with_inclusive_limits(): void
    {
        $node = $this->parser->parse('{value, choice, 0#zero|1#one}');

        $choice = $node->parts[0];
        $this->assertInstanceOf(ChoiceNode::class, $choice);
        $this->assertFalse($choice->options[0]->isExclusive);
        $this->assertFalse($choice->options[1]->isExclusive);
    }

    public function test_parses_choice_with_exclusive_limits(): void
    {
        $node = $this->parser->parse('{value, choice, 0<not zero}');

        $choice = $node->parts[0];
        $this->assertInstanceOf(ChoiceNode::class, $choice);
        $this->assertTrue($choice->options[0]->isExclusive);
    }

    public function test_parses_choice_messages_without_operators(): void
    {
        $node = $this->parser->parse('{value, choice, 0#none|1<one}');

        $choice = $node->parts[0];
        $this->assertInstanceOf(ChoiceNode::class, $choice);
        $firstPart = $choice->options[0]->message->parts[0];
        $this->assertInstanceOf(TextNode::class, $firstPart);
        $this->assertSame('none', $firstPart->text);
        $secondPart = $choice->options[1]->message->parts[0];
        $this->assertInstanceOf(TextNode::class, $secondPart);
        $this->assertSame('one', $secondPart->text);
    }

    public function test_parses_choice_with_multiple_options(): void
    {
        $node = $this->parser->parse('{value, choice, 0#zero|1#one|2<two|3<three}');

        $choice = $node->parts[0];
        $this->assertInstanceOf(ChoiceNode::class, $choice);
        $this->assertCount(4, $choice->options);
        $this->assertEqualsWithDelta(0.0, $choice->options[0]->limit, \PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(1.0, $choice->options[1]->limit, \PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(2.0, $choice->options[2]->limit, \PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(3.0, $choice->options[3]->limit, \PHP_FLOAT_EPSILON);
    }

    public function test_parses_choice_with_complex_messages(): void
    {
        $node = $this->parser->parse('{value, choice, 0#No items|1#One item|1<Many items}');

        $choice = $node->parts[0];
        $this->assertInstanceOf(ChoiceNode::class, $choice);
        $this->assertCount(3, $choice->options);
        $part = $choice->options[2]->message->parts[0];
        $this->assertInstanceOf(TextNode::class, $part);
        $this->assertSame('Many items', trim($part->text));
    }

    public function test_throws_on_empty_choice(): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('Expected options for "choice" argument.');

        $this->parser->parse('{value, choice}');
    }

    public function test_throws_on_missing_choice_operator(): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('Expected "#" or "<" after choice limit.');

        $this->parser->parse('{value, choice, 0 none}');
    }
}
