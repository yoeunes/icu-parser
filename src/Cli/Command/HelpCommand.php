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

use IcuParser\Cli\ConsoleStyle;
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
        return 'Display this help message';
    }

    public function run(Input $input, Output $output): int
    {
        $binary = $this->resolveInvocation();
        $style = new ConsoleStyle($output, $input->globalOptions->visuals);
        $this->renderHeader($style);

        $specificCommand = $input->args[0] ?? null;
        if (null !== $specificCommand) {
            return $this->renderCommandHelp($output, $binary, $specificCommand);
        }

        $this->renderTextSection($output, 'Description', [
            'CLI for ICU MessageFormat parsing, validation, analysis, and linting',
        ]);

        $this->renderTextSection($output, 'Usage', [
            $this->formatUsage($output, $binary),
        ]);

        if ([] !== $this->commands) {
            $commands = array_map(
                static fn (CommandInterface $command): array => [$command->getName(), $command->getDescription()],
                $this->commands,
            );
        } else {
            $commands = [
                ['debug', 'Parse an ICU message and dump its AST and parameters'],
                ['format', 'Format and highlight an ICU message for readability'],
                ['audit', 'Audit ICU messages, usage, and locale consistency'],
                ['highlight', 'Highlight an ICU message string'],
                ['lint', 'Validate ICU messages in YAML and XLIFF files'],
                ['version', 'Display version information'],
                ['self-update', 'Update the CLI phar to the latest release'],
                ['help', 'Display this help message'],
            ];
        }
        $this->renderTableSection($output, 'Commands', $commands, fn (string $value): string => $this->formatCommand($output, $value));

        $globalOptions = [
            ['--ansi', 'Force ANSI output'],
            ['--no-ansi', 'Disable ANSI output'],
            ['-q, --quiet', 'Suppress output'],
            ['--silent', 'Same as --quiet'],
            ['--no-visuals', 'Disable banner and section visuals'],
            ['--help', 'Display this help message'],
        ];
        $this->renderTableSection($output, 'Global Options', $globalOptions, fn (string $value): string => $this->formatOption($output, $value));

        $lintOptions = [
            ['--format <format>', 'Output format (console, json, github, checkstyle, junit)'],
            ['--output <file>', 'Write output to file'],
            ['-v, --verbose', 'Show detailed output'],
        ];
        $this->renderTableSection($output, 'Lint Options', $lintOptions, fn (string $value): string => $this->formatOption($output, $value));

        $examples = [
            [[$binary, 'debug', "'{count, plural, one {# item} other {# items}}'"], 'Parse and show AST'],
            [[$binary, 'format', "'{count, plural, one {# item} other {# items}}'"], 'Format and highlight'],
            [[$binary, 'audit', 'translations/'], 'Audit translation files'],
            [[$binary, 'highlight', "'Hello {name}'"], 'Quick highlight'],
            [[$binary, 'lint', 'translations/'], 'Lint translation files'],
            [[$binary, 'lint', '--format=json', 'translations/'], 'JSON output'],
            [[$binary, 'lint', '--verbose', 'translations/'], 'Verbose output'],
            [[$binary, 'self-update'], 'Update the installed phar'],
        ];
        $this->renderExamplesSection($output, $examples);

        return 0;
    }

    private function renderCommandHelp(Output $output, string $binary, string $command): int
    {
        $commandData = $this->getCommandData($command);
        if (null === $commandData) {
            $output->write($output->error("Unknown command: {$command}\n\n"));
            $this->renderTextSection($output, 'Available Commands', [
                'debug', 'format', 'audit', 'highlight', 'lint', 'version', 'self-update', 'help',
            ]);

            return 1;
        }

        $this->renderTextSection($output, 'Description', [$commandData['description']]);
        $this->renderTextSection($output, 'Usage', [$this->formatCommandUsage($output, $binary, $command, $commandData)]);

        if (!empty($commandData['options'])) {
            $this->renderTableSection($output, 'Options', $commandData['options'], fn (string $value): string => $this->formatOption($output, $value));
        }

        if (!empty($commandData['notes'])) {
            foreach ($commandData['notes'] as $note) {
                $output->write($output->dim('  '.$note)."\n");
            }
            $output->write("\n");
        }

        if (!empty($commandData['examples'])) {
            $this->renderExamplesSection($output, $commandData['examples']);
        }

        return 0;
    }

    /**
     * @return array{description: string, options: array<int, array{0: string, 1: string}>, notes: array<int, string>, examples: array<int, array{0: array<int, string>, 1: string}>}|null
     */
    private function getCommandData(string $command): ?array
    {
        return match ($command) {
            'debug' => [
                'description' => 'Parse an ICU message and dump its AST and parameters',
                'options' => [
                    ['--format <format>', 'Output format (console, json)'],
                ],
                'notes' => ['Parses the ICU message and displays the AST structure and inferred parameter types.'],
                'examples' => [
                    [[$this->resolveInvocation(), 'debug', "'{count, plural, one {# item} other {# items}}'"], 'Parse a plural message'],
                    [[$this->resolveInvocation(), 'debug', "'{gender, select, male {He} female {She} other {They}'"], 'Parse a select message'],
                ],
            ],
            'format' => [
                'description' => 'Format and highlight an ICU message for readability',
                'options' => [
                    ['--indent <n>', 'Indentation size (default: 4)'],
                    ['--no-align', 'Disable selector alignment'],
                ],
                'notes' => [
                    'Pretty-prints the ICU message with indentation and selector alignment.',
                    'Applies syntax highlighting for better readability.',
                    'Outputs the formatted message only.',
                ],
                'examples' => [
                    [[$this->resolveInvocation(), 'format', "'{count, plural, one {# item} other {# items}}'"], 'Format a plural message'],
                    [[$this->resolveInvocation(), 'format', '--indent=2', "'{gender, select, male {He} female {She} other {They}'"], 'Format with 2-space indent'],
                    [[$this->resolveInvocation(), 'format', "'{gender_of_host, select, female {{host} invites {guest} to her party.} other {{host} invites {guest} to their party.}'"], 'Format nested select'],
                ],
            ],
            'audit' => [
                'description' => 'Audit ICU messages, usage, and locale consistency',
                'options' => [
                    ['--format <format>', 'Output format (console, json)'],
                ],
                'notes' => [],
                'examples' => [
                    [[$this->resolveInvocation(), 'audit', 'translations/'], 'Audit translation directory'],
                    [[$this->resolveInvocation(), 'audit', '--format=json', 'translations/'], 'JSON output'],
                ],
            ],
            'highlight' => [
                'description' => 'Highlight an ICU message string',
                'options' => [
                    ['--format <format>', 'Output format (console, html)'],
                ],
                'notes' => [],
                'examples' => [
                    [[$this->resolveInvocation(), 'highlight', "'Hello {name}'"], 'Highlight a simple message'],
                    [[$this->resolveInvocation(), 'highlight', '--format=html', "'{count}'"], 'HTML output'],
                ],
            ],
            'lint' => [
                'description' => 'Validate ICU messages in YAML and XLIFF files',
                'options' => [
                    ['--format <format>', 'Output format (console, json, github, checkstyle, junit)'],
                    ['--output <file>', 'Write output to file'],
                    ['-v, --verbose', 'Show detailed output'],
                ],
                'notes' => [
                    'Supports YAML and XLIFF translation files.',
                    'Validates ICU syntax and semantics including plural categories.',
                ],
                'examples' => [
                    [[$this->resolveInvocation(), 'lint', 'translations/'], 'Lint translations directory'],
                    [[$this->resolveInvocation(), 'lint', '--format=json', 'translations/'], 'JSON output format'],
                    [[$this->resolveInvocation(), 'lint', '--verbose', 'translations/'], 'Verbose linting'],
                    [[$this->resolveInvocation(), 'lint', 'translations/messages.yaml'], 'Lint single file'],
                ],
            ],
            'version' => [
                'description' => 'Display version information',
                'options' => [],
                'notes' => [],
                'examples' => [
                    [[$this->resolveInvocation(), 'version'], 'Show version'],
                ],
            ],
            'self-update' => [
                'description' => 'Update the CLI phar to the latest release',
                'options' => [],
                'notes' => ['Updates the installed phar file to the latest version.'],
                'examples' => [
                    [[$this->resolveInvocation(), 'self-update'], 'Update to latest version'],
                ],
            ],
            'help' => [
                'description' => 'Display help information',
                'options' => [
                    ['<command>', 'Show help for specific command'],
                ],
                'notes' => [],
                'examples' => [
                    [[$this->resolveInvocation(), '--help'], 'Show general help'],
                    [[$this->resolveInvocation(), 'lint', '--help'], 'Show lint command help'],
                ],
            ],
            default => null,
        };
    }

    /**
     * @param array{description: string, options: array<int, array{0: string, 1: string}>, notes: array<int, string>, examples: array<int, array{0: array<int, string>, 1: string}>} $commandData
     */
    private function formatCommandUsage(Output $output, string $binary, string $command, array $commandData): string
    {
        $usage = $output->color($binary, Output::BLUE).' '.$output->color($command, Output::YELLOW.Output::BOLD);

        if ('lint' === $command || 'audit' === $command) {
            $usage .= ' '.$output->color('[options]', Output::CYAN).' '.$output->color('<path>', Output::GREEN);
        } elseif (\in_array($command, ['debug', 'highlight', 'format'], true)) {
            $usage .= ' '.$output->color('[options]', Output::CYAN).' '.$output->color('<message>', Output::GREEN);
        } elseif ('help' === $command) {
            $usage .= ' '.$output->color('[command]', Output::GREEN);
        }

        return $usage;
    }

    private function renderHeader(ConsoleStyle $style): void
    {
        $style->renderBanner('help', [], 'Treat Internationalization as Code.');
    }

    /**
     * @param array<int, string> $lines
     */
    private function renderTextSection(Output $output, string $title, array $lines): void
    {
        $output->write($output->color($title.':', Output::MAGENTA)."\n");

        foreach ($lines as $line) {
            $output->write('  '.$line."\n");
        }

        $output->write("\n");
    }

    /**
     * @param array<int, array{0: string, 1: string}> $rows
     * @param callable(string): string                $formatLeft
     */
    private function renderTableSection(Output $output, string $title, array $rows, callable $formatLeft): void
    {
        $output->write($output->color($title.':', Output::MAGENTA)."\n");
        $this->renderTable($output, $rows, $formatLeft);
        $output->write("\n");
    }

    /**
     * @param array<int, array{0: string, 1: string}> $rows
     * @param callable(string): string                $formatLeft
     */
    private function renderTable(Output $output, array $rows, callable $formatLeft): void
    {
        $maxWidth = 0;
        foreach ($rows as $row) {
            $maxWidth = max($maxWidth, \strlen($row[0]));
        }

        foreach ($rows as [$left, $right]) {
            $padding = max(0, $maxWidth - \strlen($left));
            $output->write('  '.$formatLeft($left).str_repeat(' ', $padding + 2).$right."\n");
        }
    }

    /**
     * @param array<int, array{0: array<int, string>, 1: string}> $examples
     */
    private function renderExamplesSection(Output $output, array $examples): void
    {
        $output->write($output->color('Examples:', Output::MAGENTA)."\n");

        $maxWidth = 0;
        foreach ($examples as [$tokens]) {
            $command = implode(' ', $tokens);
            $maxWidth = max($maxWidth, \strlen($command));
        }

        foreach ($examples as [$tokens, $description]) {
            $command = implode(' ', $tokens);
            $padding = max(0, $maxWidth - \strlen($command));
            $output->write('  '.$this->formatExampleCommand($output, $tokens).str_repeat(' ', $padding + 2).$output->dim('# '.$description)."\n");
        }

        $output->write("\n");
    }

    private function resolveInvocation(): string
    {
        $argv = $_SERVER['argv'] ?? null;
        if (\is_array($argv) && isset($argv[0]) && \is_string($argv[0]) && '' !== $argv[0]) {
            return $argv[0];
        }

        return 'icu';
    }

    private function formatUsage(Output $output, string $binary): string
    {
        return $output->color($binary, Output::BLUE)
            .' '.$output->color('<command>', Output::YELLOW)
            .' '.$output->color('[options]', Output::CYAN);
    }

    private function formatCommand(Output $output, string $command): string
    {
        return $output->color($command, Output::GREEN.Output::BOLD);
    }

    private function formatOption(Output $output, string $option): string
    {
        if (!$output->isAnsi()) {
            return $option;
        }

        $parts = preg_split('/(<[^>]+>)/', $option, -1, \PREG_SPLIT_DELIM_CAPTURE);
        if (false === $parts) {
            return $option;
        }

        $formatted = '';
        foreach ($parts as $part) {
            if ('' === $part) {
                continue;
            }

            $partText = \is_array($part) ? $part[0] : $part;

            if ($this->isPlaceholder($partText)) {
                $formatted .= $output->color($partText, Output::YELLOW.Output::BOLD);

                continue;
            }

            $formatted .= $output->color($partText, Output::CYAN);
        }

        return $formatted;
    }

    /**
     * @param array<int, string> $tokens
     */
    private function formatExampleCommand(Output $output, array $tokens): string
    {
        $formatted = [];
        foreach ($tokens as $index => $token) {
            $formatted[] = $this->formatExampleToken($output, $token, $index);
        }

        return implode(' ', $formatted);
    }

    private function formatExampleToken(Output $output, string $token, int $index): string
    {
        if (0 === $index) {
            return $output->color($token, Output::BLUE.Output::BOLD);
        }

        if (str_starts_with($token, '-')) {
            return $output->color($token, Output::CYAN);
        }

        if ($this->isMessageToken($token)) {
            return $output->color($token, Output::GREEN);
        }

        if (\in_array($token, ['debug', 'format', 'audit', 'highlight', 'lint', 'version', 'self-update', 'help'], true)) {
            return $output->color($token, Output::YELLOW.Output::BOLD);
        }

        return $token;
    }

    private function isPlaceholder(string $value): bool
    {
        return str_starts_with($value, '<') && str_ends_with($value, '>');
    }

    private function isMessageToken(string $token): bool
    {
        $candidate = $token;

        if (
            (str_starts_with($candidate, "'") && str_ends_with($candidate, "'"))
            || (str_starts_with($candidate, '"') && str_ends_with($candidate, '"'))
        ) {
            return true;
        }

        // Check if it looks like an ICU message (contains {, })
        return str_contains($candidate, '{') || str_contains($candidate, '}');
    }
}
