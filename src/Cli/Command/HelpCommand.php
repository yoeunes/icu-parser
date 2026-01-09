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

final class HelpCommand implements CommandInterface
{
    /**
     * @var array<int, CommandInterface>
     */
    private array $commands = [];

    /**
     * @param array<int, CommandInterface> $commands
     */
    public function setCommands(array $commands): void
    {
        $this->commands = $commands;
    }

    public function getName(): string
    {
        return 'help';
    }

    public function getAliases(): array
    {
        return ['-h', '--help'];
    }

    public function getDescription(): string
    {
        return 'Show available commands.';
    }

    public function run(Input $input, Output $output): int
    {
        $output->write("Usage: icu <command> [options]\n\n");
        $output->write("Commands:\n");

        $seen = [];
        foreach ($this->commands as $command) {
            $name = $command->getName();
            if (isset($seen[$name])) {
                continue;
            }
            $seen[$name] = true;
            $output->write(sprintf("  %-10s %s\n", $name, $command->getDescription()));
        }

        $output->write("\nGlobal options:\n");
        $output->write("  --ansi        Force ANSI output.\n");
        $output->write("  --no-ansi     Disable ANSI output.\n");
        $output->write("  --no-banner   Hide the runtime banner.\n");
        $output->write("  -q, --quiet   Suppress output.\n");
        $output->write("  -h, --help    Show this help message.\n");

        return 0;
    }
}
