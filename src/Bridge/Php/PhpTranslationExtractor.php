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

namespace IcuParser\Bridge\Php;

use IcuParser\Type\ParameterType;
use IcuParser\Usage\TranslationUsage;

/**
 * @phpstan-type Token array{0: int, 1: string, 2: int}|string
 * @phpstan-type TokenList array<int, Token>
 */
final class PhpTranslationExtractor
{
    /**
     * @return list<TranslationUsage>
     */
    public function extractFromFile(string $path): array
    {
        $contents = file_get_contents($path);
        if (false === $contents) {
            return [];
        }

        return $this->extractFromSource($contents, $path);
    }

    /**
     * @return list<TranslationUsage>
     */
    public function extractFromSource(string $source, string $path): array
    {
        /** @var TokenList $tokens */
        $tokens = token_get_all($source);
        $usages = [];
        $count = \count($tokens);

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];
            if (!$this->isTransToken($token)) {
                continue;
            }

            $prev = $this->previousNonWhitespaceToken($tokens, $i - 1);
            if (null === $prev || !$this->isMethodCallContext($prev[0])) {
                continue;
            }

            $next = $this->nextNonWhitespaceToken($tokens, $i + 1);
            if (null === $next || '(' !== $next[0]) {
                continue;
            }

            [$arguments, $endIndex] = $this->collectArguments($tokens, $next[1]);
            $id = $this->resolveStringArgument($arguments[0] ?? []);
            if (null === $id) {
                continue;
            }

            $parameters = $this->resolveArrayArgument($arguments[1] ?? []);
            $domain = $this->resolveStringArgument($arguments[2] ?? []);
            $line = \is_array($token) ? $token[2] : null;

            $usages[] = new TranslationUsage($id, $parameters, $path, \is_int($line) ? $line : null, $domain);
            $i = $endIndex;
        }

        return $usages;
    }

    /**
     * @param Token $token
     */
    private function isTransToken(array|string $token): bool
    {
        if (!\is_array($token)) {
            return false;
        }

        return \T_STRING === $token[0] && 'trans' === strtolower($token[1]);
    }

    /**
     * @param Token $token
     */
    private function isMethodCallContext(array|string $token): bool
    {
        if (!\is_array($token)) {
            return \in_array($token, ['->', '?->'], true);
        }

        return \in_array($token[0], [\T_OBJECT_OPERATOR, \T_NULLSAFE_OBJECT_OPERATOR], true);
    }

    /**
     * @param TokenList $tokens
     *
     * @return array{0: list<TokenList>, 1: int}
     */
    private function collectArguments(array $tokens, int $startIndex): array
    {
        $arguments = [];
        $current = [];
        $depth = 0;
        $count = \count($tokens);

        for ($i = $startIndex + 1; $i < $count; $i++) {
            $token = $tokens[$i];
            $value = \is_array($token) ? $token[1] : $token;

            if ('(' === $value || '[' === $value || '{' === $value) {
                $depth++;
                $current[] = $token;

                continue;
            }

            if (')' === $value) {
                if (0 === $depth) {
                    if ([] !== $current) {
                        $arguments[] = $current;
                    }

                    return [$arguments, $i];
                }

                $depth--;
                $current[] = $token;

                continue;
            }

            if (']' === $value || '}' === $value) {
                if ($depth > 0) {
                    $depth--;
                }
                $current[] = $token;

                continue;
            }

            if (',' === $value && 0 === $depth) {
                $arguments[] = $current;
                $current = [];

                continue;
            }

            $current[] = $token;
        }

        return [$arguments, $startIndex];
    }

    /**
     * @param TokenList $tokens
     */
    private function resolveStringArgument(array $tokens): ?string
    {
        $first = $this->firstNonWhitespaceToken($tokens);
        if (!\is_array($first) || \T_CONSTANT_ENCAPSED_STRING !== $first[0]) {
            return null;
        }

        $value = $first[1];
        if (!\is_string($value)) {
            return null;
        }

        return $this->unquote($value);
    }

    /**
     * @param TokenList $tokens
     *
     * @return array<string, ParameterType>
     */
    private function resolveArrayArgument(array $tokens): array
    {
        $first = $this->firstNonWhitespaceToken($tokens);
        if (null === $first) {
            return [];
        }

        if (\is_array($first) && \T_ARRAY === $first[0]) {
            return $this->extractArrayParameters($tokens);
        }

        if ('[' === $first) {
            return $this->extractArrayParameters($tokens);
        }

        return [];
    }

    /**
     * @param TokenList $tokens
     *
     * @return array<string, ParameterType>
     */
    private function extractArrayParameters(array $tokens): array
    {
        $parameters = [];
        $count = \count($tokens);

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];
            if (!\is_array($token) || \T_CONSTANT_ENCAPSED_STRING !== $token[0]) {
                continue;
            }

            $keyValue = $token[1];
            if (!\is_string($keyValue)) {
                continue;
            }

            $key = $this->unquote($keyValue);
            $next = $this->nextNonWhitespaceTokenFrom($tokens, $i + 1);
            if (null === $next || '=>' !== $next[0]) {
                continue;
            }

            $value = $this->nextNonWhitespaceTokenFrom($tokens, $next[1] + 1);
            if (null === $value) {
                continue;
            }

            $parameters[$key] = $this->mapTokenType($value[0]);
        }

        return $parameters;
    }

    /**
     * @param Token $token
     */
    private function mapTokenType(array|string $token): ParameterType
    {
        if (\is_array($token)) {
            if (\T_LNUMBER === $token[0] || \T_DNUMBER === $token[0]) {
                return ParameterType::NUMBER;
            }

            if (\T_CONSTANT_ENCAPSED_STRING === $token[0]) {
                return ParameterType::STRING;
            }
        }

        return ParameterType::MIXED;
    }

    /**
     * @param TokenList $tokens
     *
     * @return Token|null
     */
    private function firstNonWhitespaceToken(array $tokens): array|string|null
    {
        foreach ($tokens as $token) {
            if ($this->isIgnorableToken($token)) {
                continue;
            }

            return $token;
        }

        return null;
    }

    /**
     * @param TokenList $tokens
     *
     * @return array{0: Token, 1: int}|null
     */
    private function nextNonWhitespaceToken(array $tokens, int $index): ?array
    {
        $count = \count($tokens);
        for ($i = $index; $i < $count; $i++) {
            $token = $tokens[$i];
            if ($this->isIgnorableToken($token)) {
                continue;
            }

            return [$token, $i];
        }

        return null;
    }

    /**
     * @param TokenList $tokens
     *
     * @return array{0: Token, 1: int}|null
     */
    private function previousNonWhitespaceToken(array $tokens, int $index): ?array
    {
        for ($i = $index; $i >= 0; $i--) {
            $token = $tokens[$i];
            if ($this->isIgnorableToken($token)) {
                continue;
            }

            return [$token, $i];
        }

        return null;
    }

    /**
     * @param TokenList $tokens
     *
     * @return array{0: Token, 1: int}|null
     */
    private function nextNonWhitespaceTokenFrom(array $tokens, int $index): ?array
    {
        $count = \count($tokens);
        for ($i = $index; $i < $count; $i++) {
            $token = $tokens[$i];
            if ($this->isIgnorableToken($token)) {
                continue;
            }

            return [$token, $i];
        }

        return null;
    }

    /**
     * @param Token $token
     */
    private function isIgnorableToken(array|string $token): bool
    {
        if (!\is_array($token)) {
            return '' === trim($token);
        }

        return \in_array($token[0], [\T_WHITESPACE, \T_COMMENT, \T_DOC_COMMENT], true);
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
            return stripcslashes(substr($value, 1, -1));
        }

        return $value;
    }
}
