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

namespace IcuParser\Tests\Bridge\Php;

use IcuParser\Bridge\Php\PhpTranslationExtractor;
use IcuParser\Type\ParameterType;
use PHPUnit\Framework\TestCase;

final class PhpTranslationExtractorTest extends TestCase
{
    public function test_extracts_method_call_with_parameters(): void
    {
        $source = <<<'PHP'
            <?php

            $translator->trans('app.welcome', ['name' => 'Ada', 'count' => 3], 'messages');
            PHP;

        $extractor = new PhpTranslationExtractor();
        $usages = $extractor->extractFromSource($source, '/app/Controller.php');

        $this->assertCount(1, $usages);
        $this->assertSame('app.welcome', $usages[0]->id);
        $this->assertSame('messages', $usages[0]->domain);
        $this->assertSame('/app/Controller.php', $usages[0]->file);
        $this->assertSame(3, $usages[0]->line);
        $this->assertSame([
            'name' => ParameterType::STRING,
            'count' => ParameterType::NUMBER,
        ], $usages[0]->parameters);
    }

    public function test_extracts_nullsafe_method_call(): void
    {
        $source = <<<'PHP'
            <?php

            $translator?->trans('app.nullsafe', array('count' => 5));
            PHP;

        $extractor = new PhpTranslationExtractor();
        $usages = $extractor->extractFromSource($source, '/app/Controller.php');

        $this->assertCount(1, $usages);
        $this->assertSame('app.nullsafe', $usages[0]->id);
        $this->assertSame([
            'count' => ParameterType::NUMBER,
        ], $usages[0]->parameters);
    }

    public function test_skips_non_method_calls(): void
    {
        $source = <<<'PHP'
            <?php

            trans('app.missing');
            PHP;

        $extractor = new PhpTranslationExtractor();
        $usages = $extractor->extractFromSource($source, '/app/Controller.php');

        $this->assertSame([], $usages);
    }

    public function test_skips_dynamic_id(): void
    {
        $source = <<<'PHP'
            <?php

            $id = 'app.dynamic';
            $translator->trans($id, ['name' => 'Ada']);
            PHP;

        $extractor = new PhpTranslationExtractor();
        $usages = $extractor->extractFromSource($source, '/app/Controller.php');

        $this->assertSame([], $usages);
    }
}
