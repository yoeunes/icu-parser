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

namespace IcuParser\Validator;

use IcuParser\Loader\TranslationEntry;
use IcuParser\Type\ParameterType;

final class CrossLocaleValidator
{
    /**
     * @param array<string, array<string, array<string, TranslationEntry>>>             $entriesByDomain
     * @param array<string, array<string, array<string, array<string, ParameterType>>>> $typesByDomain
     *
     * @return list<CrossLocaleIssue>
     */
    public function validate(array $entriesByDomain, array $typesByDomain, ?string $referenceLocale = null): array
    {
        $issues = [];

        foreach ($entriesByDomain as $domain => $entriesById) {
            foreach ($entriesById as $id => $entriesByLocale) {
                $baselineLocale = $this->resolveBaselineLocale($entriesByLocale, $referenceLocale);
                if (null === $baselineLocale) {
                    continue;
                }

                $baselineTypes = $typesByDomain[$domain][$id][$baselineLocale] ?? null;
                if (null === $baselineTypes) {
                    continue;
                }

                foreach ($entriesByLocale as $locale => $entry) {
                    if ($locale === $baselineLocale) {
                        continue;
                    }

                    $currentTypes = $typesByDomain[$domain][$id][$locale] ?? [];

                    foreach ($baselineTypes as $name => $expected) {
                        if (!isset($currentTypes[$name])) {
                            $issues[] = new CrossLocaleIssue(
                                sprintf(
                                    'Missing parameter "%s" compared to locale "%s".',
                                    $name,
                                    $baselineLocale,
                                ),
                                $domain,
                                $id,
                                $locale,
                                $entry->file,
                                $entry->line,
                                'cross_locale.missing_parameter',
                            );

                            continue;
                        }

                        $actual = $currentTypes[$name];
                        if ($this->isTypeMismatch($expected, $actual)) {
                            $issues[] = new CrossLocaleIssue(
                                sprintf(
                                    'Parameter "%s" expects "%s" (locale "%s"), but "%s" was inferred.',
                                    $name,
                                    $expected->value,
                                    $baselineLocale,
                                    $actual->value,
                                ),
                                $domain,
                                $id,
                                $locale,
                                $entry->file,
                                $entry->line,
                                'cross_locale.type_mismatch',
                            );
                        }
                    }
                }
            }
        }

        return $issues;
    }

    /**
     * @param array<string, TranslationEntry> $entriesByLocale
     */
    private function resolveBaselineLocale(array $entriesByLocale, ?string $referenceLocale): ?string
    {
        if (null !== $referenceLocale && isset($entriesByLocale[$referenceLocale])) {
            return $referenceLocale;
        }

        $locales = array_keys($entriesByLocale);

        return $locales[0] ?? null;
    }

    private function isTypeMismatch(ParameterType $expected, ParameterType $actual): bool
    {
        if (ParameterType::MIXED === $expected || ParameterType::MIXED === $actual) {
            return false;
        }

        return $expected !== $actual;
    }
}
