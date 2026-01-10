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

final class ConstantStringType extends Type
{
    public function __construct(private readonly string $value) {}

    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return array<int, self>
     */
    public function getConstantStrings(): array
    {
        return [$this];
    }
}
