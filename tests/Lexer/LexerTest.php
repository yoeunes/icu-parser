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

use IcuParser\Exception\LexerException;
use IcuParser\Lexer\Lexer;
use IcuParser\Lexer\TokenType;
use PHPUnit\Framework\TestCase;

final class LexerTest extends TestCase
{
    private Lexer $lexer;

    protected function setUp(): void
    {
        $this->lexer = new Lexer();
    }

    public function test_tokenizes_simple_text(): void
    {
        $stream = $this->lexer->tokenize('Hello world');

        $tokens = $stream->getTokens();

        $this->assertCount(4, $tokens);
        $this->assertSame(TokenType::T_IDENTIFIER, $tokens[0]->type);
        $this->assertSame('Hello', $tokens[0]->value);
        $this->assertSame(TokenType::T_WHITESPACE, $tokens[1]->type);
        $this->assertSame(' ', $tokens[1]->value);
        $this->assertSame(TokenType::T_IDENTIFIER, $tokens[2]->type);
        $this->assertSame('world', $tokens[2]->value);
        $this->assertSame(TokenType::T_EOF, $tokens[3]->type);
    }

    public function test_tokenizes_braces(): void
    {
        $stream = $this->lexer->tokenize('{name}');

        $tokens = $stream->getTokens();

        $this->assertCount(4, $tokens);
        $this->assertSame(TokenType::T_LBRACE, $tokens[0]->type);
        $this->assertSame('{', $tokens[0]->value);
        $this->assertSame(TokenType::T_IDENTIFIER, $tokens[1]->type);
        $this->assertSame('name', $tokens[1]->value);
        $this->assertSame(TokenType::T_RBRACE, $tokens[2]->type);
        $this->assertSame('}', $tokens[2]->value);
        $this->assertSame(TokenType::T_EOF, $tokens[3]->type);
    }

    public function test_tokenizes_whitespace(): void
    {
        $stream = $this->lexer->tokenize('  ');

        $tokens = $stream->getTokens();

        $this->assertCount(2, $tokens);
        $this->assertSame(TokenType::T_WHITESPACE, $tokens[0]->type);
        $this->assertSame('  ', $tokens[0]->value);
        $this->assertSame(TokenType::T_EOF, $tokens[1]->type);
    }

    public function test_tokenizes_identifier(): void
    {
        $stream = $this->lexer->tokenize('select');

        $tokens = $stream->getTokens();

        $this->assertCount(2, $tokens);
        $this->assertSame(TokenType::T_IDENTIFIER, $tokens[0]->type);
        $this->assertSame('select', $tokens[0]->value);
    }

    public function test_tokenizes_number(): void
    {
        $stream = $this->lexer->tokenize('42');

        $tokens = $stream->getTokens();

        $this->assertCount(2, $tokens);
        $this->assertSame(TokenType::T_NUMBER, $tokens[0]->type);
        $this->assertSame('42', $tokens[0]->value);
    }

    public function test_tokenizes_decimal_number(): void
    {
        $stream = $this->lexer->tokenize('3.14');

        $tokens = $stream->getTokens();

        $this->assertCount(2, $tokens);
        $this->assertSame(TokenType::T_NUMBER, $tokens[0]->type);
        $this->assertSame('3.14', $tokens[0]->value);
    }

    public function test_tokenizes_negative_number(): void
    {
        $stream = $this->lexer->tokenize('-5');

        $tokens = $stream->getTokens();

        $this->assertCount(2, $tokens);
        $this->assertSame(TokenType::T_NUMBER, $tokens[0]->type);
        $this->assertSame('-5', $tokens[0]->value);
    }

    public function test_tokenizes_single_quote_as_text(): void
    {
        $stream = $this->lexer->tokenize("'");

        $tokens = $stream->getTokens();

        $this->assertCount(2, $tokens);
        $this->assertSame(TokenType::T_TEXT, $tokens[0]->type);
        $this->assertSame("'", $tokens[0]->value);
    }

    public function test_tokenizes_double_single_quote_as_text(): void
    {
        $stream = $this->lexer->tokenize("''");

        $tokens = $stream->getTokens();

        $this->assertCount(2, $tokens);
        $this->assertSame(TokenType::T_TEXT, $tokens[0]->type);
        $this->assertSame("'", $tokens[0]->value);
    }

    public function test_tokenizes_quoted_literal(): void
    {
        $stream = $this->lexer->tokenize("'{name}'");

        $tokens = $stream->getTokens();

        $this->assertCount(2, $tokens);
        $this->assertSame(TokenType::T_TEXT, $tokens[0]->type);
        $this->assertSame('{name}', $tokens[0]->value);
    }

    public function test_tokenizes_complex_message(): void
    {
        $stream = $this->lexer->tokenize('Hello {name}, you have {count, plural, one {# item} other {# items}}');

        $tokens = $stream->getTokens();

        // Should have multiple tokens
        $this->assertGreaterThan(10, $tokens);
        // Check some key tokens exist
        $types = array_map(fn ($token) => $token->type, $tokens);
        $this->assertContains(TokenType::T_LBRACE, $types);
        $this->assertContains(TokenType::T_IDENTIFIER, $types);
        $this->assertContains(TokenType::T_COMMA, $types);
        $this->assertContains(TokenType::T_HASH, $types);
    }

    public function test_tokenizes_choice_tokens(): void
    {
        $stream = $this->lexer->tokenize('0#none|1<one');

        $tokens = $stream->getTokens();

        $this->assertSame(TokenType::T_NUMBER, $tokens[0]->type);
        $this->assertSame(TokenType::T_HASH, $tokens[1]->type);
        $this->assertSame(TokenType::T_IDENTIFIER, $tokens[2]->type);
        $this->assertSame(TokenType::T_PIPE, $tokens[3]->type);
        $this->assertSame(TokenType::T_NUMBER, $tokens[4]->type);
        $this->assertSame(TokenType::T_LT, $tokens[5]->type);
        $this->assertSame(TokenType::T_IDENTIFIER, $tokens[6]->type);
        $this->assertSame(TokenType::T_EOF, $tokens[7]->type);
    }

    public function test_throws_on_invalid_utf8(): void
    {
        $this->expectException(LexerException::class);
        $this->expectExceptionMessage('Input string is not valid UTF-8.');

        $this->lexer->tokenize("\x80\x81");
    }

    public function test_throws_on_unterminated_quoted_literal(): void
    {
        $this->expectException(LexerException::class);
        $this->expectExceptionMessage('Unterminated quoted literal.');

        $this->lexer->tokenize("'{unclosed");
    }
}
