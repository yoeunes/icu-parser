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
use IcuParser\Exception\IcuParserException;
use IcuParser\Formatter\FormatOptions;
use IcuParser\IcuParser;
use IcuParser\NodeVisitor\ConsoleHighlighterVisitor;
use IcuParser\Runtime\IcuRuntimeInfo;

final class FormatCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'format';
    }

    public function getAliases(): array
    {
        return ['fmt'];
    }

    public function getDescription(): string
    {
        return 'Format and highlight an ICU message for readability.';
    }

    public function run(Input $input, Output $output): int
    {
        // Parse options first
        $indentSize = 4;
        $alignSelectors = true;
        $args = $input->args;
        $message = null;

        foreach ($args as $arg) {
            if (str_starts_with($arg, '--indent=')) {
                $indentSize = (int) substr($arg, 9);
            } elseif ('--no-align' === $arg) {
                $alignSelectors = false;
            } elseif (!str_starts_with($arg, '-')) {
                $message = $arg;
            }
        }

        if (null === $message) {
            $output->write($output->error("Error: Missing ICU message string.\n"));
            $output->write("Usage: icu format '<message>' [--indent=4] [--no-align]\n");

            return 1;
        }

        $style = new ConsoleStyle($output, $input->globalOptions->visuals);
        $runtime = IcuRuntimeInfo::detect();
        $meta = [
            'Intl' => $output->warning($runtime->intlVersion),
            'ICU' => $output->warning($runtime->icuVersion),
            'Locale' => $output->warning($runtime->locale),
        ];

        if ($style->visualsEnabled()) {
            $style->renderBanner('Format', $meta, 'ICU MessageFormat Formatter');
        }

        try {
            $parser = new IcuParser();

            // First, format the message
            $options = new FormatOptions(
                indent: str_repeat(' ', $indentSize),
                lineBreak: "\n",
                alignSelectors: $alignSelectors,
            );
            $formatted = $parser->format($message, $options);

            // Then, parse the formatted message and highlight it
            $ast = $parser->parse($formatted);
            $visitor = new ConsoleHighlighterVisitor($output->isAnsi());
            $highlighted = $ast->accept($visitor);

            if ($style->visualsEnabled()) {
                $style->renderPattern($highlighted);
            } else {
                $output->write($formatted."\n");
            }
        } catch (IcuParserException $exception) {
            $output->write('  '.$output->badge('FAIL', Output::WHITE, Output::BG_RED).' '.$output->error("Error: {$exception->getMessage()}")."\n");

            $snippet = $exception->getSnippet();
            if (null !== $snippet && '' !== $snippet) {
                $output->write($snippet."\n");
            }

            return 1;
        }

        return 0;
    }
}
