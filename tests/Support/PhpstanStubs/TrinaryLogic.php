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

final readonly class TrinaryLogic
{
    private function __construct(private bool $yes) {}

    public static function createYes(): self
    {
        return new self(true);
    }

    public static function createNo(): self
    {
        return new self(false);
    }

    public function yes(): bool
    {
        return $this->yes;
    }
}
