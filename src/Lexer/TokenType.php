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

namespace IcuParser\Lexer;

enum TokenType: string
{
    case T_LBRACE = 'lbrace';
    case T_RBRACE = 'rbrace';
    case T_COMMA = 'comma';
    case T_COLON = 'colon';
    case T_HASH = 'hash';
    case T_EQUAL = 'equal';
    case T_PIPE = 'pipe';
    case T_LT = 'lt';
    case T_IDENTIFIER = 'identifier';
    case T_NUMBER = 'number';
    case T_TEXT = 'text';
    case T_WHITESPACE = 'whitespace';
    case T_EOF = 'eof';
}
