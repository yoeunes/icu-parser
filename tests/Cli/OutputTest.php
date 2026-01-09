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

use IcuParser\Cli\Output;
use PHPUnit\Framework\TestCase;

final class OutputTest extends TestCase
{
    public function test_write_when_not_quiet(): void
    {
        $output = new Output(true, false);

        ob_start();
        $output->write('test');
        $result = ob_get_clean();

        $this->assertSame('test', $result);
    }

    public function test_write_when_quiet(): void
    {
        $output = new Output(true, true);

        ob_start();
        $output->write('test');
        $result = ob_get_clean();

        $this->assertEmpty($result);
    }

    public function test_color_with_ansi(): void
    {
        $output = new Output(true, false);

        $result = $output->color('text', Output::RED);

        $this->assertSame("\033[31mtext\033[0m", $result);
    }

    public function test_color_without_ansi(): void
    {
        $output = new Output(false, false);

        $result = $output->color('text', Output::RED);

        $this->assertSame('text', $result);
    }

    public function test_success(): void
    {
        $output = new Output(true, false);

        $result = $output->success('ok');

        $this->assertSame("\033[32mok\033[0m", $result);
    }

    public function test_error(): void
    {
        $output = new Output(true, false);

        $result = $output->error('fail');

        $this->assertSame("\033[31mfail\033[0m", $result);
    }

    public function test_warning(): void
    {
        $output = new Output(true, false);

        $result = $output->warning('warn');

        $this->assertSame("\033[33mwarn\033[0m", $result);
    }

    public function test_info(): void
    {
        $output = new Output(true, false);

        $result = $output->info('info');

        $this->assertSame("\033[34minfo\033[0m", $result);
    }

    public function test_bold(): void
    {
        $output = new Output(true, false);

        $result = $output->bold('bold');

        $this->assertSame("\033[1mbold\033[0m", $result);
    }

    public function test_dim(): void
    {
        $output = new Output(true, false);

        $result = $output->dim('dim');

        $this->assertSame("\033[90mdim\033[0m", $result);
    }

    public function test_accent(): void
    {
        $output = new Output(true, false);

        $result = $output->accent('accent');

        $this->assertSame("\033[36maccent\033[0m", $result);
    }

    public function test_is_ansi(): void
    {
        $output = new Output(true, false);

        $this->assertTrue($output->isAnsi());
    }

    public function test_set_ansi(): void
    {
        $output = new Output(false, false);

        $output->setAnsi(true);

        $this->assertTrue($output->isAnsi());
    }

    public function test_is_quiet(): void
    {
        $output = new Output(true, true);

        $this->assertTrue($output->isQuiet());
    }

    public function test_set_quiet(): void
    {
        $output = new Output(true, false);

        $output->setQuiet(true);

        $this->assertTrue($output->isQuiet());
    }
}
