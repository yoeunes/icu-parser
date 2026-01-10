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

class Type
{
    public function isInteger(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function isFloat(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function isString(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function isNull(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    /**
     * @return array<int, ConstantStringType>
     */
    public function getConstantStrings(): array
    {
        return [];
    }

    /**
     * @return array<int, ConstantArrayType>
     */
    public function getConstantArrays(): array
    {
        return [];
    }
}
