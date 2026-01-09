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

namespace IcuParser\Tests\Cli\Command;

use IcuParser\Cli\Command\AuditCommand;
use IcuParser\Cli\GlobalOptions;
use IcuParser\Cli\Input;
use IcuParser\Cli\Output;
use IcuParser\Tests\Support\FilesystemTestCase;

final class AuditCommandTest extends FilesystemTestCase
{
    public function test_reports_no_issues_for_valid_usage(): void
    {
        $this->writeFile('translations/messages.en.yaml', "app:\n  hello: \"Hello {name}\"\n");
        $this->writeFile('src/Controller.php', <<<'PHP'
            <?php

            $translator->trans('app.hello', ['name' => 'Ada']);
            PHP);

        [$status, $output] = $this->runAudit($this->createTempDir());

        $this->assertSame(0, $status);
        $this->assertStringContainsString('No issues found.', $output);
    }

    public function test_reports_semantic_issues(): void
    {
        $this->writeFile('translations/messages.en.yaml', "app:\n  count: \"{count, plural, one {1}}\"\n");

        [$status, $output] = $this->runAudit($this->createTempDir());

        $this->assertSame(1, $status);
        $this->assertStringContainsString('Semantic error in "app.count"', $output);
        $this->assertStringContainsString('Missing required "other" option', $output);
    }

    public function test_reports_cross_locale_issues(): void
    {
        $this->writeFile('translations/messages.en.yaml', "app:\n  greet: \"Hello {name}\"\n");
        $this->writeFile('translations/messages.fr.yaml', "app:\n  greet: \"Bonjour {nom}\"\n");

        [$status, $output] = $this->runAudit($this->createTempDir());

        $this->assertSame(1, $status);
        $this->assertStringContainsString('Missing parameter "name"', $output);
        $this->assertStringContainsString('Consistency', $output);
    }

    public function test_reports_usage_type_mismatch(): void
    {
        $this->writeFile('translations/messages.en.yaml', "app:\n  count: \"{count, plural, one {1} other {#}}\"\n");
        $this->writeFile('src/Controller.php', <<<'PHP'
            <?php

            $translator->trans('app.count', ['count' => 'five']);
            PHP);

        [$status, $output] = $this->runAudit($this->createTempDir());

        $this->assertSame(1, $status);
        $this->assertStringContainsString('expects "number"', $output);
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function runAudit(string $path): array
    {
        $command = new AuditCommand();
        $input = new Input('audit', [$path], new GlobalOptions(false, false, false, false));
        $output = new Output(false, false);

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        return [$status, $content];
    }
}
