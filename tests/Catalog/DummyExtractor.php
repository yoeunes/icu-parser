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

namespace IcuParser\Tests\Catalog;

use IcuParser\Loader\TranslationExtraction;
use IcuParser\Loader\TranslationExtractorInterface;

final class DummyExtractor implements TranslationExtractorInterface
{
    public function supports(string $path): bool
    {
        return false;
    }

    public function extract(string $path): TranslationExtraction
    {
        return new TranslationExtraction([], []);
    }
}
