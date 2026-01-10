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

final class ConstantArrayType extends Type
{
    /**
     * @param array<int, Type> $keyTypes
     * @param array<int, Type> $valueTypes
     */
    public function __construct(private readonly array $keyTypes, private readonly array $valueTypes) {}

    /**
     * @return array<int, Type>
     */
    public function getKeyTypes(): array
    {
        return $this->keyTypes;
    }

    /**
     * @return array<int, Type>
     */
    public function getValueTypes(): array
    {
        return $this->valueTypes;
    }
}
