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

final readonly class CompositeTranslationExtractor implements TranslationExtractorInterface
{
    /**
     * @param array<int, TranslationExtractorInterface> $extractors
     */
    public function __construct(private array $extractors) {}

    public function supports(string $path): bool
    {
        foreach ($this->extractors as $extractor) {
            if ($extractor->supports($path)) {
                return true;
            }
        }

        return false;
    }

    public function extract(string $path): TranslationExtraction
    {
        foreach ($this->extractors as $extractor) {
            if ($extractor->supports($path)) {
                return $extractor->extract($path);
            }
        }

        return new TranslationExtraction([]);
    }
}
