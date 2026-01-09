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

final class GlobalOptionsParser
{
    /**
     * @param array<int, string> $args
     */
    public function parse(array $args): ParsedInput
    {
        $ansi = null;
        $quiet = false;
        $banner = true;
        $help = false;
        $remaining = [];

        foreach ($args as $arg) {
            if ('--help' === $arg || '-h' === $arg) {
                $help = true;
                continue;
            }

            if ('--ansi' === $arg) {
                $ansi = true;
                continue;
            }

            if ('--no-ansi' === $arg) {
                $ansi = false;
                continue;
            }

            if ('-q' === $arg || '--quiet' === $arg) {
                $quiet = true;
                continue;
            }

            if ('--no-banner' === $arg) {
                $banner = false;
                continue;
            }

            if (str_starts_with($arg, '-')) {
                return new ParsedInput(
                    new GlobalOptions($ansi, $quiet, $banner, $help),
                    $remaining,
                    sprintf('Unknown option: %s', $arg),
                );
            }

            $remaining[] = $arg;
        }

        return new ParsedInput(new GlobalOptions($ansi, $quiet, $banner, $help), $remaining);
    }
}
