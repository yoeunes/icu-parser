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

final class StubType extends Type
{
    /**
     * @param array<int, ConstantStringType> $constantStrings
     * @param array<int, ConstantArrayType>  $constantArrays
     */
    public function __construct(
        private readonly bool $integer = false,
        private readonly bool $float = false,
        private readonly bool $string = false,
        private readonly bool $null = false,
        private readonly array $constantStrings = [],
        private readonly array $constantArrays = [],
    ) {}

    public function isInteger(): TrinaryLogic
    {
        return $this->integer
            ? TrinaryLogic::createYes()
            : TrinaryLogic::createNo();
    }

    public function isFloat(): TrinaryLogic
    {
        return $this->float
            ? TrinaryLogic::createYes()
            : TrinaryLogic::createNo();
    }

    public function isString(): TrinaryLogic
    {
        return $this->string
            ? TrinaryLogic::createYes()
            : TrinaryLogic::createNo();
    }

    public function isNull(): TrinaryLogic
    {
        return $this->null
            ? TrinaryLogic::createYes()
            : TrinaryLogic::createNo();
    }

    /**
     * @return array<int, ConstantStringType>
     */
    public function getConstantStrings(): array
    {
        return $this->constantStrings;
    }

    /**
     * @return array<int, ConstantArrayType>
     */
    public function getConstantArrays(): array
    {
        return $this->constantArrays;
    }
}
