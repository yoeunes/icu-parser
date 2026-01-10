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

use IcuParser\Cli\GlobalOptions;
use IcuParser\Cli\Input;
use IcuParser\Cli\Output;
use IcuParser\Cli\Command\HelpCommand;
use PHPUnit\Framework\TestCase;

final class HelpCommandTest extends TestCase
{
    public function test_get_name(): void
    {
        $command = new HelpCommand();

        $this->assertSame('help', $command->getName());
    }

    public function test_get_aliases(): void
    {
        $command = new HelpCommand();

        $this->assertSame(['-h', '--help'], $command->getAliases());
    }

    public function test_get_description(): void
    {
        $command = new HelpCommand();

        $this->assertSame('Display this help message', $command->getDescription());
    }

    public function test_run_shows_help_sections(): void
    {
        $command = new HelpCommand();
        $input = new Input('help', [], new GlobalOptions(false, false, false, false));
        $output = new Output(false, false, '#', '-');

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        $this->assertSame(0, $status);
        $this->assertStringContainsString('Description:', $content);
        $this->assertStringContainsString('Usage:', $content);
        $this->assertStringContainsString('Commands:', $content);
        $this->assertStringContainsString('Global Options:', $content);
        $this->assertStringContainsString('Examples:', $content);
    }

    public function test_run_shows_commands(): void
    {
        $command = new HelpCommand();
        $input = new Input('help', [], new GlobalOptions(false, false, false, false));
        $output = new Output(false, false, '#', '-');

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        $this->assertSame(0, $status);
        $this->assertStringContainsString('debug', $content);
        $this->assertStringContainsString('Parse an ICU message and dump its AST and parameters', $content);
        $this->assertStringContainsString('audit', $content);
        $this->assertStringContainsString('highlight', $content);
        $this->assertStringContainsString('lint', $content);
        $this->assertStringContainsString('version', $content);
        $this->assertStringContainsString('self-update', $content);
    }

    public function test_run_shows_global_options(): void
    {
        $command = new HelpCommand();
        $input = new Input('help', [], new GlobalOptions(false, false, false, false));
        $output = new Output(false, false, '#', '-');

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        $this->assertSame(0, $status);
        $this->assertStringContainsString('Global Options:', $content);
        $this->assertStringContainsString('--ansi', $content);
        $this->assertStringContainsString('--no-ansi', $content);
        $this->assertStringContainsString('--quiet', $content);
        $this->assertStringContainsString('--no-visuals', $content);
    }

    public function test_run_shows_lint_options(): void
    {
        $command = new HelpCommand();
        $input = new Input('help', [], new GlobalOptions(false, false, false, false));
        $output = new Output(false, false, '#', '-');

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        $this->assertSame(0, $status);
        $this->assertStringContainsString('Lint Options:', $content);
        $this->assertStringContainsString('--format <format>', $content);
        $this->assertStringContainsString('--output <file>', $content);
        $this->assertStringContainsString('--verbose', $content);
    }

    public function test_run_shows_examples(): void
    {
        $command = new HelpCommand();
        $input = new Input('help', [], new GlobalOptions(false, false, false, false));
        $output = new Output(false, false, '#', '-');

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        $this->assertSame(0, $status);
        $this->assertStringContainsString('Examples:', $content);
        $this->assertStringContainsString('debug', $content);
        $this->assertStringContainsString('audit', $content);
        $this->assertStringContainsString('highlight', $content);
        $this->assertStringContainsString('lint', $content);
    }

    public function test_run_with_specific_command_shows_command_help(): void
    {
        $command = new HelpCommand();
        $input = new Input('help', ['lint'], new GlobalOptions(false, false, false, false));
        $output = new Output(false, false, '#', '-');

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        $this->assertSame(0, $status);
        $this->assertStringContainsString('Description:', $content);
        $this->assertStringContainsString('Usage:', $content);
        $this->assertStringContainsString('lint', $content);
    }

    public function test_run_with_unknown_command_shows_error(): void
    {
        $command = new HelpCommand();
        $input = new Input('help', ['unknown-command'], new GlobalOptions(false, false, false, false));
        $output = new Output(false, false, '#', '-');

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        $this->assertSame(1, $status);
        $this->assertStringContainsString('Unknown command:', $content);
    }

    public function test_run_with_visuals_disabled_shows_banner(): void
    {
        $command = new HelpCommand();
        // With visuals disabled (4th param in GlobalOptions), no banner should be shown
        $input = new Input('help', [], new GlobalOptions(false, false, false, false));
        $output = new Output(false, false, '#', '-');

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        $this->assertSame(0, $status);
        // Without visuals, banner should not be shown
        $this->assertStringNotContainsString('IcuParser', $content);
    }

    public function test_run_with_visuals_enabled_shows_banner(): void
    {
        $command = new HelpCommand();
        // With visuals enabled (4th param in GlobalOptions), banner should be shown
        $input = new Input('help', [], new GlobalOptions(false, false, false, true));
        $output = new Output(false, false, '#', '-');

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        $this->assertSame(0, $status);
        // With visuals, banner should be shown
        $this->assertStringContainsString('IcuParser', $content);
        $this->assertStringContainsString('Runtime', $content);
        $this->assertStringContainsString('Command', $content);
    }
}
