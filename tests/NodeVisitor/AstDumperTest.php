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
        $dump = $this->dumpMessage('Hello world');

        $this->assertSame('Message', $dump['type']);
        $this->assertCount(1, $dump['parts']);
        /** @var array{type: string, text: string} $part */
        $part = $dump['parts'][0];
        $this->assertSame('Text', $part['type']);
        $this->assertSame('Hello world', $part['text']);
    }

    public function test_dumps_simple_argument(): void
    {
        $dump = $this->dumpMessage('Hello {name}');

        $this->assertSame('Message', $dump['type']);
        $this->assertCount(2, $dump['parts']);
        /** @var array{type: string, text: string} $textPart */
        $textPart = $dump['parts'][0];
        $this->assertSame('Text', $textPart['type']);
        $this->assertSame('Hello ', $textPart['text']);
        /** @var array{type: string, name: string, format: string|null} $argumentPart */
        $argumentPart = $dump['parts'][1];
        $this->assertSame('Argument', $argumentPart['type']);
        $this->assertSame('name', $argumentPart['name']);
        $this->assertNull($argumentPart['format']);
    }

    public function test_dumps_formatted_argument(): void
    {
        $dump = $this->dumpMessage('Count: {count, number}');

        $this->assertSame('Message', $dump['type']);
        $this->assertCount(2, $dump['parts']);
        /** @var array{type: string, name: string, format: string|null, style: string|null} $argumentPart */
        $argumentPart = $dump['parts'][1];
        $this->assertSame('Argument', $argumentPart['type']);
        $this->assertSame('count', $argumentPart['name']);
        $this->assertSame('number', $argumentPart['format']);
        $this->assertNull($argumentPart['style']);
    }

    public function test_dumps_plural(): void
    {
        $dump = $this->dumpMessage('{count, plural, one {# item} other {# items}}');

        $this->assertSame('Message', $dump['type']);
        /** @var array{type: string, name: string, offset: int|null, options: list<array<string, mixed>>} $pluralDump */
        $pluralDump = $dump['parts'][0];
        $this->assertSame('Plural', $pluralDump['type']);
        $this->assertSame('count', $pluralDump['name']);
        $this->assertNull($pluralDump['offset']);
        $this->assertCount(2, $pluralDump['options']);
        /** @var array{type: string, selector: string, explicit: bool} $optionDump */
        $optionDump = $pluralDump['options'][0];
        $this->assertSame('Option', $optionDump['type']);
        $this->assertSame('one', $optionDump['selector']);
        $this->assertFalse($optionDump['explicit']);
    }

    public function test_dumps_select(): void
    {
        $dump = $this->dumpMessage('{gender, select, male {Mr.} female {Ms.}}');

        $this->assertSame('Message', $dump['type']);
        /** @var array{type: string, name: string, options: list<array<string, mixed>>} $selectDump */
        $selectDump = $dump['parts'][0];
        $this->assertSame('Select', $selectDump['type']);
        $this->assertSame('gender', $selectDump['name']);
        $this->assertCount(2, $selectDump['options']);
    }

    public function test_dumps_pound(): void
    {
        $dump = $this->dumpMessage('{count, plural, one {# item} other {# items}}');

        // Find the pound in the options
        /** @var array{options: list<array<string, mixed>>} $pluralDump */
        $pluralDump = $dump['parts'][0];
        /** @var array{message: array<string, mixed>} $optionDump */
        $optionDump = $pluralDump['options'][0];
        /** @var array{type: string, parts: list<array<string, mixed>>} $optionMessage */
        $optionMessage = $optionDump['message'];
        $this->assertSame('Message', $optionMessage['type']);
        $this->assertCount(2, $optionMessage['parts']);
        /** @var array{type: string} $poundPart */
        $poundPart = $optionMessage['parts'][0];
        $this->assertSame('Pound', $poundPart['type']);
        /** @var array{type: string, text: string} $textPart */
        $textPart = $optionMessage['parts'][1];
        $this->assertSame('Text', $textPart['type']);
        $this->assertSame(' item', $textPart['text']);
    }

    public function test_dumps_choice(): void
    {
        $dump = $this->dumpMessage('{value, choice, 0#none|1<one}');

        $this->assertSame('Message', $dump['type']);
        /** @var array{type: string, name: string, options: list<array<string, mixed>>} $choiceDump */
        $choiceDump = $dump['parts'][0];
        $this->assertSame('Choice', $choiceDump['type']);
        $this->assertSame('value', $choiceDump['name']);
        $this->assertCount(2, $choiceDump['options']);
        $this->assertEqualsWithDelta(0.0, $choiceDump['options'][0]['limit'], \PHP_FLOAT_EPSILON);
        $this->assertFalse($choiceDump['options'][0]['isExclusive']);
        $this->assertTrue($choiceDump['options'][1]['isExclusive']);
    }

    /**
     * @return array{type: string, parts: list<array<string, mixed>>}
     */
    private function dumpMessage(string $message): array
    {
        $node = $this->parser->parse($message);
        /** @var array{type: string, parts: list<array<string, mixed>>} $dump */
        $dump = $this->dumper->dump($node);

        return $dump;
    }
}
