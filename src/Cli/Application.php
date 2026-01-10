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
        $parsed = $this->parseArguments($argv);
        $options = $parsed->options;
        $args = $parsed->args;

        $this->configureOutput($options);

        if ($options->help) {
            return $this->showHelpFor($args, $options);
        }

        $commandName = $this->resolveCommandName($args);
        if (null === $commandName) {
            return $this->showHelpAndExit($options);
        }

        $command = $this->getCommand($commandName);
        if (null === $command) {
            return $this->handleUnknownCommand($commandName, $options);
        }

        $commandArgs = $this->extractCommandArgs($args);
        $input = new Input($commandName, $commandArgs, $options);

        return $command->run($input, $this->output);
    }

    /**
     * @param array<int, string> $argv
     */
    private function parseArguments(array $argv): ParsedGlobalOptions
    {
        $args = $argv;
        array_shift($args);

        return $this->globalOptionsParser->parse($args);
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

    /**
     * @param array<int, string> $args
     */
    private function showHelpFor(array $args, GlobalOptions $options): int
    {
        $targetCommand = $args[0] ?? null;

        return $this->helpCommand->run(
            new Input('help', null !== $targetCommand ? [$targetCommand] : [], $options),
            $this->output,
        );
    }

    /**
     * @param array<int, string> $args
     */
    private function resolveCommandName(array $args): ?string
    {
        return $args[0] ?? null;
    }

    private function showHelpAndExit(GlobalOptions $options): int
    {
        $this->helpCommand->run(new Input('help', [], $options), $this->output);

        return 1;
    }

    private function handleUnknownCommand(string $commandName, GlobalOptions $options): int
    {
        $this->output->write($this->output->error("Unknown command: {$commandName}\n\n"));
        $this->helpCommand->run(new Input('help', [], $options), $this->output);

        return 1;
    }

    private function getCommand(string $name): ?CommandInterface
    {
        return $this->commands[$name] ?? null;
    }

    /**
     * @param array<int, string> $args
     *
     * @return array<int, string>
     */
    private function extractCommandArgs(array $args): array
    {
        return \array_slice($args, 1);
    }
}
