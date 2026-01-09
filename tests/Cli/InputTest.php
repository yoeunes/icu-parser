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

namespace IcuParser\Tests\Cli;

use IcuParser\Cli\GlobalOptions;
use IcuParser\Cli\Input;
use PHPUnit\Framework\TestCase;

final class InputTest extends TestCase
{
    public function test_constructor(): void
    {
        $globalOptions = new GlobalOptions(true, false, true, false);
        $input = new Input('test', ['arg1', 'arg2'], $globalOptions);

        $this->assertSame('test', $input->command);
        $this->assertSame(['arg1', 'arg2'], $input->args);
        $this->assertSame($globalOptions, $input->globalOptions);
    }
}
