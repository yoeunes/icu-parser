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

namespace IcuParser\Formatter;

use IcuParser\Node\MessageNode;
use IcuParser\NodeVisitor\PrettyPrintVisitor;
use IcuParser\NodeVisitor\TokenStylerInterface;

/**
 * Pretty formats an ICU message without evaluating it.
 */
final readonly class PrettyFormatter
{
    public function __construct(private FormatOptions $defaultOptions = new FormatOptions()) {}

    public function format(MessageNode $message, ?FormatOptions $options = null): string
    {
        $options ??= $this->defaultOptions;

        $visitor = new PrettyPrintVisitor($options);

        return $message->accept($visitor);
    }

    public function formatStyled(
        MessageNode $message,
        TokenStylerInterface $styler,
        ?FormatOptions $options = null,
    ): string {
        $options ??= $this->defaultOptions;

        $visitor = new PrettyPrintVisitor($options, $styler);

        return $message->accept($visitor);
    }
}
