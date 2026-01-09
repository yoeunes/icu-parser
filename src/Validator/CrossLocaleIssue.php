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

namespace IcuParser\Validator;

final readonly class CrossLocaleIssue
{
    public function __construct(
        public string $message,
        public string $domain,
        public string $id,
        public string $locale,
        public string $file,
        public ?int $line,
        public string $code,
    ) {}
}
