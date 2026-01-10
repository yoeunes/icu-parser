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

use IcuParser\Cli\Command\DebugCommand;
use IcuParser\Cli\GlobalOptions;
use IcuParser\Cli\Input;
use IcuParser\Cli\Output;
use PHPUnit\Framework\TestCase;

final class DebugCommandTest extends TestCase
{
    public function test_get_name(): void
    {
        $command = new DebugCommand();

        $this->assertSame('debug', $command->getName());
    }

    public function test_get_aliases(): void
    {
        $command = new DebugCommand();

        $this->assertSame([], $command->getAliases());
    }

    public function test_get_description(): void
    {
        $command = new DebugCommand();

        $this->assertSame('Parse an ICU message and dump its AST and parameters.', $command->getDescription());
    }

    public function test_run_with_missing_message_returns_error(): void
    {
        $command = new DebugCommand();
        $input = new Input('debug', [], new GlobalOptions(false, false, false, false));
        $output = new Output(false, false, '#', '-');

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        $this->assertSame(1, $status);
        $this->assertStringContainsString('Error: Missing ICU message string.', $content);
        $this->assertStringContainsString('Usage: icu debug \'<message>\'', $content);
    }

    public function test_run_with_simple_message(): void
    {
        $command = new DebugCommand();
        $input = new Input('debug', ['Hello {name}'], new GlobalOptions(false, false, false, false));
        $output = new Output(false, false, '#', '-');

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        $this->assertSame(0, $status);
        $this->assertStringContainsString('AST', $content);
        $this->assertStringContainsString('Parameters', $content);
        $this->assertStringContainsString('name', $content);
        $this->assertStringContainsString('string', $content);
    }

    public function test_run_with_plural_message(): void
    {
        $command = new DebugCommand();
        $input = new Input('debug', ['{count, plural, one {# item} other {# items}}'], new GlobalOptions(false, false, false, false));
        $output = new Output(false, false, '#', '-');

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        $this->assertSame(0, $status);
        $this->assertStringContainsString('AST', $content);
        $this->assertStringContainsString('Parameters', $content);
        $this->assertStringContainsString('count', $content);
        $this->assertStringContainsString('number', $content);
    }

    public function test_run_with_invalid_message_returns_error(): void
    {
        $command = new DebugCommand();
        $input = new Input('debug', ['Hello {unclosed'], new GlobalOptions(false, false, false, false));
        $output = new Output(false, false, '#', '-');

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        $this->assertSame(1, $status);
        $this->assertStringContainsString('Error:', $content);
    }

    public function test_run_with_banner_enabled(): void
    {
        $command = new DebugCommand();
        $input = new Input('debug', ['Hello {name}'], new GlobalOptions(false, false, false, true));
        $output = new Output(false, false, '#', '-');

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        $this->assertSame(0, $status);
        $this->assertStringContainsString('Debug', $content);
        $this->assertStringContainsString('ICU MessageFormat diagnostics', $content);
        $this->assertStringContainsString('Intl', $content);
        $this->assertStringContainsString('ICU', $content);
        $this->assertStringContainsString('Locale', $content);
    }

    public function test_run_with_message_containing_no_parameters(): void
    {
        $command = new DebugCommand();
        $input = new Input('debug', ['Hello World'], new GlobalOptions(false, false, false, false));
        $output = new Output(false, false, '#', '-');

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        $this->assertSame(0, $status);
        $this->assertStringContainsString('AST', $content);
        $this->assertStringContainsString('Parameters', $content);
        $this->assertStringContainsString('(none)', $content);
    }

    public function test_run_with_select_message(): void
    {
        $command = new DebugCommand();
        $input = new Input('debug', ['{gender, select, male {He} female {She} other {They} } likes it.'], new GlobalOptions(false, false, false, false));
        $output = new Output(false, false, '#', '-');

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        $this->assertSame(0, $status);
        $this->assertStringContainsString('AST', $content);
        $this->assertStringContainsString('Parameters', $content);
        $this->assertStringContainsString('gender', $content);
        $this->assertStringContainsString('string', $content);
    }
}
