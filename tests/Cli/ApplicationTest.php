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

use IcuParser\Cli\Application;
use IcuParser\Cli\Command\CommandInterface;
use IcuParser\Cli\GlobalOptionsParser;
use IcuParser\Cli\Output;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ApplicationTest extends TestCase
{
    private CommandInterface&MockObject $helpCommand;

    private Application $app;

    protected function setUp(): void
    {
        $parser = new GlobalOptionsParser();
        $output = new Output(true, false); // ansi on, not quiet
        $this->helpCommand = $this->createMock(CommandInterface::class);
        $this->helpCommand->method('getName')->willReturn('help');
        $this->helpCommand->method('getAliases')->willReturn([]);
        $this->app = new Application($parser, $output, $this->helpCommand);
        $this->app->register($this->helpCommand);
    }

    public function test_run_with_parse_error(): void
    {
        // Use unknown option to trigger error
        ob_start();
        $result = $this->app->run(['script', '--unknown']);
        $output = ob_get_clean();

        $this->assertStringContainsString('Error: Unknown option: --unknown', (string) $output);
        $this->assertSame(1, $result);
    }

    public function test_run_with_help(): void
    {
        $this->helpCommand->method('run')->willReturn(0);
        $this->helpCommand->method('getName')->willReturn('help');
        $this->helpCommand->method('getAliases')->willReturn([]);

        ob_start();
        $result = $this->app->run(['script', '--help', 'arg']);
        $output = ob_get_clean();

        $this->assertSame(0, $result);
    }

    public function test_run_with_unknown_command(): void
    {
        $this->helpCommand->method('run')->willReturn(0);
        $this->helpCommand->method('getName')->willReturn('help');
        $this->helpCommand->method('getAliases')->willReturn([]);

        ob_start();
        $result = $this->app->run(['script', 'unknown']);
        $output = ob_get_clean();

        $this->assertStringContainsString('Unknown command: unknown', (string) $output);
        $this->assertSame(1, $result);
    }

    public function test_run_with_valid_command(): void
    {
        /** @var CommandInterface&MockObject $command */
        $command = $this->createMock(CommandInterface::class);
        $command->method('getName')->willReturn('test');
        $command->method('getAliases')->willReturn([]);
        $command->method('run')->willReturn(42);
        $this->app->register($command);

        $result = $this->app->run(['script', 'test', 'arg1', 'arg2']);

        $this->assertSame(42, $result);
    }

    public function test_run_defaults_to_help(): void
    {
        $this->helpCommand->method('run')->willReturn(0);
        $this->helpCommand->method('getName')->willReturn('help');
        $this->helpCommand->method('getAliases')->willReturn([]);

        $result = $this->app->run(['script']);

        $this->assertSame(0, $result);
    }
}
