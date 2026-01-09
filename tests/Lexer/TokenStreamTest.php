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

namespace IcuParser\Tests\Lexer;

use IcuParser\Lexer\Lexer;
use IcuParser\Lexer\Token;
use IcuParser\Lexer\TokenStream;
use IcuParser\Lexer\TokenType;
use PHPUnit\Framework\TestCase;

final class TokenStreamTest extends TestCase
{
    private TokenStream $stream;

    protected function setUp(): void
    {
        $lexer = new Lexer();
        $this->stream = $lexer->tokenize('hello world');
    }

    public function test_current_returns_current_token(): void
    {
        $token = $this->stream->current();

        $this->assertSame(TokenType::T_IDENTIFIER, $token->type);
        $this->assertSame('hello', $token->value);
    }

    public function test_next_advances_position(): void
    {
        $this->stream->next();
        $token = $this->stream->current();

        $this->assertSame(TokenType::T_WHITESPACE, $token->type);
    }

    public function test_peek_returns_token_ahead(): void
    {
        $token = $this->stream->peek();

        $this->assertSame(TokenType::T_WHITESPACE, $token->type);
        $this->assertSame(' ', $token->value);
    }

    public function test_peek_with_offset(): void
    {
        $token = $this->stream->peek(2);

        $this->assertSame(TokenType::T_IDENTIFIER, $token->type);
        $this->assertSame('world', $token->value);
    }

    public function test_is_at_end_returns_false_initially(): void
    {
        $this->assertFalse($this->stream->isAtEnd());
    }

    public function test_is_at_end_returns_true_at_end(): void
    {
        // Advance to EOF
        $this->stream->next(); // whitespace
        $this->stream->next(); // world
        $this->stream->next(); // eof

        $this->assertTrue($this->stream->isAtEnd());
    }

    public function test_get_position(): void
    {
        $this->assertSame(0, $this->stream->getPosition());

        $this->stream->next();
        $this->assertSame(1, $this->stream->getPosition());
    }

    public function test_set_position(): void
    {
        $this->stream->setPosition(2);

        $token = $this->stream->current();
        $this->assertSame('world', $token->value);
    }

    public function test_set_position_out_of_bounds_throws(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Position 10 is out of bounds.');

        $this->stream->setPosition(10);
    }

    public function test_get_source(): void
    {
        $this->assertSame('hello world', $this->stream->getSource());
    }

    public function test_get_source_length(): void
    {
        $this->assertSame(11, $this->stream->getSourceLength());
    }

    public function test_get_tokens(): void
    {
        $tokens = $this->stream->getTokens();

        $this->assertCount(4, $tokens); // hello, space, world, eof
        $this->assertInstanceOf(Token::class, $tokens[0]);
    }
}
