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

use IcuParser\Cli\ConsoleStyle;
use IcuParser\Cli\Output;
use PHPUnit\Framework\TestCase;

final class ConsoleStyleTest extends TestCase
{
    private Output $output;

    private ConsoleStyle $style;

    protected function setUp(): void
    {
        $this->output = new Output(true, false); // ansi on, not quiet
        $this->style = new ConsoleStyle($this->output);
    }

    public function test_render_banner_with_visuals(): void
    {
        ob_start();
        $this->style->renderBanner('audit', ['Extra' => 'value'], 'High-performance ICU MessageFormat parser');
        $output = ob_get_clean();

        $this->assertStringContainsString('IcuParser', (string) $output);
        $this->assertStringContainsString('0.1.0', (string) $output);
        $this->assertStringContainsString('Younes ENNAJI', (string) $output);
        $this->assertStringContainsString('High-performance ICU MessageFormat parser', (string) $output);
        $this->assertStringContainsString('Runtime', (string) $output);
        $this->assertStringContainsString('Command', (string) $output);
        $this->assertStringContainsString('audit', (string) $output);
        $this->assertStringContainsString('Extra', (string) $output);
        $this->assertStringContainsString('value', (string) $output);
    }

    public function test_render_banner_without_visuals(): void
    {
        $style = new ConsoleStyle($this->output, false);

        ob_start();
        $style->renderBanner('audit');
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function test_render_section(): void
    {
        ob_start();
        $this->style->renderSection('Section Title');
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('Section Title', $output);
        $this->assertStringStartsWith('  ', $output);
    }

    public function test_render_key_value_block(): void
    {
        ob_start();
        $this->style->renderKeyValueBlock(['Key1' => 'Value1', 'Key2' => 'Value2'], 4);
        $output = ob_get_clean();

        $this->assertStringContainsString('Key1', (string) $output);
        $this->assertStringContainsString('Value1', (string) $output);
        $this->assertStringContainsString('Key2', (string) $output);
        $this->assertStringContainsString('Value2', (string) $output);
    }

    public function test_render_key_value_block_empty(): void
    {
        ob_start();
        $this->style->renderKeyValueBlock([]);
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }
}
