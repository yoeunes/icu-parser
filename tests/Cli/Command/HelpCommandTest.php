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

use IcuParser\Cli\Command\CommandInterface;
use IcuParser\Cli\Command\HelpCommand;
use IcuParser\Cli\GlobalOptions;
use IcuParser\Cli\Input;
use IcuParser\Cli\Output;
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

        $this->assertSame('Show available commands.', $command->getDescription());
    }

    public function test_set_commands(): void
    {
        $command = new HelpCommand();
        $mockCommand = $this->createMock(CommandInterface::class);
        $mockCommand->method('getName')->willReturn('test');
        $mockCommand->method('getDescription')->willReturn('Test command');

        $command->setCommands([$mockCommand]);

        // Test that commands are set by running the command
        $input = new Input('help', [], new GlobalOptions(false, false, false, false));
        $output = new Output(false, false);

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        $this->assertSame(0, $status);
        $this->assertStringContainsString('test', $content);
        $this->assertStringContainsString('Test command', $content);
    }

    public function test_run_with_no_commands(): void
    {
        $command = new HelpCommand();
        $input = new Input('help', [], new GlobalOptions(false, false, false, false));
        $output = new Output(false, false);

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        $this->assertSame(0, $status);
        $this->assertStringContainsString('Usage: icu <command> [options]', $content);
        $this->assertStringContainsString('Commands:', $content);
        $this->assertStringContainsString('Global options:', $content);
    }

    public function test_run_with_multiple_commands(): void
    {
        $command = new HelpCommand();

        $mockCommand1 = $this->createMock(CommandInterface::class);
        $mockCommand1->method('getName')->willReturn('audit');
        $mockCommand1->method('getDescription')->willReturn('Audit translations');

        $mockCommand2 = $this->createMock(CommandInterface::class);
        $mockCommand2->method('getName')->willReturn('debug');
        $mockCommand2->method('getDescription')->willReturn('Debug ICU messages');

        $mockCommand3 = $this->createMock(CommandInterface::class);
        $mockCommand3->method('getName')->willReturn('lint');
        $mockCommand3->method('getDescription')->willReturn('Lint translation files');

        $command->setCommands([$mockCommand1, $mockCommand2, $mockCommand3]);

        $input = new Input('help', [], new GlobalOptions(false, false, false, false));
        $output = new Output(false, false);

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        $this->assertSame(0, $status);
        $this->assertStringContainsString('audit', $content);
        $this->assertStringContainsString('debug', $content);
        $this->assertStringContainsString('lint', $content);
        $this->assertStringContainsString('Audit translations', $content);
        $this->assertStringContainsString('Debug ICU messages', $content);
        $this->assertStringContainsString('Lint translation files', $content);
    }

    public function test_run_with_duplicate_command_names(): void
    {
        $command = new HelpCommand();

        $mockCommand1 = $this->createMock(CommandInterface::class);
        $mockCommand1->method('getName')->willReturn('test');
        $mockCommand1->method('getDescription')->willReturn('First test command');

        $mockCommand2 = $this->createMock(CommandInterface::class);
        $mockCommand2->method('getName')->willReturn('test');
        $mockCommand2->method('getDescription')->willReturn('Second test command');

        $command->setCommands([$mockCommand1, $mockCommand2]);

        $input = new Input('help', [], new GlobalOptions(false, false, false, false));
        $output = new Output(false, false);

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        $this->assertSame(0, $status);
        // Should only show command once (no duplicates)
        $this->assertStringContainsString('test', $content);
        // Count lines that start with 'test' to avoid counting 'test' in descriptions
        $lines = explode("\n", $content);
        $testLines = array_filter($lines, fn ($line): bool => 1 === preg_match('/^\s*test\s/', (string) $line));
        $this->assertCount(1, $testLines, 'Command should only appear once');
    }

    public function test_run_shows_global_options(): void
    {
        $command = new HelpCommand();
        $input = new Input('help', [], new GlobalOptions(false, false, false, false));
        $output = new Output(false, false);

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        $this->assertSame(0, $status);
        $this->assertStringContainsString('Global options:', $content);
        $this->assertStringContainsString('--ansi        Force ANSI output.', $content);
        $this->assertStringContainsString('--no-ansi     Disable ANSI output.', $content);
        $this->assertStringContainsString('--no-banner   Hide the runtime banner.', $content);
        $this->assertStringContainsString('-q, --quiet   Suppress output.', $content);
        $this->assertStringContainsString('-h, --help    Show this help message.', $content);
    }
}
