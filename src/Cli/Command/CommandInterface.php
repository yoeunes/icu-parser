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

namespace IcuParser\Cli\Command;

use IcuParser\Cli\Input;
use IcuParser\Cli\Output;

interface CommandInterface
{
    public function getName(): string;

    /**
     * @return array<int, string>
     */
    public function getAliases(): array;

    public function getDescription(): string;

    public function run(Input $input, Output $output): int;
}
