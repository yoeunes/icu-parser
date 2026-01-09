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

namespace IcuParser\Tests\Type;

use IcuParser\Parser\Parser;
use IcuParser\Type\ParameterType;
use IcuParser\Type\TypeInferer;
use PHPUnit\Framework\TestCase;

final class TypeInfererTest extends TestCase
{
    private TypeInferer $inferer;

    private Parser $parser;

    protected function setUp(): void
    {
        $this->inferer = new TypeInferer();
        $this->parser = new Parser();
    }

    public function test_infers_string_for_simple_argument(): void
    {
        $node = $this->parser->parse('Hello {name}');
        $types = $this->inferer->infer($node);

        $this->assertSame(ParameterType::STRING, $types->get('name'));
    }

    public function test_infers_types_for_formatted_arguments(): void
    {
        $node = $this->parser->parse('Count: {count, number}, Date: {date, date}');
        $types = $this->inferer->infer($node);

        $this->assertSame(ParameterType::NUMBER, $types->get('count'));
        $this->assertSame(ParameterType::DATETIME, $types->get('date'));
    }

    public function test_infers_string_for_select(): void
    {
        $node = $this->parser->parse('{gender, select, male {Mr.} female {Ms.}}');
        $types = $this->inferer->infer($node);

        $this->assertSame(ParameterType::STRING, $types->get('gender'));
    }

    public function test_infers_number_for_plural(): void
    {
        $node = $this->parser->parse('{count, plural, one {# item} other {# items}}');
        $types = $this->inferer->infer($node);

        $this->assertSame(ParameterType::NUMBER, $types->get('count'));
    }

    public function test_infers_number_for_select_ordinal(): void
    {
        $node = $this->parser->parse('{count, selectordinal, one {#st} other {#th}}');
        $types = $this->inferer->infer($node);

        $this->assertSame(ParameterType::NUMBER, $types->get('count'));
    }

    public function test_maps_unknown_format_to_string(): void
    {
        $node = $this->parser->parse('{value, unknown}');
        $types = $this->inferer->infer($node);

        $this->assertSame(ParameterType::STRING, $types->get('value'));
    }
}
