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

use IcuParser\IcuParser;

final readonly class ConsoleStyle
{
    private const INDENT = '  ';

    public function __construct(
        private Output $output,
        private bool $visuals = true,
    ) {}

    /**
     * @param array<string, string> $meta
     */
    public function renderBanner(string $command, array $meta = [], ?string $tagline = null): void
    {
        if (!$this->visuals) {
            return;
        }

        $this->writeTitleBlock($tagline);
        $this->writeRuntimeInfo($command, $meta);
        $this->output->write("\n");
    }

    public function renderSection(string $title): void
    {
        if (!$this->visuals) {
            return;
        }

        $this->output->write(self::INDENT.$this->output->dim($title)."\n");
    }

    /**
     * @param array<string, string> $rows
     */
    public function renderKeyValueBlock(array $rows, int $indent = 2): void
    {
        if ([] === $rows) {
            return;
        }

        $maxLabelLength = max(array_map(strlen(...), array_keys($rows)));
        $prefix = str_repeat(' ', max(0, $indent));

        foreach ($rows as $label => $value) {
            $this->output->write(
                $prefix
                .$this->output->dim(str_pad($label, $maxLabelLength))
                .' : '
                .$value
                ."\n",
            );
        }
    }

    private function writeTitleBlock(?string $tagline): void
    {
        $this->output->write(
            $this->output->accent('IcuParser')
            .' '
            .$this->output->warning(IcuParser::VERSION)
            ." by Younes ENNAJI\n",
        );

        if (null !== $tagline && '' !== $tagline) {
            $this->output->write($this->output->dim($tagline)."\n");
        }

        $this->output->write("\n");
    }

    /**
     * @param array<string, string> $meta
     */
    private function writeRuntimeInfo(string $command, array $meta): void
    {
        $lines = [
            'Runtime' => 'PHP '.$this->output->warning(\PHP_VERSION),
            'Command' => $this->output->warning($command),
        ];

        foreach ($meta as $label => $value) {
            $lines[$label] = $value;
        }

        $maxLabelLength = max(array_map(strlen(...), array_keys($lines)));

        foreach ($lines as $label => $value) {
            $this->output->write(
                $this->output->bold(str_pad($label, $maxLabelLength))
                .' : '
                .$value
                ."\n",
            );
        }
    }
}
