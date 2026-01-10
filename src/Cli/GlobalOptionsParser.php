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
    public function parse(array $args): ParsedGlobalOptions
    {
        $quiet = false;
        $ansi = null;
        $help = false;
        $visuals = true;
        $remaining = [];

        for ($i = 0; $i < \count($args); $i++) {
            $arg = $args[$i];

            if ($this->isQuietOption($arg)) {
                $quiet = true;

                continue;
            }

            if ($this->isAnsiOption($arg)) {
                $ansi = '--ansi' === $arg;

                continue;
            }

            if ($this->isHelpOption($arg)) {
                $help = true;

                continue;
            }

            if ($this->isVisualsOption($arg)) {
                $visuals = false;

                continue;
            }

            $remaining[] = $arg;
        }

        $options = new GlobalOptions($quiet, $ansi, $help, $visuals);

        return new ParsedGlobalOptions($options, $remaining);
    }

    private function isQuietOption(string $arg): bool
    {
        return '-q' === $arg || '--quiet' === $arg || '--silent' === $arg;
    }

    private function isAnsiOption(string $arg): bool
    {
        return '--ansi' === $arg || '--no-ansi' === $arg;
    }

    private function isHelpOption(string $arg): bool
    {
        return '--help' === $arg || '-h' === $arg;
    }

    private function isVisualsOption(string $arg): bool
    {
        return '--no-visuals' === $arg || '--no-art' === $arg || '--no-splash' === $arg;
    }
}
