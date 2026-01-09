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

namespace IcuParser\Loader;

interface TranslationExtractorInterface
{
    public function supports(string $path): bool;

    public function extract(string $path): TranslationExtraction;
}
