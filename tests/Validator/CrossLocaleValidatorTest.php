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

namespace IcuParser\Tests\Validator;

use IcuParser\Loader\TranslationEntry;
use IcuParser\Type\ParameterType;
use IcuParser\Validator\CrossLocaleValidator;
use PHPUnit\Framework\TestCase;

final class CrossLocaleValidatorTest extends TestCase
{
    public function test_reports_missing_parameter_across_locales(): void
    {
        $entries = [
            'messages' => [
                'app.greet' => [
                    'en' => new TranslationEntry('messages.en.yaml', 'en', 'messages', 'app.greet', '{name}', 3),
                    'fr' => new TranslationEntry('messages.fr.yaml', 'fr', 'messages', 'app.greet', '{nom}', 4),
                ],
            ],
        ];

        $types = [
            'messages' => [
                'app.greet' => [
                    'en' => ['name' => ParameterType::STRING],
                    'fr' => ['nom' => ParameterType::STRING],
                ],
            ],
        ];

        $issues = (new CrossLocaleValidator())->validate($entries, $types, 'en');

        $this->assertCount(1, $issues);
        $this->assertSame('cross_locale.missing_parameter', $issues[0]->code);
        $this->assertSame('fr', $issues[0]->locale);
    }

    public function test_reports_type_mismatch_across_locales(): void
    {
        $entries = [
            'messages' => [
                'app.count' => [
                    'en' => new TranslationEntry('messages.en.yaml', 'en', 'messages', 'app.count', '{count, plural, one {1} other {#}}', 2),
                    'fr' => new TranslationEntry('messages.fr.yaml', 'fr', 'messages', 'app.count', '{count}', 2),
                ],
            ],
        ];

        $types = [
            'messages' => [
                'app.count' => [
                    'en' => ['count' => ParameterType::NUMBER],
                    'fr' => ['count' => ParameterType::STRING],
                ],
            ],
        ];

        $issues = (new CrossLocaleValidator())->validate($entries, $types, 'en');

        $this->assertCount(1, $issues);
        $this->assertSame('cross_locale.type_mismatch', $issues[0]->code);
    }

    public function test_respects_reference_locale_when_available(): void
    {
        $entries = [
            'messages' => [
                'app.greet' => [
                    'fr' => new TranslationEntry('messages.fr.yaml', 'fr', 'messages', 'app.greet', '{name}', 3),
                    'en' => new TranslationEntry('messages.en.yaml', 'en', 'messages', 'app.greet', '{name}', 3),
                ],
            ],
        ];

        $types = [
            'messages' => [
                'app.greet' => [
                    'en' => ['name' => ParameterType::STRING],
                    'fr' => ['name' => ParameterType::STRING],
                ],
            ],
        ];

        $issues = (new CrossLocaleValidator())->validate($entries, $types, 'fr');

        $this->assertSame([], $issues);
    }
}
