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

namespace IcuParser\Runtime;

/**
 * Captures runtime ICU information for CLI diagnostics.
 */
final readonly class IcuRuntimeInfo implements \JsonSerializable
{
    public function __construct(
        public string $phpVersion,
        public string $intlVersion,
        public string $icuVersion,
        public string $locale,
    ) {}

    public static function detect(): self
    {
        $intlLoaded = extension_loaded('intl');
        $intlVersion = $intlLoaded ? (phpversion('intl') ?: 'unknown') : 'missing';

        $icuVersion = 'missing';
        if ($intlLoaded) {
            if (function_exists('intl_get_icu_version')) {
                $icuVersion = (string) intl_get_icu_version();
            } elseif (class_exists('Intl') && method_exists('Intl', 'getIcuVersion')) {
                $icuVersion = (string) \Intl::getIcuVersion();
            } elseif (defined('INTL_ICU_VERSION')) {
                $icuVersion = (string) INTL_ICU_VERSION;
            } else {
                $icuVersion = 'unknown';
            }
        }

        $locale = 'unknown';
        if ($intlLoaded && class_exists('Locale')) {
            $locale = \Locale::getDefault();
        }

        return new self(\PHP_VERSION, $intlVersion, $icuVersion, $locale);
    }

    public function jsonSerialize(): array
    {
        return [
            'php' => $this->phpVersion,
            'intl' => $this->intlVersion,
            'icu' => $this->icuVersion,
            'locale' => $this->locale,
        ];
    }
}
