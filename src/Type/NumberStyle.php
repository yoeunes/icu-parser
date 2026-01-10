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

namespace IcuParser\Type;

/**
 * Named style types for number formatters.
 */
enum NumberStyle: string
{
    case DEFAULT = '';
    case DECIMAL = 'decimal';
    case CURRENCY = 'currency';
    case PERCENT = 'percent';
    case SCIENTIFIC = 'scientific';
    case COMPACT_DECIMAL = 'compact-decimal';
    case COMPACT_CURRENCY = 'compact-currency';
    case LONG = 'long';
    case SHORT = 'short';
}
