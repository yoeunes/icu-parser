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

namespace IcuParser\Usage;

use IcuParser\Type\ParameterType;

final readonly class TranslationUsage
{
    /**
     * @param array<string, ParameterType> $parameters
     */
    public function __construct(
        public string $id,
        public array $parameters,
        public string $file,
        public ?int $line,
        public ?string $domain = null,
        public ?string $locale = null,
    ) {}
}
