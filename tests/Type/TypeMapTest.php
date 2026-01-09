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

use IcuParser\Type\ParameterType;
use IcuParser\Type\TypeMap;
use PHPUnit\Framework\TestCase;

final class TypeMapTest extends TestCase
{
    public function test_add_and_get(): void
    {
        $map = new TypeMap();
        $map->add('name', ParameterType::STRING);

        $this->assertSame(ParameterType::STRING, $map->get('name'));
        $this->assertNotInstanceOf(ParameterType::class, $map->get('missing'));
    }

    public function test_add_merges_to_mixed(): void
    {
        $map = new TypeMap();
        $map->add('value', ParameterType::STRING);
        $map->add('value', ParameterType::NUMBER);

        $this->assertSame(ParameterType::MIXED, $map->get('value'));
    }

    public function test_all(): void
    {
        $map = new TypeMap();
        $map->add('a', ParameterType::STRING);
        $map->add('b', ParameterType::NUMBER);

        $all = $map->all();
        $this->assertSame(['a' => ParameterType::STRING, 'b' => ParameterType::NUMBER], $all);
    }

    public function test_json_serialize(): void
    {
        $map = new TypeMap();
        $map->add('name', ParameterType::STRING);

        $json = $map->jsonSerialize();
        $this->assertSame(['name' => 'string'], $json);
    }

    public function test_add_when_existing_type_is_mixed(): void
    {
        $map = new TypeMap();
        
        // First, create a mixed type by adding different types
        $map->add('value', ParameterType::STRING);
        $map->add('value', ParameterType::NUMBER);
        $this->assertSame(ParameterType::MIXED, $map->get('value'));
        
        // Now add another type to the already mixed parameter
        $map->add('value', ParameterType::DATETIME);
        
        // Should still be mixed (covers line 35-37 early return)
        $this->assertSame(ParameterType::MIXED, $map->get('value'));
    }

    public function test_add_when_existing_type_is_same_as_new_type(): void
    {
        $map = new TypeMap();
        
        // Add a type first
        $map->add('name', ParameterType::STRING);
        $this->assertSame(ParameterType::STRING, $map->get('name'));
        
        // Add the same type again (covers line 35-37 early return)
        $map->add('name', ParameterType::STRING);
        
        // Should still be the same type
        $this->assertSame(ParameterType::STRING, $map->get('name'));
    }
}
