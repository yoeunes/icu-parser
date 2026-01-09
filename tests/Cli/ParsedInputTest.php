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
use IcuParser\Cli\ParsedInput;
use PHPUnit\Framework\TestCase;

final class ParsedInputTest extends TestCase
{
    public function test_constructor(): void
    {
        $options = new GlobalOptions(true, false, true, false);
        $parsed = new ParsedInput($options, ['arg1'], 'error message');

        $this->assertSame($options, $parsed->options);
        $this->assertSame(['arg1'], $parsed->args);
        $this->assertSame('error message', $parsed->error);
    }
}
