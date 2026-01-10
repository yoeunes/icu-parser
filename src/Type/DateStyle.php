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
 * Named style types for date formatters.
 */
enum DateStyle: string
{
    case DEFAULT = '';
    case FULL = 'full';
    case LONG = 'long';
    case MEDIUM = 'medium';
    case SHORT = 'short';
}
