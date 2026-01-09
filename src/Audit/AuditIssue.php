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

namespace IcuParser\Audit;

final readonly class AuditIssue
{
    public function __construct(
        public string $category,
        public string $message,
        public string $file,
        public ?int $line,
        public ?string $snippet = null,
        public ?string $code = null,
    ) {}
}
