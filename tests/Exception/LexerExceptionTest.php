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

namespace IcuParser\Tests\Exception;

use IcuParser\Exception\LexerException;
use PHPUnit\Framework\TestCase;

final class LexerExceptionTest extends TestCase
{
    public function test_constructor_with_minimal_parameters(): void
    {
        $exception = new LexerException('Lex error');

        $this->assertSame('Lex error', $exception->getMessage());
        $this->assertNull($exception->getPosition());
        $this->assertSame('', $exception->getSnippet());
        $this->assertSame('lexer.error', $exception->getErrorCode());
    }

    public function test_constructor_with_all_parameters(): void
    {
        $previous = new \Exception('Previous');
        $exception = new LexerException(
            'Lex error',
            42,
            'invalid token',
            $previous
        );

        $this->assertSame('Lex error', $exception->getMessage());
        $this->assertSame(42, $exception->getPosition());
        $this->assertNotNull($exception->getSnippet());
        $this->assertSame('lexer.error', $exception->getErrorCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function test_with_context_static_method(): void
    {
        $exception = LexerException::withContext('Error message', 10, 'input string');

        $this->assertSame('Error message', $exception->getMessage());
        $this->assertSame(10, $exception->getPosition());
        $this->assertNotNull($exception->getSnippet());
        $this->assertSame('lexer.error', $exception->getErrorCode());
    }
}