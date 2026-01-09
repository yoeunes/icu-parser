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
                $icuVersionValue = intl_get_icu_version();
                if (is_string($icuVersionValue) || is_numeric($icuVersionValue)) {
                    $icuVersion = (string) $icuVersionValue;
                } else {
                    $icuVersion = 'unknown';
                }
            } elseif (defined('INTL_ICU_VERSION')) {
                $icuVersion = (string) \INTL_ICU_VERSION;
            } else {
                $icuVersion = 'unknown';
            }
        }

        $locale = 'unknown';
        if ($intlLoaded && class_exists('Locale')) {
            $localeValue = \Locale::getDefault();
            $locale = is_string($localeValue) ? $localeValue : (string) $localeValue;
        }

        return new self(\PHP_VERSION, $intlVersion, $icuVersion, $locale);
    }

    /**
     * @return array{php: string, intl: string, icu: string, locale: string}
     */
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
