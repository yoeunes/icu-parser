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

use IcuParser\Cli\Command\LintCommand;
use IcuParser\Cli\GlobalOptions;
use IcuParser\Cli\Input;
use IcuParser\Cli\Output;
use IcuParser\Tests\Support\FilesystemTestCase;

final class LintCommandTest extends FilesystemTestCase
{
    public function test_get_name(): void
    {
        $command = new LintCommand();

        $this->assertSame('lint', $command->getName());
    }

    public function test_get_aliases(): void
    {
        $command = new LintCommand();

        $this->assertSame([], $command->getAliases());
    }

    public function test_get_description(): void
    {
        $command = new LintCommand();

        $this->assertSame('Validate ICU messages in YAML and XLIFF files.', $command->getDescription());
    }

    public function test_run_with_nonexistent_path_returns_error(): void
    {
        $command = new LintCommand();
        $input = new Input('lint', ['/nonexistent'], new GlobalOptions(false, false, false, false));
        $output = new Output(false, false);

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        $this->assertSame(1, $status);
        $this->assertStringContainsString('Error: Path not found.', $content);
    }

    public function test_run_with_no_translation_files(): void
    {
        $command = new LintCommand();
        $tempDir = $this->createTempDir();
        $input = new Input('lint', [$tempDir], new GlobalOptions(false, false, false, false));
        $output = new Output(false, false);

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        $this->assertSame(0, $status);
        $this->assertStringContainsString('No translation messages found.', $content);
    }

    public function test_run_with_valid_yaml_files(): void
    {
        $command = new LintCommand();
        $tempDir = $this->createTempDir();

        $this->writeFile('translations/messages.en.yaml', "app:\n  hello: \"Hello {name}\"\n  count: \"{count, plural, one {# item} other {# items}}\"\n");

        $input = new Input('lint', [$tempDir], new GlobalOptions(false, false, false, false));
        $output = new Output(false, false);

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        $this->assertSame(0, $status);
        $this->assertStringContainsString('Checked 2 message(s) in 1 file(s).', $content);
        $this->assertStringContainsString('No issues found.', $content);
    }

    public function test_run_with_invalid_yaml_messages(): void
    {
        $command = new LintCommand();
        $tempDir = $this->createTempDir();

        $this->writeFile('translations/messages.en.yaml', "app:\n  invalid: \"Hello {unclosed\"\n  valid: \"Hello {name}\"\n");

        $input = new Input('lint', [$tempDir], new GlobalOptions(false, false, false, false));
        $output = new Output(false, false);

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        $this->assertSame(1, $status);
        $this->assertStringContainsString('Checked 2 message(s) in 1 file(s).', $content);
        $this->assertStringContainsString('1 issue(s) found.', $content);
        $this->assertStringContainsString('Error:', $content);
    }

    public function test_run_with_semantic_errors(): void
    {
        $command = new LintCommand();
        $tempDir = $this->createTempDir();

        $this->writeFile('translations/messages.en.yaml', "app:\n  plural: \"{count, plural, one {#}}\"\n");

        $input = new Input('lint', [$tempDir], new GlobalOptions(false, false, false, false));
        $output = new Output(false, false);

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        $this->assertSame(1, $status);
        $this->assertStringContainsString('Checked 1 message(s) in 1 file(s).', $content);
        $this->assertStringContainsString('1 issue(s) found.', $content);
        $this->assertStringContainsString('Semantic error:', $content);
        $this->assertStringContainsString('Missing required "other" option', $content);
    }

    public function test_run_with_banner_enabled(): void
    {
        $command = new LintCommand();
        $tempDir = $this->createTempDir();

        $this->writeFile('translations/messages.en.yaml', "app:\n  hello: \"Hello {name}\"\n");

        $input = new Input('lint', [$tempDir], new GlobalOptions(false, false, true, false));
        $output = new Output(false, false);

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        $this->assertSame(0, $status);
        $this->assertStringContainsString('Lint', $content);
        $this->assertStringContainsString('ICU MessageFormat linting', $content);
        $this->assertStringContainsString('Intl', $content);
        $this->assertStringContainsString('ICU', $content);
        $this->assertStringContainsString('Locale', $content);
    }

    public function test_run_with_multiple_files(): void
    {
        $command = new LintCommand();
        $tempDir = $this->createTempDir();

        $this->writeFile('translations/messages.en.yaml', "app:\n  hello: \"Hello {name}\"\n");
        $this->writeFile('translations/messages.fr.yaml', "app:\n  hello: \"Bonjour {name}\"\n");
        $this->writeFile('translations/validators.en.yaml', "form:\n  email: \"Please enter a valid email\"\n");

        $input = new Input('lint', [$tempDir], new GlobalOptions(false, false, false, false));
        $output = new Output(false, false);

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        $this->assertSame(0, $status);
        $this->assertStringContainsString('Checked 3 message(s) in 3 file(s).', $content);
        $this->assertStringContainsString('No issues found.', $content);
    }

    public function test_run_with_xliff_files(): void
    {
        $command = new LintCommand();
        $tempDir = $this->createTempDir();

        $this->writeFile('translations/messages.en.xlf', '<?xml version="1.0" encoding="utf-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
  <file source-language="en" target-language="en" datatype="plaintext">
    <body>
      <trans-unit id="app.hello">
        <source>Hello {name}</source>
        <target>Hello {name}</target>
      </trans-unit>
    </body>
  </file>
</xliff>');

        $input = new Input('lint', [$tempDir], new GlobalOptions(false, false, false, false));
        $output = new Output(false, false);

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        $this->assertSame(0, $status);
        $this->assertStringContainsString('Checked 1 message(s) in 1 file(s).', $content);
        $this->assertStringContainsString('No issues found.', $content);
    }

    public function test_run_with_mixed_valid_and_invalid_files(): void
    {
        $command = new LintCommand();
        $tempDir = $this->createTempDir();

        $this->writeFile('translations/valid.en.yaml', "app:\n  hello: \"Hello {name}\"\n");
        $this->writeFile('translations/invalid.en.yaml', "app:\n  broken: \"Hello {unclosed\"\n");

        $input = new Input('lint', [$tempDir], new GlobalOptions(false, false, false, false));
        $output = new Output(false, false);

        ob_start();
        $status = $command->run($input, $output);
        $content = ob_get_clean() ?: '';

        $this->assertSame(1, $status);
        $this->assertStringContainsString('Checked 2 message(s) in 2 file(s).', $content);
        $this->assertStringContainsString('1 issue(s) found.', $content);
    }

    public function test_run_with_current_working_directory(): void
    {
        $command = new LintCommand();
        $tempDir = $this->createTempDir();

        $this->writeFile('translations/messages.en.yaml', "app:\n  hello: \"Hello {name}\"\n");

        // Change to temp directory and run without args
        $originalCwd = getcwd();
        if (false === $originalCwd) {
            $this->fail('Unable to resolve current working directory.');
        }
        chdir($tempDir);

        try {
            $input = new Input('lint', [], new GlobalOptions(false, false, false, false));
            $output = new Output(false, false);

            ob_start();
            $status = $command->run($input, $output);
            $content = ob_get_clean() ?: '';

            $this->assertSame(0, $status);
            $this->assertStringContainsString('Checked 1 message(s) in 1 file(s).', $content);
            $this->assertStringContainsString('No issues found.', $content);
        } finally {
            chdir($originalCwd);
        }
    }
}
