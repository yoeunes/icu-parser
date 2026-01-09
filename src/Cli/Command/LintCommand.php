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
use IcuParser\Loader\TranslationLoader;
use IcuParser\Parser\Parser;
use IcuParser\Runtime\IcuRuntimeInfo;
use IcuParser\Validation\SemanticValidator;

final class LintCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'lint';
    }

    public function getAliases(): array
    {
        return [];
    }

    public function getDescription(): string
    {
        return 'Validate ICU messages in YAML and XLIFF files.';
    }

    public function run(Input $input, Output $output): int
    {
        $path = $input->args[0] ?? getcwd();
        if (false === $path || !file_exists($path)) {
            $output->write($output->error('Error: Path not found.')."\n");

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
            $style->renderBanner('Lint', $meta, 'ICU MessageFormat linting');
        }

        $loader = new TranslationLoader([$path], 'und');
        $entries = [];
        $files = [];
        foreach ($loader->scan() as $entry) {
            $entries[] = $entry;
            $files[$entry->file] = true;
        }

        if ([] === $entries) {
            $output->write($output->warning('No translation messages found.')."\n");

            return 0;
        }

        $parser = new Parser();
        $validator = new SemanticValidator();
        $issues = 0;
        $checked = 0;

        foreach ($entries as $entry) {
            $checked++;

            try {
                $ast = $parser->parse($entry->message);
            } catch (IcuParserException $exception) {
                $issues++;
                $location = $entry->file;
                if (null !== $entry->line) {
                    $location .= ':'.$entry->line;
                }
                $output->write($output->error('Error').': '.$location.' - '.$exception->getMessage()."\n");
                $snippet = $exception->getSnippet();
                if (null !== $snippet && '' !== $snippet) {
                    $output->write($snippet."\n");
                }

                continue;
            }

            $validation = $validator->validate($ast, $entry->message);
            foreach ($validation->getErrors() as $error) {
                $issues++;
                $location = $entry->file;
                if (null !== $entry->line) {
                    $location .= ':'.$entry->line;
                }
                $output->write($output->error('Semantic error').': '.$location.' - '.$error->getMessage()."\n");
                $snippet = $error->getSnippet();
                if ('' !== $snippet) {
                    $output->write($snippet."\n");
                }
            }
        }

        $summary = sprintf(
            'Checked %d message(s) in %d file(s).',
            $checked,
            \count($files),
        );

        if (0 === $issues) {
            $output->write($output->success($summary.' No issues found.')."\n");

            return 0;
        }

        $output->write($output->error($summary.' '.$issues.' issue(s) found.')."\n");

        return 1;
    }
}
