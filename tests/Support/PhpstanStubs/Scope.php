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

namespace IcuParser\Tests\Support\PhpstanStubs;

final class Scope
{
    /**
     * @param array<string, Type> $types
     */
    public function __construct(private array $types = []) {}

    public function getType(object $expr): Type
    {
        return $this->types[spl_object_hash($expr)] ?? new Type();
    }
}
