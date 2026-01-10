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
use IcuParser\Node\DurationNode;
use IcuParser\Node\FormattedArgumentNode;
use IcuParser\Node\OrdinalNode;
use IcuParser\Node\PluralNode;
use IcuParser\Node\PoundNode;
use IcuParser\Node\SelectNode;
use IcuParser\Node\SelectOrdinalNode;
use IcuParser\Node\SimpleArgumentNode;
use IcuParser\Node\SpelloutNode;
use IcuParser\Node\TextNode;
use IcuParser\NodeVisitor\AstDumper;
use IcuParser\Parser\Parser;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase
{
    private Parser $parser;

    private AstDumper $astDumper;

    protected function setUp(): void
    {
        $this->parser = new Parser();
        $this->astDumper = new AstDumper();
    }

    public function test_parses_simple_message(): void
    {
        $node = $this->parser->parse('Hello world');

        $this->assertCount(1, $node->parts);
        $this->assertInstanceOf(TextNode::class, $node->parts[0]);
        $this->assertSame('Hello world', $node->parts[0]->text);
    }

    public function test_parses_simple_argument(): void
    {
        $node = $this->parser->parse('Hello {name}');

        $this->assertCount(2, $node->parts);
        $this->assertInstanceOf(TextNode::class, $node->parts[0]);
        $this->assertSame('Hello ', $node->parts[0]->text);
        $this->assertInstanceOf(SimpleArgumentNode::class, $node->parts[1]);
        $this->assertSame('name', $node->parts[1]->name);
    }

    public function test_parses_numeric_argument_name(): void
    {
        $node = $this->parser->parse('Value {0}');

        $this->assertCount(2, $node->parts);
        $this->assertInstanceOf(SimpleArgumentNode::class, $node->parts[1]);
        $this->assertSame('0', $node->parts[1]->name);
    }

    public function test_rejects_negative_argument_name(): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('Argument name cannot be negative.');

        $this->parser->parse('Value {-1}');
    }

    public function test_parses_formatted_argument(): void
    {
        $node = $this->parser->parse('Count: {count, number}');

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

        $this->assertCount(1, $node->parts);
        $this->assertInstanceOf(PluralNode::class, $node->parts[0]);
        $this->assertSame('count', $node->parts[0]->name);
        $this->assertCount(2, $node->parts[0]->options);
    }

    public function test_parses_select_argument(): void
    {
        $node = $this->parser->parse('{gender, select, male {Mr.} female {Ms.} other {}}');

        $this->assertCount(1, $node->parts);
        $this->assertInstanceOf(SelectNode::class, $node->parts[0]);
        $this->assertSame('gender', $node->parts[0]->name);
        $this->assertCount(3, $node->parts[0]->options);
    }

    public function test_parses_complex_message(): void
    {
        $node = $this->parser->parse('Hello {name}, you have {count, plural, one {# item} other {# items}} in your cart.');

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

    public function test_parse_numeric_value_with_integer(): void
    {
        $node = $this->parser->parse('{n, plural, offset:5 one {#} other {#}}');

        $this->assertInstanceOf(PluralNode::class, $node->parts[0]);
        $this->assertSame(5, $node->parts[0]->offset);
    }

    public function test_parse_numeric_value_with_float(): void
    {
        $node = $this->parser->parse('{n, plural, offset:5.5 one {#} other {#}}');

        $this->assertInstanceOf(PluralNode::class, $node->parts[0]);
        $this->assertEqualsWithDelta(5.5, $node->parts[0]->offset, \PHP_FLOAT_EPSILON);
    }

    public function test_parse_numeric_value_with_zero(): void
    {
        $node = $this->parser->parse('{n, plural, offset:0 one {#} other {#}}');

        $this->assertInstanceOf(PluralNode::class, $node->parts[0]);
        $this->assertSame(0, $node->parts[0]->offset);
    }

    public function test_parse_numeric_value_with_negative_integer(): void
    {
        $node = $this->parser->parse('{n, plural, offset:-10 one {#} other {#}}');

        $this->assertInstanceOf(PluralNode::class, $node->parts[0]);
        $this->assertSame(-10, $node->parts[0]->offset);
    }

    public function test_parse_numeric_value_with_negative_float(): void
    {
        $node = $this->parser->parse('{n, plural, offset:-10.5 one {#} other {#}}');

        $this->assertInstanceOf(PluralNode::class, $node->parts[0]);
        $this->assertSame(-10.5, $node->parts[0]->offset);
    }

    public function test_parse_numeric_value_with_explicit_selector_integer(): void
    {
        $node = $this->parser->parse('{n, plural, =1 {one} other {other}}');

        $this->assertInstanceOf(PluralNode::class, $node->parts[0]);
        $this->assertCount(2, $node->parts[0]->options);
        $this->assertSame('=1', $node->parts[0]->options[0]->selector);
        $this->assertTrue($node->parts[0]->options[0]->explicit);
        $this->assertSame(1, $node->parts[0]->options[0]->explicitValue);
    }

    public function test_parse_numeric_value_with_explicit_selector_float(): void
    {
        $node = $this->parser->parse('{n, plural, =1.5 {one} other {other}}');

        $this->assertInstanceOf(PluralNode::class, $node->parts[0]);
        $this->assertCount(2, $node->parts[0]->options);
        $this->assertSame('=1.5', $node->parts[0]->options[0]->selector);
        $this->assertTrue($node->parts[0]->options[0]->explicit);
        $this->assertEqualsWithDelta(1.5, $node->parts[0]->options[0]->explicitValue, \PHP_FLOAT_EPSILON);
    }

    public function test_collect_style_until_with_simple_style(): void
    {
        $node = $this->parser->parse('{value, number, currency}');

        $this->assertInstanceOf(FormattedArgumentNode::class, $node->parts[0]);
        $this->assertSame('currency', $node->parts[0]->style);
    }

    public function test_collect_style_until_with_complex_style(): void
    {
        $node = $this->parser->parse('{value, number, ¤#,##0.00}');

        $this->assertInstanceOf(FormattedArgumentNode::class, $node->parts[0]);
        $this->assertSame('¤#,##0.00', $node->parts[0]->style);
    }

    public function test_collect_style_until_with_whitespace(): void
    {
        $node = $this->parser->parse('{value, number,  percent }');

        $this->assertInstanceOf(FormattedArgumentNode::class, $node->parts[0]);
        $this->assertSame('percent', $node->parts[0]->style);
    }

    public function test_collect_style_until_with_empty_style_returns_null(): void
    {
        $node = $this->parser->parse('{value, number, }');

        $this->assertInstanceOf(FormattedArgumentNode::class, $node->parts[0]);
        $this->assertNull($node->parts[0]->style);
    }

    public function test_collect_style_until_with_date_format(): void
    {
        $node = $this->parser->parse('{date, date, yyyy-MM-dd}');

        $this->assertInstanceOf(FormattedArgumentNode::class, $node->parts[0]);
        $this->assertSame('yyyy-MM-dd', $node->parts[0]->style);
    }

    public function test_collect_style_until_with_time_format(): void
    {
        $node = $this->parser->parse('{time, time, HH:mm:ss}');

        $this->assertInstanceOf(FormattedArgumentNode::class, $node->parts[0]);
        $this->assertSame('HH:mm:ss', $node->parts[0]->style);
    }

    public function test_parses_spellout_argument(): void
    {
        $node = $this->parser->parse('{count, spellout}');

        $this->assertCount(1, $node->parts);
        $this->assertInstanceOf(SpelloutNode::class, $node->parts[0]);
        $this->assertSame('count', $node->parts[0]->name);
        $this->assertSame('spellout', $node->parts[0]->format);
        $this->assertNull($node->parts[0]->style);
    }

    public function test_parses_spellout_argument_with_style(): void
    {
        $node = $this->parser->parse('{count, spellout, %cardinal}');

        $this->assertInstanceOf(SpelloutNode::class, $node->parts[0]);
        $this->assertSame('%cardinal', $node->parts[0]->style);
    }

    public function test_parses_ordinal_argument(): void
    {
        $node = $this->parser->parse('{rank, ordinal}');

        $this->assertCount(1, $node->parts);
        $this->assertInstanceOf(OrdinalNode::class, $node->parts[0]);
        $this->assertSame('rank', $node->parts[0]->name);
        $this->assertSame('ordinal', $node->parts[0]->format);
        $this->assertNull($node->parts[0]->style);
    }

    public function test_parses_ordinal_argument_with_style(): void
    {
        $node = $this->parser->parse('{rank, ordinal, %digits}');

        $this->assertInstanceOf(OrdinalNode::class, $node->parts[0]);
        $this->assertSame('%digits', $node->parts[0]->style);
    }

    public function test_parses_duration_argument(): void
    {
        $node = $this->parser->parse('{duration, duration}');

        $this->assertCount(1, $node->parts);
        $this->assertInstanceOf(DurationNode::class, $node->parts[0]);
        $this->assertSame('duration', $node->parts[0]->name);
        $this->assertSame('duration', $node->parts[0]->format);
        $this->assertNull($node->parts[0]->style);
    }

    public function test_parses_duration_argument_with_style(): void
    {
        $node = $this->parser->parse('{duration, duration, h:mm:ss}');

        $this->assertInstanceOf(DurationNode::class, $node->parts[0]);
        $this->assertSame('h:mm:ss', $node->parts[0]->style);
    }

    public function test_collect_style_until_throws_on_nested_brace(): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('Unexpected "{" inside format style.');

        $this->parser->parse('{value, number, {nested}}');
    }

    public function test_parse_with_multiple_styles(): void
    {
        $node = $this->parser->parse('Price: {price, number, ¤#,##0.00} - Date: {date, date, MMM d, yyyy}');

        $this->assertCount(4, $node->parts);
        $this->assertInstanceOf(TextNode::class, $node->parts[0]);
        $this->assertSame('Price: ', $node->parts[0]->text);
        $this->assertInstanceOf(FormattedArgumentNode::class, $node->parts[1]);
        $this->assertSame('¤#,##0.00', $node->parts[1]->style);
        $this->assertInstanceOf(TextNode::class, $node->parts[2]);
        $this->assertSame(' - Date: ', $node->parts[2]->text);
        $this->assertInstanceOf(FormattedArgumentNode::class, $node->parts[3]);
        $this->assertSame('MMM d, yyyy', $node->parts[3]->style);
    }

    public function test_parse_with_selectordinal_and_offset(): void
    {
        $node = $this->parser->parse('{n, selectordinal, offset:1 one {1st} two {2nd} few {3rd} other {#th}}');

        $this->assertCount(1, $node->parts);
        $this->assertInstanceOf(SelectOrdinalNode::class, $node->parts[0]);
        $this->assertSame(1, $node->parts[0]->offset);
    }

    public function test_parse_with_selectordinal_without_offset(): void
    {
        $node = $this->parser->parse('{n, selectordinal, one {1st} two {2nd} other {#th}}');

        $this->assertCount(1, $node->parts);
        $this->assertInstanceOf(SelectOrdinalNode::class, $node->parts[0]);
        $this->assertNull($node->parts[0]->offset);
    }

    public function test_parse_with_multiple_explicit_selectors(): void
    {
        $node = $this->parser->parse('{n, plural, =0 {none} =1 {one} other {#}}');

        $this->assertInstanceOf(PluralNode::class, $node->parts[0]);
        $this->assertCount(3, $node->parts[0]->options);
        $this->assertSame('=0', $node->parts[0]->options[0]->selector);
        $this->assertTrue($node->parts[0]->options[0]->explicit);
        $this->assertSame(0, $node->parts[0]->options[0]->explicitValue);
        $this->assertSame('=1', $node->parts[0]->options[1]->selector);
        $this->assertTrue($node->parts[0]->options[1]->explicit);
        $this->assertSame(1, $node->parts[0]->options[1]->explicitValue);
    }

    public function test_parse_pound_node_in_plural_context(): void
    {
        $node = $this->parser->parse('{count, plural, one {You have # item} other {You have # items}}');

        $this->assertInstanceOf(PluralNode::class, $node->parts[0]);
        $this->assertInstanceOf(PoundNode::class, $node->parts[0]->options[0]->message->parts[1]);
        $this->assertInstanceOf(PoundNode::class, $node->parts[0]->options[1]->message->parts[1]);
    }

    public function test_parse_nested_plural(): void
    {
        $node = $this->parser->parse('{n, plural, one {{c, plural, one {1 item} other {# items}}} other {{c, plural, one {1 item} other {# items}}}}');

        $this->assertInstanceOf(PluralNode::class, $node->parts[0]);
        $this->assertCount(2, $node->parts[0]->options);
        $this->assertInstanceOf(PluralNode::class, $node->parts[0]->options[0]->message->parts[0]);
        $this->assertInstanceOf(PluralNode::class, $node->parts[0]->options[1]->message->parts[0]);
    }

    public function test_parse_empty_message(): void
    {
        $node = $this->parser->parse('');

        $this->assertCount(0, $node->parts);
    }

    public function test_parse_message_with_only_text(): void
    {
        $node = $this->parser->parse('Just plain text, no arguments.');

        $this->assertCount(1, $node->parts);
        $this->assertInstanceOf(TextNode::class, $node->parts[0]);
        $this->assertSame('Just plain text, no arguments.', $node->parts[0]->text);
    }

    public function test_parse_consecutive_arguments(): void
    {
        $node = $this->parser->parse('{first}{second}{third}');

        $this->assertCount(3, $node->parts);
        $this->assertInstanceOf(SimpleArgumentNode::class, $node->parts[0]);
        $this->assertSame('first', $node->parts[0]->name);
        $this->assertInstanceOf(SimpleArgumentNode::class, $node->parts[1]);
        $this->assertSame('second', $node->parts[1]->name);
        $this->assertInstanceOf(SimpleArgumentNode::class, $node->parts[2]);
        $this->assertSame('third', $node->parts[2]->name);
    }

    public function test_throws_on_unexpected_trailing_brace(): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('Unexpected "}".');

        $this->parser->parse('Hello {name}}');
    }

    public function test_parse_selector_throws_on_explicit_selector_in_select(): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('Explicit selectors are only valid for plural arguments.');

        $this->parser->parse('{gender, select, =male {Mr.} other {}}');
    }

    public function test_parse_selector_throws_on_missing_selector_keyword(): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('Expected selector keyword.');

        $this->parser->parse('{n, plural, 123 {one} other {other}}');
    }

    public function test_parse_selector_throws_on_special_character_in_select(): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('Expected selector keyword.');

        $this->parser->parse('{gender, select, @male {Mr.} other {}}');
    }

    public function test_parse_message_with_unclosed_style(): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('Expected "}" to close argument.');

        $this->parser->parse('{value, number, currency');
    }

    public function test_ast_dump_with_spellout_node(): void
    {
        $node = $this->parser->parse('{count, spellout}');

        /** @var array{parts: list<array<string, mixed>>} $ast */
        $ast = $node->accept($this->astDumper);

        $this->assertSame('Spellout', $ast['parts'][0]['type']);
        $this->assertSame('count', $ast['parts'][0]['name']);
        $this->assertSame('spellout', $ast['parts'][0]['format']);
        $this->assertNull($ast['parts'][0]['style']);
    }

    public function test_ast_dump_with_ordinal_node(): void
    {
        $node = $this->parser->parse('{rank, ordinal}');

        /** @var array{parts: list<array<string, mixed>>} $ast */
        $ast = $node->accept($this->astDumper);

        $this->assertSame('Ordinal', $ast['parts'][0]['type']);
        $this->assertSame('rank', $ast['parts'][0]['name']);
        $this->assertSame('ordinal', $ast['parts'][0]['format']);
        $this->assertNull($ast['parts'][0]['style']);
    }

    public function test_ast_dump_with_duration_node(): void
    {
        $node = $this->parser->parse('{duration, duration}');

        /** @var array{parts: list<array<string, mixed>>} $ast */
        $ast = $node->accept($this->astDumper);

        $this->assertSame('Duration', $ast['parts'][0]['type']);
        $this->assertSame('duration', $ast['parts'][0]['name']);
        $this->assertSame('duration', $ast['parts'][0]['format']);
        $this->assertNull($ast['parts'][0]['style']);
    }

    public function test_parse_valid_message_reaches_eof(): void
    {
        $node = $this->parser->parse('Hello {name}, welcome!');

        $this->assertCount(3, $node->parts);
    }

    public function test_parse_empty_message_reaches_eof(): void
    {
        $node = $this->parser->parse('');

        $this->assertCount(0, $node->parts);
    }
}
