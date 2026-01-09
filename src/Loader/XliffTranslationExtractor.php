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

final class XliffTranslationExtractor implements TranslationExtractorInterface
{
    public function supports(string $path): bool
    {
        $extension = strtolower(pathinfo($path, \PATHINFO_EXTENSION));

        return 'xlf' === $extension || 'xliff' === $extension;
    }

    public function extract(string $path): TranslationExtraction
    {
        $contents = file_get_contents($path);
        if (false === $contents) {
            return new TranslationExtraction([]);
        }

        $dom = new \DOMDocument();
        $loaded = @$dom->loadXML($contents);
        if (false === $loaded) {
            return new TranslationExtraction([]);
        }

        $messages = [];
        $lines = [];

        foreach ($dom->getElementsByTagName('trans-unit') as $unit) {
            if (!$unit instanceof \DOMElement) {
                continue;
            }

            $id = $this->resolveId($unit);
            if (null === $id) {
                continue;
            }

            $text = $this->resolveMessage($unit);
            if (null === $text || '' === $text) {
                continue;
            }

            $messages[$id] = $text;
            $line = $unit->getLineNo();
            $lines[$id] = $line > 0 ? $line : null;
        }

        foreach ($dom->getElementsByTagName('unit') as $unit) {
            if (!$unit instanceof \DOMElement) {
                continue;
            }

            $id = $this->resolveId($unit);
            if (null === $id) {
                continue;
            }

            $text = $this->resolveMessage($unit);
            if (null === $text || '' === $text) {
                continue;
            }

            $messages[$id] = $text;
            $line = $unit->getLineNo();
            $lines[$id] = $line > 0 ? $line : null;
        }

        return new TranslationExtraction($messages, $lines);
    }

    private function resolveId(\DOMElement $element): ?string
    {
        $id = $element->getAttribute('id');
        if ('' !== $id) {
            return $id;
        }

        $id = $element->getAttribute('resname');
        if ('' !== $id) {
            return $id;
        }

        return null;
    }

    private function resolveMessage(\DOMElement $element): ?string
    {
        foreach (['target', 'source'] as $tag) {
            $nodes = $element->getElementsByTagName($tag);
            if (0 === $nodes->length) {
                continue;
            }

            $text = trim($nodes->item(0)->textContent ?? '');
            if ('' !== $text) {
                return $text;
            }
        }

        return null;
    }
}
