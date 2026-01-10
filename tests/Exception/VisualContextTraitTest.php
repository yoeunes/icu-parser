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

use IcuParser\Exception\VisualContextTrait;
use PHPUnit\Framework\TestCase;

final class VisualContextTraitTest extends TestCase
{
    public function test_exposes_source_and_snippet(): void
    {
        $exception = new class('Failure') extends \RuntimeException {
            use VisualContextTrait;

            public function initialize(?int $position, ?string $source): void
            {
                $this->initializeContext($position, $source);
            }
        };

        $exception->initialize(3, 'abcdef');

        $this->assertSame('abcdef', $exception->getMessageSource());
        $this->assertStringContainsString('Line 1:', $exception->getVisualSnippet());
        $this->assertStringContainsString('^', $exception->getVisualSnippet());
    }

    public function test_snippet_handles_long_lines_with_ellipsis(): void
    {
        $exception = new class('Failure') extends \RuntimeException {
            use VisualContextTrait;

            public function initialize(?int $position, ?string $source): void
            {
                $this->initializeContext($position, $source);
            }
        };

        $source = str_repeat('a', 120);
        $exception->initialize(60, $source);

        $snippet = $exception->getVisualSnippet();
        $this->assertStringContainsString('Line 1:', $snippet);
        $this->assertStringContainsString('...', $snippet);
        $this->assertStringContainsString('^', $snippet);
    }

    public function test_snippet_handles_missing_or_invalid_position(): void
    {
        $exception = new class('Failure') extends \RuntimeException {
            use VisualContextTrait;

            public function initialize(?int $position, ?string $source): void
            {
                $this->initializeContext($position, $source);
            }
        };

        $exception->initialize(null, null);
        $this->assertSame('', $exception->getVisualSnippet());

        $exception->initialize(-1, 'abc');
        $this->assertSame('', $exception->getVisualSnippet());

        $exception->initialize(10, 'abc');
        $this->assertStringContainsString('Line 1:', $exception->getVisualSnippet());
    }
}
