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

namespace IcuParser\Cli;

final readonly class GlobalOptions
{
    public function __construct(
        public bool $quiet,
        public ?bool $ansi,
        public bool $help,
        public bool $visuals,
    ) {}
}
