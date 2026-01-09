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
use IcuParser\NodeVisitor\AstDumper;
use IcuParser\Parser\Parser;
use IcuParser\Runtime\IcuRuntimeInfo;
use IcuParser\Type\TypeInferer;

final class DebugCommand implements CommandInterface
{
    public function getName(): string
    {
        return 'debug';
    }

    public function getAliases(): array
    {
        return [];
    }

    public function getDescription(): string
    {
        return 'Parse an ICU message and dump its AST and parameters.';
    }

    public function run(Input $input, Output $output): int
    {
        $message = $input->args[0] ?? null;
        if (null === $message) {
            $output->write($output->error("Error: Missing ICU message string.\n"));
            $output->write("Usage: icu debug '<message>'\n");

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
            $style->renderBanner('Debug', $meta, 'ICU MessageFormat diagnostics');
        }

        try {
            $parser = new Parser();
            $ast = $parser->parse($message);
            $dump = (new AstDumper())->dump($ast);
            $types = (new TypeInferer())->infer($ast);
        } catch (IcuParserException $exception) {
            $output->write($output->error('Error: '.$exception->getMessage()."\n"));
            $snippet = $exception->getSnippet();
            if (null !== $snippet && '' !== $snippet) {
                $output->write($snippet."\n");
            }

            return 1;
        }

        $style->renderSection('AST');
        $json = json_encode($dump, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
        $output->write(($json ?: '{}')."\n\n");

        $style->renderSection('Parameters');
        $rows = [];
        foreach ($types->all() as $name => $type) {
            $rows[$name] = $output->info($type->value);
        }
        if ([] === $rows) {
            $output->write("  (none)\n");
        } else {
            $style->renderKeyValueBlock($rows, 2);
        }

        return 0;
    }
}
