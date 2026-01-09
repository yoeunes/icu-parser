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

namespace IcuParser\Cli;

use IcuParser\Cli\Command\CommandInterface;

final class Application
{
    /**
     * @var array<string, CommandInterface>
     */
    private array $commands = [];

    public function __construct(
        private readonly GlobalOptionsParser $globalOptionsParser,
        private readonly Output $output,
        private readonly CommandInterface $helpCommand,
    ) {}

    public function register(CommandInterface $command): void
    {
        $this->commands[$command->getName()] = $command;

        foreach ($command->getAliases() as $alias) {
            $this->commands[$alias] = $command;
        }
    }

    /**
     * @param array<int, string> $argv
     */
    public function run(array $argv): int
    {
        $args = $argv;
        array_shift($args);

        $parsed = $this->globalOptionsParser->parse($args);
        $this->configureOutput($parsed->options);

        if (null !== $parsed->error) {
            $this->output->write($this->output->error('Error: '.$parsed->error."\n"));

            return 1;
        }

        if ($parsed->options->help) {
            return $this->helpCommand->run(new Input('help', $parsed->args, $parsed->options), $this->output);
        }

        $commandName = $parsed->args[0] ?? 'help';
        $command = $this->getCommand($commandName);

        if (null === $command) {
            $this->output->write($this->output->error('Unknown command: '.$commandName."\n\n"));
            $this->helpCommand->run(new Input('help', [], $parsed->options), $this->output);

            return 1;
        }

        $commandArgs = array_slice($parsed->args, 1);
        $input = new Input($commandName, $commandArgs, $parsed->options);

        return $command->run($input, $this->output);
    }

    private function configureOutput(GlobalOptions $options): void
    {
        $this->output->setAnsi($this->shouldUseAnsi($options->ansi));
        $this->output->setQuiet($options->quiet);
    }

    private function shouldUseAnsi(?bool $forced): bool
    {
        return $forced ?? (\function_exists('posix_isatty') && posix_isatty(\STDOUT));
    }

    private function getCommand(string $name): ?CommandInterface
    {
        return $this->commands[$name] ?? null;
    }
}
