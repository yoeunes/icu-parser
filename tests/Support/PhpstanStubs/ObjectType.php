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

final class ObjectType extends Type
{
    public function __construct(private readonly string $className) {}

    public function getClassName(): string
    {
        return $this->className;
    }

    public function isSuperTypeOf(Type $type): TrinaryLogic
    {
        if ($type instanceof self) {
            return is_a($type->className, $this->className, true)
                ? TrinaryLogic::createYes()
                : TrinaryLogic::createNo();
        }

        return TrinaryLogic::createNo();
    }
}
