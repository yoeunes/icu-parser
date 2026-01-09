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

final class YamlTranslationExtractor implements TranslationExtractorInterface
{
    public function supports(string $path): bool
    {
        $extension = strtolower(pathinfo($path, \PATHINFO_EXTENSION));

        return 'yml' === $extension || 'yaml' === $extension;
    }

    public function extract(string $path): TranslationExtraction
    {
        $contents = file_get_contents($path);
        if (false === $contents) {
            return new TranslationExtraction([]);
        }

        $messages = [];
        $lines = [];
        $stack = [];
        $rawLines = preg_split('/\R/', $contents) ?: [];

        foreach ($rawLines as $index => $line) {
            $trimmed = ltrim($line);
            if ('' === $trimmed || str_starts_with($trimmed, '#')) {
                continue;
            }

            if (!preg_match('/^(\s*)([^:#]+)\s*:\s*(.*)$/', $line, $matches)) {
                continue;
            }

            $indent = \strlen($matches[1]);
            $key = trim($matches[2]);
            $value = $matches[3];

            if ('' === $key) {
                continue;
            }

            while ([] !== $stack && $indent <= $stack[array_key_last($stack)]['indent']) {
                array_pop($stack);
            }

            $valueTrimmed = ltrim($value);
            if ('' === $valueTrimmed || str_starts_with($valueTrimmed, '|') || str_starts_with($valueTrimmed, '>')) {
                $stack[] = ['indent' => $indent, 'key' => $key];

                continue;
            }

            $cleanValue = $this->stripInlineComment(trim($value));
            $cleanValue = $this->unquote($cleanValue);

            $fullKey = $this->buildKey($stack, $key);
            $messages[$fullKey] = $cleanValue;
            $lines[$fullKey] = $index + 1;
        }

        return new TranslationExtraction($messages, $lines);
    }

    /**
     * @param array<int, array{indent: int, key: string}> $stack
     */
    private function buildKey(array $stack, string $key): string
    {
        $segments = [];
        foreach ($stack as $entry) {
            $segments[] = $entry['key'];
        }
        $segments[] = $key;

        return implode('.', $segments);
    }

    private function stripInlineComment(string $value): string
    {
        if ('' === $value) {
            return $value;
        }

        $first = $value[0];
        if ('"' === $first || "'" === $first) {
            return $value;
        }

        $pos = strpos($value, ' #');
        if (false === $pos) {
            return $value;
        }

        return rtrim(substr($value, 0, $pos));
    }

    private function unquote(string $value): string
    {
        $length = \strlen($value);
        if ($length < 2) {
            return $value;
        }

        $first = $value[0];
        $last = $value[$length - 1];

        if (("'" === $first && "'" === $last) || ('"' === $first && '"' === $last)) {
            return substr($value, 1, -1);
        }

        return $value;
    }
}
