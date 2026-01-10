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
use IcuParser\IcuParser;
use IcuParser\NodeVisitor\ConsoleHighlighterVisitor;
use IcuParser\NodeVisitor\HtmlHighlighterVisitor;
use IcuParser\Runtime\IcuRuntimeInfo;

final class HighlightCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'highlight';
    }

    public function getAliases(): array
    {
        return [];
    }

    public function getDescription(): string
    {
        return 'Highlight an ICU message string.';
    }

    public function run(Input $input, Output $output): int
    {
        $message = $input->args[0] ?? null;
        if (null === $message) {
            $output->write($output->error("Error: Missing ICU message string.\n"));
            $output->write("Usage: icu highlight '<message>' [--format=auto|cli|html]\n");

            return 1;
        }

        // Parse format option
        $format = 'auto';
        for ($i = 0; $i < \count($input->args); $i++) {
            $arg = $input->args[$i];
            if (str_starts_with($arg, '--format=')) {
                $format = substr($arg, 9);

                break;
            }
            if ('--format' === $arg) {
                $format = $input->args[$i + 1] ?? $format;
                $i++;
            }
        }

        $style = new ConsoleStyle($output, $input->globalOptions->visuals);
        $runtime = IcuRuntimeInfo::detect();
        $meta = [
            'Intl' => $output->warning($runtime->intlVersion),
            'ICU' => $output->warning($runtime->icuVersion),
            'Locale' => $output->warning($runtime->locale),
        ];

        try {
            $parser = new IcuParser();
            $ast = $parser->parse($message);

            if ('auto' === $format) {
                $format = \PHP_SAPI === 'cli' ? 'cli' : 'html';
            }

            if ('cli' === $format && $style->visualsEnabled()) {
                $meta['Format'] = $output->warning('cli');
                $style->renderBanner('Highlight', $meta, 'ICU MessageFormat Highlighting');
            }

            $visitor = match ($format) {
                'cli' => new ConsoleHighlighterVisitor($output->isAnsi()),
                'html' => new HtmlHighlighterVisitor(),
                default => throw new \InvalidArgumentException("Invalid format: $format"),
            };

            $highlighted = $ast->accept($visitor);

            if ('cli' === $format && !$output->isAnsi()) {
                $highlighted = $message;
            }

            if ('cli' === $format && $style->visualsEnabled()) {
                $style->renderSection('Highlighting message', 1, 1);
                $style->renderPattern($highlighted);
            } else {
                $output->write($highlighted."\n");
            }
        } catch (IcuParserException|\InvalidArgumentException $exception) {
            $output->write('  '.$output->badge('FAIL', Output::WHITE, Output::BG_RED).' '.$output->error("Error: {$exception->getMessage()}")."\n");

            $snippet = $exception instanceof IcuParserException ? $exception->getSnippet() : null;
            if (null !== $snippet && '' !== $snippet) {
                $output->write($snippet."\n");
            }

            return 1;
        }

        return 0;
    }
}
