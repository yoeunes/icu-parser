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

namespace IcuParser\Tests\Cli\Command;

use IcuParser\Cli\Command\HighlightCommand;
use IcuParser\Cli\GlobalOptions;
use IcuParser\Cli\Input;
use IcuParser\Cli\Output;
use PHPUnit\Framework\TestCase;

final class HighlightCommandTest extends TestCase
{
    public function test_get_name(): void
    {
        $command = new HighlightCommand();

        $this->assertSame('highlight', $command->getName());
    }

    public function test_get_aliases(): void
    {
        $command = new HighlightCommand();

        $this->assertSame([], $command->getAliases());
    }

    public function test_get_description(): void
    {
        $command = new HighlightCommand();

        $this->assertSame('Highlight an ICU message string.', $command->getDescription());
    }

    public function test_run_with_missing_message_returns_error(): void
    {
        $command = new HighlightCommand();
        $input = new Input('highlight', [], new GlobalOptions(false, false, false, false));
        $output = new Output(false, false, '#', '-');

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        $this->assertSame(1, $status);
        $this->assertStringContainsString('Error: Missing ICU message string.', $content);
        $this->assertStringContainsString('Usage: icu highlight', $content);
    }

    public function test_run_with_message_outputs_highlighted_string(): void
    {
        $command = new HighlightCommand();
        $input = new Input('highlight', ['Hello {name}'], new GlobalOptions(false, false, false, false));
        $output = new Output(false, false, '#', '-');

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        $this->assertSame(0, $status);
        $this->assertStringContainsString('Hello {name}', $content);
    }

    public function test_run_with_banner_enabled(): void
    {
        $command = new HighlightCommand();
        $input = new Input('highlight', ['Hello {name}'], new GlobalOptions(false, false, false, true));
        $output = new Output(false, false, '#', '-');

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        $this->assertSame(0, $status);
        $this->assertStringContainsString('Highlight', $content);
        $this->assertStringContainsString('ICU MessageFormat highlighting', $content);
        $this->assertStringContainsString('Intl', $content);
        $this->assertStringContainsString('ICU', $content);
        $this->assertStringContainsString('Locale', $content);
    }
}
