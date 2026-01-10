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

    public function test_detect_when_intl_extension_not_loaded(): void
    {
        // This test would simulate when intl extension is not loaded
        // Since we can't easily unload extensions in tests, we'll test the logic indirectly
        $info = IcuRuntimeInfo::detect();

        // The method should handle the case gracefully
        $this->assertIsString($info->intlVersion);
        $this->assertIsString($info->icuVersion);

        // When intl is loaded, it should return the version number
        // When intl is not loaded, it should return 'missing' or 'unknown'
        if (extension_loaded('intl')) {
            $this->assertMatchesRegularExpression('/^\d+\.\d+/', $info->intlVersion);
        } else {
            $this->assertContains($info->intlVersion, ['missing', 'unknown']);
        }
    }
}
