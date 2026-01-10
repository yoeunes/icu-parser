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
use IcuParser\Highlight\HighlightTheme;
use IcuParser\IcuParser;
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
            $output->write("Usage: icu highlight '<message>'\n");

            return 1;
        }

        $style = new ConsoleStyle($output, true);
        $runtime = IcuRuntimeInfo::detect();
        $meta = [
            'Intl' => $output->warning($runtime->intlVersion),
            'ICU' => $output->warning($runtime->icuVersion),
            'Locale' => $output->warning($runtime->locale),
        ];

        if ($input->globalOptions->banner) {
            $style->renderBanner('Highlight', $meta, 'ICU MessageFormat highlighting');
        }

        try {
            $theme = $output->isAnsi() ? HighlightTheme::ansi() : HighlightTheme::plain();
            $highlighted = (new IcuParser())->highlight($message, $theme);
        } catch (IcuParserException $exception) {
            $output->write($output->error('Error: '.$exception->getMessage()."\n"));
            $snippet = $exception->getSnippet();
            if (null !== $snippet && '' !== $snippet) {
                $output->write($snippet."\n");
            }

            return 1;
        }

        $output->write($highlighted."\n");

        return 0;
    }
}
