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

namespace IcuParser\Formatter;

/**
 * Options controlling ICU message pretty formatting.
 */
final readonly class FormatOptions
{
    /**
     * @param non-empty-string $lineBreak
     */
    public function __construct(
        public string $indent = '    ',
        public string $lineBreak = "\n",
        public bool $alignSelectors = true,
    ) {
        if ('' === $lineBreak) {
            throw new \InvalidArgumentException('lineBreak must not be empty.');
        }
    }
}
