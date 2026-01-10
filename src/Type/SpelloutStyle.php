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
 * Named style types for spellout formatters.
 */
enum SpelloutStyle: string
{
    case DEFAULT = '';
    case SPELLOUT = 'spellout';
    case ORDINAL = 'ordinal';
    case CARDINAL = 'cardinal';
    case YEAR = 'year';
}
