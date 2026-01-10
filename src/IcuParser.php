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

namespace IcuParser;

use IcuParser\Exception\IcuParserException;
use IcuParser\Formatter\FormatOptions;
use IcuParser\Formatter\PrettyFormatter;
use IcuParser\Highlight\Highlighter;
use IcuParser\Highlight\HighlightTheme;
use IcuParser\Node\MessageNode;
use IcuParser\Parser\Parser;
use IcuParser\Type\TypeInferer;
use IcuParser\Type\TypeMap;

/**
 * Entry point for the ICU MessageFormat parser.
 */
final readonly class IcuParser
{
    public const VERSION = '0.1.0';

    public function __construct(
        private Parser $parser = new Parser(),
        private TypeInferer $typeInferer = new TypeInferer(),
        private Highlighter $highlighter = new Highlighter(),
        private PrettyFormatter $formatter = new PrettyFormatter(),
    ) {}

    /**
     * @throws IcuParserException
     */
    public function parse(string $message): MessageNode
    {
        return $this->parser->parse($message);
    }

    /**
     * @throws IcuParserException
     */
    public function infer(string $message): TypeMap
    {
        $ast = $this->parse($message);

        return $this->typeInferer->infer($ast);
    }

    /**
     * @throws IcuParserException
     */
    public function highlight(string $message, ?HighlightTheme $theme = null): string
    {
        return $this->highlighter->highlight($message, $theme);
    }

    /**
     * @throws IcuParserException
     */
    public function format(string $message, ?FormatOptions $options = null): string
    {
        $ast = $this->parse($message);

        return $this->formatter->format($ast, $options);
    }
}
