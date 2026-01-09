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

namespace IcuParser\Tests\NodeVisitor;

use IcuParser\NodeVisitor\AstDumper;
use IcuParser\Parser\Parser;
use PHPUnit\Framework\TestCase;

final class AstDumperTest extends TestCase
{
    private AstDumper $dumper;

    private Parser $parser;

    protected function setUp(): void
    {
        $this->dumper = new AstDumper();
        $this->parser = new Parser();
    }

    public function test_dumps_simple_message(): void
    {
        $node = $this->parser->parse('Hello world');
        $dump = $this->dumper->dump($node);

        $this->assertSame('Message', $dump['type']);
        $this->assertCount(1, $dump['parts']);
        $this->assertSame('Text', $dump['parts'][0]['type']);
        $this->assertSame('Hello world', $dump['parts'][0]['text']);
    }

    public function test_dumps_simple_argument(): void
    {
        $node = $this->parser->parse('Hello {name}');
        $dump = $this->dumper->dump($node);

        $this->assertSame('Message', $dump['type']);
        $this->assertCount(2, $dump['parts']);
        $this->assertSame('Text', $dump['parts'][0]['type']);
        $this->assertSame('Hello ', $dump['parts'][0]['text']);
        $this->assertSame('Argument', $dump['parts'][1]['type']);
        $this->assertSame('name', $dump['parts'][1]['name']);
        $this->assertNull($dump['parts'][1]['format']);
    }

    public function test_dumps_formatted_argument(): void
    {
        $node = $this->parser->parse('Count: {count, number}');
        $dump = $this->dumper->dump($node);

        $this->assertSame('Message', $dump['type']);
        $this->assertCount(2, $dump['parts']);
        $this->assertSame('Argument', $dump['parts'][1]['type']);
        $this->assertSame('count', $dump['parts'][1]['name']);
        $this->assertSame('number', $dump['parts'][1]['format']);
        $this->assertNull($dump['parts'][1]['style']);
    }

    public function test_dumps_plural(): void
    {
        $node = $this->parser->parse('{count, plural, one {# item} other {# items}}');
        $dump = $this->dumper->dump($node);

        $this->assertSame('Message', $dump['type']);
        $pluralDump = $dump['parts'][0];
        $this->assertSame('Plural', $pluralDump['type']);
        $this->assertSame('count', $pluralDump['name']);
        $this->assertNull($pluralDump['offset']);
        $this->assertCount(2, $pluralDump['options']);
        $this->assertSame('Option', $pluralDump['options'][0]['type']);
        $this->assertSame('one', $pluralDump['options'][0]['selector']);
        $this->assertFalse($pluralDump['options'][0]['explicit']);
    }

    public function test_dumps_select(): void
    {
        $node = $this->parser->parse('{gender, select, male {Mr.} female {Ms.}}');
        $dump = $this->dumper->dump($node);

        $this->assertSame('Message', $dump['type']);
        $selectDump = $dump['parts'][0];
        $this->assertSame('Select', $selectDump['type']);
        $this->assertSame('gender', $selectDump['name']);
        $this->assertCount(2, $selectDump['options']);
    }

    public function test_dumps_pound(): void
    {
        $node = $this->parser->parse('{count, plural, one {# item} other {# items}}');
        $dump = $this->dumper->dump($node);

        // Find the pound in the options
        $pluralDump = $dump['parts'][0];
        $optionDump = $pluralDump['options'][0]['message'];
        $this->assertSame('Message', $optionDump['type']);
        $this->assertCount(2, $optionDump['parts']);
        $this->assertSame('Pound', $optionDump['parts'][0]['type']);
        $this->assertSame('Text', $optionDump['parts'][1]['type']);
        $this->assertSame(' item', $optionDump['parts'][1]['text']);
    }
}
