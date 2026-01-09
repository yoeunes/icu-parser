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

final class Output
{
    public const RESET = "\033[0m";
    public const RED = "\033[31m";
    public const GREEN = "\033[32m";
    public const YELLOW = "\033[33m";
    public const BLUE = "\033[34m";
    public const CYAN = "\033[36m";
    public const GRAY = "\033[90m";
    public const BOLD = "\033[1m";

    public function __construct(
        private bool $ansi,
        private bool $quiet,
    ) {}

    public function isAnsi(): bool
    {
        return $this->ansi;
    }

    public function setAnsi(bool $ansi): void
    {
        $this->ansi = $ansi;
    }

    public function isQuiet(): bool
    {
        return $this->quiet;
    }

    public function setQuiet(bool $quiet): void
    {
        $this->quiet = $quiet;
    }

    public function write(string $text): void
    {
        if (!$this->quiet) {
            echo $text;
        }
    }

    public function color(string $text, string $color): string
    {
        return $this->ansi ? $color.$text.self::RESET : $text;
    }

    public function success(string $text): string
    {
        return $this->color($text, self::GREEN);
    }

    public function error(string $text): string
    {
        return $this->color($text, self::RED);
    }

    public function warning(string $text): string
    {
        return $this->color($text, self::YELLOW);
    }

    public function info(string $text): string
    {
        return $this->color($text, self::BLUE);
    }

    public function bold(string $text): string
    {
        return $this->color($text, self::BOLD);
    }

    public function dim(string $text): string
    {
        return $this->color($text, self::GRAY);
    }

    public function accent(string $text): string
    {
        return $this->color($text, self::CYAN);
    }
}
