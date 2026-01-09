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

namespace IcuParser\Exception;

/**
 * Represents a lexical analysis error.
 */
final class LexerException extends IcuParserException
{
    use VisualContextTrait;

    public function __construct(string $message, ?int $position = null, ?string $input = null, ?\Throwable $previous = null)
    {
        $this->initializeContext($position, $input);

        parent::__construct($message, $position, $this->getVisualSnippet(), 'lexer.error', $previous);
    }

    public static function withContext(string $message, int $position, string $input, ?\Throwable $previous = null): self
    {
        return new self($message, $position, $input, $previous);
    }
}
