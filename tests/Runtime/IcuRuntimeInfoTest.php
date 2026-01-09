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

namespace IcuParser\Tests\Runtime;

use IcuParser\Runtime\IcuRuntimeInfo;
use PHPUnit\Framework\TestCase;

final class IcuRuntimeInfoTest extends TestCase
{
    public function test_detect_returns_instance(): void
    {
        $info = IcuRuntimeInfo::detect();

        $this->assertInstanceOf(IcuRuntimeInfo::class, $info);
        $this->assertIsString($info->phpVersion);
        $this->assertIsString($info->intlVersion);
        $this->assertIsString($info->icuVersion);
        $this->assertIsString($info->locale);
    }

    public function test_json_serialize(): void
    {
        $info = new IcuRuntimeInfo('8.2', '1.0', '70.1', 'en_US');

        $expected = [
            'php' => '8.2',
            'intl' => '1.0',
            'icu' => '70.1',
            'locale' => 'en_US',
        ];

        $this->assertSame($expected, $info->jsonSerialize());
    }
}
