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
 * Named style types for duration formatters.
 */
enum DurationStyle: string
{
    case DEFAULT = '';
    case LONG = 'long';
    case SHORT = 'short';
}
