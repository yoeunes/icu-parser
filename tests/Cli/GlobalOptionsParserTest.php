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

namespace IcuParser\Tests\Cli;

use IcuParser\Cli\GlobalOptionsParser;
use PHPUnit\Framework\TestCase;

final class GlobalOptionsParserTest extends TestCase
{
    private GlobalOptionsParser $parser;

    protected function setUp(): void
    {
        $this->parser = new GlobalOptionsParser();
    }

    public function test_parse_no_options(): void
    {
        $parsed = $this->parser->parse(['arg1', 'arg2']);

        $this->assertNull($parsed->options->ansi);
        $this->assertFalse($parsed->options->quiet);
        $this->assertTrue($parsed->options->visuals);
        $this->assertFalse($parsed->options->help);
        $this->assertSame(['arg1', 'arg2'], $parsed->args);
        $this->assertNull($parsed->error);
    }

    public function test_parse_help(): void
    {
        $parsed = $this->parser->parse(['--help', 'arg']);

        $this->assertTrue($parsed->options->help);
        $this->assertSame(['arg'], $parsed->args);
    }

    public function test_parse_ansi(): void
    {
        $parsed = $this->parser->parse(['--ansi', 'arg']);

        $this->assertTrue($parsed->options->ansi);
        $this->assertSame(['arg'], $parsed->args);
    }

    public function test_parse_no_ansi(): void
    {
        $parsed = $this->parser->parse(['--no-ansi', 'arg']);

        $this->assertFalse($parsed->options->ansi);
        $this->assertSame(['arg'], $parsed->args);
    }

    public function test_parse_quiet(): void
    {
        $parsed = $this->parser->parse(['-q', 'arg']);

        $this->assertTrue($parsed->options->quiet);
        $this->assertSame(['arg'], $parsed->args);
    }

    public function test_parse_no_visuals(): void
    {
        $parsed = $this->parser->parse(['--no-visuals', 'arg']);

        $this->assertFalse($parsed->options->visuals);
        $this->assertSame(['arg'], $parsed->args);
    }

    public function test_parse_unknown_option(): void
    {
        $parsed = $this->parser->parse(['--unknown', 'arg']);

        // Unknown options are passed through as args (allows --version alias to work)
        $this->assertSame(['--unknown', 'arg'], $parsed->args);
    }
}
