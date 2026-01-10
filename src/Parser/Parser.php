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

namespace IcuParser\Parser;

use IcuParser\Exception\ParserException;
use IcuParser\Lexer\Lexer;
use IcuParser\Lexer\Token;
use IcuParser\Lexer\TokenStream;
use IcuParser\Lexer\TokenType;
use IcuParser\Node\ChoiceNode;
use IcuParser\Node\ChoiceOptionNode;
use IcuParser\Node\DurationNode;
use IcuParser\Node\FormattedArgumentNode;
use IcuParser\Node\MessageNode;
use IcuParser\Node\NodeInterface;
use IcuParser\Node\OptionNode;
use IcuParser\Node\OrdinalNode;
use IcuParser\Node\PluralNode;
use IcuParser\Node\PoundNode;
use IcuParser\Node\SelectNode;
use IcuParser\Node\SelectOrdinalNode;
use IcuParser\Node\SimpleArgumentNode;
use IcuParser\Node\SpelloutNode;
use IcuParser\Node\TextNode;

/**
 * ICU MessageFormat parser.
 */
final class Parser
{
    private TokenStream $tokens;

    private string $message = '';

    private int $pluralDepth = 0;

    public function __construct(private readonly Lexer $lexer = new Lexer()) {}

    /**
     * @throws ParserException
     */
    public function parse(string $message): MessageNode
    {
        $this->message = $message;
        $this->tokens = $this->lexer->tokenize($message);
        $this->pluralDepth = 0;

        $node = $this->parseMessage();
        $token = $this->current();

        if (TokenType::T_RBRACE === $token->type) {
            throw ParserException::withContext('Unexpected "}".', $token->position, $message);
        }

        if (TokenType::T_EOF !== $token->type) {
            throw ParserException::withContext('Unexpected trailing tokens.', $token->position, $message);
        }

        return $node;
    }

    /**
     * @param array<int, TokenType> $terminators
     */
    private function parseMessage(array $terminators = []): MessageNode
    {
        /** @var list<NodeInterface> $parts */
        $parts = [];
        $textBuffer = '';
        $textStart = null;
        $textEnd = null;

        $start = $this->current()->position;
        $end = $start;

        while (!$this->tokens->isAtEnd()) {
            $token = $this->current();

            if (TokenType::T_RBRACE === $token->type || \in_array($token->type, $terminators, true)) {
                break;
            }

            if (TokenType::T_LBRACE === $token->type) {
                $this->flushText($parts, $textBuffer, $textStart, $textEnd);
                $parts[] = $this->parseArgument();
                $lastKey = array_key_last($parts);
                $last = $parts[$lastKey];
                $end = $last->getEndPosition();

                continue;
            }

            if (TokenType::T_HASH === $token->type && $this->inPluralContext()) {
                $this->flushText($parts, $textBuffer, $textStart, $textEnd);
                $parts[] = new PoundNode($token->position, $token->getEndPosition());
                $this->advance();
                $end = $token->getEndPosition();

                continue;
            }

            $this->appendTextToken($token, $textBuffer, $textStart, $textEnd);
            $this->advance();
            $end = $textEnd;
        }

        $this->flushText($parts, $textBuffer, $textStart, $textEnd);

        return new MessageNode($parts, $start, $end);
    }

    private function parseArgument(): SimpleArgumentNode|FormattedArgumentNode|SelectNode|PluralNode|SelectOrdinalNode|SpelloutNode|OrdinalNode|DurationNode|ChoiceNode
    {
        $startToken = $this->expect(TokenType::T_LBRACE, 'Expected "{" to start an argument.');
        $start = $startToken->position;

        $this->skipWhitespace();
        $nameToken = $this->current();
        if (!\in_array($nameToken->type, [TokenType::T_IDENTIFIER, TokenType::T_NUMBER], true)) {
            throw ParserException::withContext('Expected argument name.', $nameToken->position, $this->message);
        }
        if (TokenType::T_NUMBER === $nameToken->type && str_starts_with($nameToken->value, '-')) {
            throw ParserException::withContext('Argument name cannot be negative.', $nameToken->position, $this->message);
        }
        $this->advance();
        $name = $nameToken->value;
        $this->skipWhitespace();

        $endToken = $this->match(TokenType::T_RBRACE);
        if (null !== $endToken) {
            return new SimpleArgumentNode($name, $start, $endToken->getEndPosition());
        }

        $this->expect(TokenType::T_COMMA, 'Expected "," after argument name.');
        $this->skipWhitespace();

        $typeToken = $this->expect(TokenType::T_IDENTIFIER, 'Expected argument type.');
        $type = strtolower($typeToken->value);
        $this->skipWhitespace();

        $endToken = $this->match(TokenType::T_RBRACE);
        if (null !== $endToken) {
            if (\in_array($type, ['select', 'plural', 'selectordinal', 'choice'], true)) {
                throw ParserException::withContext(sprintf('Expected options for "%s" argument.', $type), $endToken->position, $this->message);
            }

            // Handle format types that require specific node classes, even without style
            return match ($type) {
                'spellout' => new SpelloutNode($name, $type, null, $start, $endToken->getEndPosition()),
                'ordinal' => new OrdinalNode($name, $type, null, $start, $endToken->getEndPosition()),
                'duration' => new DurationNode($name, $type, null, $start, $endToken->getEndPosition()),
                default => new FormattedArgumentNode($name, $type, null, $start, $endToken->getEndPosition()),
            };
        }

        $this->expect(TokenType::T_COMMA, 'Expected "," before argument style.');
        $this->skipWhitespace();

        if (\in_array($type, ['select', 'plural', 'selectordinal', 'choice'], true)) {
            return match ($type) {
                'choice' => $this->parseChoiceArgument($name, $start),
                default => $this->parseComplexArgument($name, $type, $start),
            };
        }

        $style = $this->collectStyleUntil(TokenType::T_RBRACE);
        $endToken = $this->expect(TokenType::T_RBRACE, 'Expected "}" to close argument.');

        // Handle specific format types with dedicated node classes
        return match ($type) {
            'spellout' => new SpelloutNode($name, $type, $style, $start, $endToken->getEndPosition()),
            'ordinal' => new OrdinalNode($name, $type, $style, $start, $endToken->getEndPosition()),
            'duration' => new DurationNode($name, $type, $style, $start, $endToken->getEndPosition()),
            default => new FormattedArgumentNode($name, $type, $style, $start, $endToken->getEndPosition()),
        };
    }

    private function parseChoiceArgument(string $name, int $start): ChoiceNode
    {
        $options = [];

        // Choice format: {name, choice, 0#none|1#one}
        // Options are NOT wrapped in outer braces like select/plural
        $this->skipWhitespace();

        if ($this->check(TokenType::T_RBRACE)) {
            throw ParserException::withContext('Choice argument must have at least one option.', $this->current()->position, $this->message);
        }

        while (!$this->tokens->isAtEnd() && !$this->check(TokenType::T_RBRACE)) {
            $option = $this->parseChoiceOption();
            $options[] = $option;

            $this->skipWhitespace();
            if ($this->check(TokenType::T_PIPE)) {
                $this->advance();
                $this->skipWhitespace();
            }
        }

        $endToken = $this->expect(TokenType::T_RBRACE, 'Expected "}" to close choice argument.');

        return new ChoiceNode($name, $options, $start, $endToken->getEndPosition());
    }

    private function parseChoiceOption(): ChoiceOptionNode
    {
        $startToken = $this->current();

        // Parse limit
        $limitToken = $this->expect(TokenType::T_NUMBER, 'Expected numeric limit for choice option.');
        $limit = (float) $limitToken->value;

        // Parse operator
        $this->skipWhitespace();
        $operatorToken = $this->current();
        $isExclusive = null;

        if (TokenType::T_HASH === $operatorToken->type) {
            $isExclusive = false;
            $this->advance();
        } elseif (TokenType::T_LT === $operatorToken->type) {
            $isExclusive = true;
            $this->advance();
        }

        if (null === $isExclusive) {
            throw ParserException::withContext('Expected "#" or "<" after choice limit.', $operatorToken->position, $this->message);
        }

        // Parse message
        $this->skipWhitespace();
        $message = $this->parseMessage([TokenType::T_PIPE]);

        return new ChoiceOptionNode(
            $limit,
            $isExclusive,
            $message,
            $startToken->position,
            $message->getEndPosition(),
        );
    }

    private function parseComplexArgument(string $name, string $type, int $start): SelectNode|PluralNode|SelectOrdinalNode
    {
        $offset = null;
        if (\in_array($type, ['plural', 'selectordinal'], true)) {
            $offset = $this->parseOffset();
        }

        /** @var list<OptionNode> $options */
        $options = [];

        while (!$this->tokens->isAtEnd() && !$this->check(TokenType::T_RBRACE)) {
            $this->skipWhitespace();
            if ($this->check(TokenType::T_RBRACE)) {
                break;
            }

            $selector = $this->parseSelector($type);
            $this->skipWhitespace();
            $this->expect(TokenType::T_LBRACE, 'Expected "{" to start option message.');
            $this->enterPluralContext($type);
            $message = $this->parseMessage();
            $this->exitPluralContext($type);
            $endToken = $this->expect(TokenType::T_RBRACE, 'Expected "}" to close option message.');

            $options[] = new OptionNode(
                $selector['key'],
                $message,
                $selector['start'],
                $endToken->getEndPosition(),
                $selector['explicit'],
                $selector['value'],
            );
        }

        $endToken = $this->expect(TokenType::T_RBRACE, 'Expected "}" to close argument.');

        return match ($type) {
            'select' => new SelectNode($name, $options, $start, $endToken->getEndPosition()),
            'selectordinal' => new SelectOrdinalNode($name, $options, $offset, $start, $endToken->getEndPosition()),
            default => new PluralNode($name, $options, $offset, $start, $endToken->getEndPosition()),
        };
    }

    private function parseOffset(): int|float|null
    {
        $this->skipWhitespace();
        $token = $this->current();

        if (TokenType::T_IDENTIFIER !== $token->type || 0 !== strcasecmp($token->value, 'offset')) {
            return null;
        }

        $this->advance();
        $this->skipWhitespace();
        $this->expect(TokenType::T_COLON, 'Expected ":" after offset.');
        $this->skipWhitespace();
        $number = $this->expect(TokenType::T_NUMBER, 'Expected numeric offset.');

        return $this->parseNumericValue($number->value);
    }

    /**
     * @return array{key: string, explicit: bool, value: int|float|null, start: int}
     */
    private function parseSelector(string $type): array
    {
        $token = $this->current();

        if (TokenType::T_EQUAL === $token->type) {
            if (!\in_array($type, ['plural', 'selectordinal'], true)) {
                throw ParserException::withContext('Explicit selectors are only valid for plural arguments.', $token->position, $this->message);
            }

            $start = $token->position;
            $this->advance();
            $number = $this->expect(TokenType::T_NUMBER, 'Expected number after "=".');

            return [
                'key' => '='.$number->value,
                'explicit' => true,
                'value' => $this->parseNumericValue($number->value),
                'start' => $start,
            ];
        }

        if (TokenType::T_IDENTIFIER !== $token->type) {
            throw ParserException::withContext('Expected selector keyword.', $token->position, $this->message);
        }

        $this->advance();

        return [
            'key' => $token->value,
            'explicit' => false,
            'value' => null,
            'start' => $token->position,
        ];
    }

    private function collectStyleUntil(TokenType $terminator): ?string
    {
        $style = '';

        while (!$this->tokens->isAtEnd()) {
            $token = $this->current();
            if ($terminator === $token->type) {
                break;
            }
            if (TokenType::T_LBRACE === $token->type) {
                throw ParserException::withContext('Unexpected "{" inside format style.', $token->position, $this->message);
            }
            $style .= $token->value;
            $this->advance();
        }

        return '' !== $style ? trim($style) : null;
    }

    /**
     * @param-out int  $start
     * @param-out int  $end
     */
    private function appendTextToken(Token $token, string &$buffer, ?int &$start, ?int &$end): void
    {
        if (null === $start) {
            $start = $token->position;
        }

        $buffer .= $token->value;
        $end = $token->getEndPosition();
    }

    /**
     * @param array<int, NodeInterface> $parts
     *
     * @param-out null                 $start
     * @param-out null                 $end
     */
    private function flushText(array &$parts, string &$buffer, ?int &$start, ?int &$end): void
    {
        if ('' !== $buffer && null !== $start && null !== $end) {
            $parts[] = new TextNode($buffer, $start, $end);
        }

        $buffer = '';
        $start = null;
        $end = null;
    }

    private function skipWhitespace(): void
    {
        while (TokenType::T_WHITESPACE === $this->current()->type) {
            $this->advance();
        }
    }

    private function check(TokenType $type): bool
    {
        return $this->current()->type === $type;
    }

    private function match(TokenType $type): ?Token
    {
        $token = $this->current();
        if ($token->type !== $type) {
            return null;
        }

        $this->advance();

        return $token;
    }

    private function expect(TokenType $type, string $message): Token
    {
        $token = $this->current();
        if ($token->type !== $type) {
            throw ParserException::withContext($message, $token->position, $this->message);
        }

        $this->advance();

        return $token;
    }

    private function current(): Token
    {
        return $this->tokens->current();
    }

    private function advance(): void
    {
        $this->tokens->next();
    }

    private function inPluralContext(): bool
    {
        return $this->pluralDepth > 0;
    }

    private function enterPluralContext(string $type): void
    {
        if (\in_array($type, ['plural', 'selectordinal'], true)) {
            $this->pluralDepth++;
        }
    }

    private function exitPluralContext(string $type): void
    {
        if (\in_array($type, ['plural', 'selectordinal'], true) && $this->pluralDepth > 0) {
            $this->pluralDepth--;
        }
    }

    private function parseNumericValue(string $value): int|float
    {
        return str_contains($value, '.') ? (float) $value : (int) $value;
    }
}
