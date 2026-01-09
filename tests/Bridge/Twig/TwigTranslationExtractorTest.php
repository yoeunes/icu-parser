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

namespace IcuParser\Tests\Bridge\Twig;

use IcuParser\Bridge\Twig\TwigTranslationExtractor;
use IcuParser\Type\ParameterType;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\TwigFilter;

final class TwigTranslationExtractorTest extends TestCase
{
    public function test_extracts_filter_usage(): void
    {
        $twig = $this->createEnvironment();
        $extractor = new TwigTranslationExtractor($twig);

        $template = "{{ 'app.welcome'|trans({'name': 'Ada', 'count': 2}, 'messages') }}";
        $usages = $extractor->extractFromSource($template, 'template.twig');

        $this->assertCount(1, $usages);
        $this->assertSame('app.welcome', $usages[0]->id);
        $this->assertSame('messages', $usages[0]->domain);
        $this->assertSame('template.twig', $usages[0]->file);
        $this->assertSame(1, $usages[0]->line);
        $this->assertSame([
            'name' => ParameterType::STRING,
            'count' => ParameterType::NUMBER,
        ], $usages[0]->parameters);
    }

    public function test_extracts_trans_tag_usage(): void
    {
        $twig = $this->createEnvironment(true);
        $extractor = new TwigTranslationExtractor($twig);

        $template = <<<'TWIG'
            {% trans with {'name': 'Ada'} %}app.tagged{% endtrans %}
            TWIG;

        $usages = $extractor->extractFromSource($template, 'template.twig');

        $this->assertCount(1, $usages);
        $this->assertSame('app.tagged', $usages[0]->id);
        $this->assertSame(['name' => ParameterType::STRING], $usages[0]->parameters);
    }

    public function test_skips_dynamic_id(): void
    {
        $twig = $this->createEnvironment();
        $extractor = new TwigTranslationExtractor($twig);

        $template = "{{ key|trans({'name': 'Ada'}) }}";
        $usages = $extractor->extractFromSource($template, 'template.twig');

        $this->assertSame([], $usages);
    }

    public function test_handles_filter_without_parameters(): void
    {
        $twig = $this->createEnvironment();
        $extractor = new TwigTranslationExtractor($twig);

        $template = "{{ 'app.simple'|trans }}";
        $usages = $extractor->extractFromSource($template, 'template.twig');

        $this->assertCount(1, $usages);
        $this->assertSame('app.simple', $usages[0]->id);
        $this->assertSame([], $usages[0]->parameters);
    }

    public function test_extract_from_file(): void
    {
        $twig = $this->createEnvironment();
        $extractor = new TwigTranslationExtractor($twig);

        $template = "{{ 'app.file'|trans }}";
        $path = tempnam(sys_get_temp_dir(), 'twig');
        file_put_contents($path, $template);

        try {
            $usages = $extractor->extractFromFile($path);

            $this->assertCount(1, $usages);
            $this->assertSame('app.file', $usages[0]->id);
            $this->assertSame($path, $usages[0]->file);
        } finally {
            unlink($path);
        }
    }

    public function test_extract_from_file_nonexistent(): void
    {
        $twig = $this->createEnvironment();
        $extractor = new TwigTranslationExtractor($twig);

        $usages = $extractor->extractFromFile('/nonexistent/path');

        $this->assertSame([], $usages);
    }

    public function test_extracts_filter_with_domain(): void
    {
        $twig = $this->createEnvironment();
        $extractor = new TwigTranslationExtractor($twig);

        $template = "{{ 'app.domain'|trans({}, 'custom') }}";
        $usages = $extractor->extractFromSource($template, 'template.twig');

        $this->assertCount(1, $usages);
        $this->assertSame('app.domain', $usages[0]->id);
        $this->assertSame('custom', $usages[0]->domain);
    }

    public function test_extracts_trans_tag_with_domain(): void
    {
        $twig = $this->createEnvironment(true);
        $extractor = new TwigTranslationExtractor($twig);

        $template = <<<'TWIG'
            {% trans with {'name': 'Ada'} from 'custom' %}app.domain{% endtrans %}
            TWIG;

        $usages = $extractor->extractFromSource($template, 'template.twig');

        $this->assertCount(1, $usages);
        $this->assertSame('app.domain', $usages[0]->id);
        $this->assertSame('custom', $usages[0]->domain);
    }

    public function test_handles_mixed_parameter_types(): void
    {
        $twig = $this->createEnvironment();
        $extractor = new TwigTranslationExtractor($twig);

        $template = "{{ 'app.mixed'|trans({'str': 'text', 'num': 42, 'float': 3.14, 'obj': some_var}) }}";
        $usages = $extractor->extractFromSource($template, 'template.twig');

        $this->assertCount(1, $usages);
        $this->assertSame([
            'str' => ParameterType::STRING,
            'num' => ParameterType::NUMBER,
            'float' => ParameterType::NUMBER,
            'obj' => ParameterType::MIXED,
        ], $usages[0]->parameters);
    }

    private function createEnvironment(bool $withTransTag = false): Environment
    {
        if (!class_exists(Environment::class)) {
            $this->markTestSkipped('Twig is not installed.');
        }

        $twig = new Environment(new ArrayLoader(), [
            'cache' => false,
            'autoescape' => false,
            'strict_variables' => false,
        ]);

        $twig->addFilter(new TwigFilter('trans', static fn (string $id): string => $id));

        if ($withTransTag && class_exists(TranslationExtension::class) && interface_exists(TranslatorInterface::class)) {
            $translator = new class implements TranslatorInterface {
                /**
                 * @param array<string, mixed> $parameters
                 */
                public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
                {
                    return $id;
                }

                public function getLocale(): string
                {
                    return 'en';
                }

                public function setLocale(string $locale): void {}
            };

            $twig->addExtension(new TranslationExtension($translator));
        }

        return $twig;
    }
}
