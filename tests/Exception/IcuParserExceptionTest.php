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

use IcuParser\Exception\IcuParserException;
use PHPUnit\Framework\TestCase;

final class IcuParserExceptionTest extends TestCase
{
    public function test_constructor_with_minimal_parameters(): void
    {
        $exception = new IcuParserException('Test message');

        $this->assertSame('Test message', $exception->getMessage());
        $this->assertNull($exception->getPosition());
        $this->assertNull($exception->getSnippet());
        $this->assertNull($exception->getErrorCode());
    }

    public function test_constructor_with_all_parameters(): void
    {
        $previous = new \Exception('Previous');
        $exception = new IcuParserException(
            'Test message',
            42,
            'snippet',
            'ERROR_CODE',
            $previous
        );

        $this->assertSame('Test message', $exception->getMessage());
        $this->assertSame(42, $exception->getPosition());
        $this->assertSame('snippet', $exception->getSnippet());
        $this->assertSame('ERROR_CODE', $exception->getErrorCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function test_getters_return_correct_values(): void
    {
        $exception = new IcuParserException(
            'Message',
            10,
            'code snippet',
            'PARSE_ERROR'
        );

        $this->assertSame(10, $exception->getPosition());
        $this->assertSame('code snippet', $exception->getSnippet());
        $this->assertSame('PARSE_ERROR', $exception->getErrorCode());
    }
}