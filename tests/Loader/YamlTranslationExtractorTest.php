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

namespace IcuParser\Tests\Loader;

use IcuParser\Loader\YamlTranslationExtractor;
use IcuParser\Tests\Support\FilesystemTestCase;

final class YamlTranslationExtractorTest extends FilesystemTestCase
{
    private YamlTranslationExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new YamlTranslationExtractor();
    }

    public function test_supports_yaml_files(): void
    {
        $this->assertTrue($this->extractor->supports('messages.yaml'));
        $this->assertTrue($this->extractor->supports('path/to/messages.yaml'));
    }

    public function test_supports_yml_files(): void
    {
        $this->assertTrue($this->extractor->supports('messages.yml'));
        $this->assertTrue($this->extractor->supports('path/to/messages.yml'));
    }

    public function test_supports_case_insensitive(): void
    {
        $this->assertTrue($this->extractor->supports('messages.YAML'));
        $this->assertTrue($this->extractor->supports('messages.YML'));
    }

    public function test_does_not_support_other_extensions(): void
    {
        $this->assertFalse($this->extractor->supports('messages.xlf'));
        $this->assertFalse($this->extractor->supports('messages.php'));
        $this->assertFalse($this->extractor->supports('messages.json'));
        $this->assertFalse($this->extractor->supports('messages'));
    }

    public function test_extract_nonexistent_file_returns_empty_extraction(): void
    {
        $result = $this->extractor->extract('/nonexistent/file.yaml');

        $this->assertSame([], $result->messages);
        $this->assertSame([], $result->lines);
    }

    public function test_extract_simple_key_value(): void
    {
        $content = "app:\n  hello: 'Hello {name}'\n";
        $tempFile = $this->writeTempFile('messages.yaml', $content);
        $result = $this->extractor->extract($tempFile);

        $this->assertCount(1, $result->messages);
        $this->assertSame('Hello {name}', $result->messages['app.hello']);
        $this->assertArrayHasKey('app.hello', $result->lines);
    }

    public function test_extract_multiple_keys(): void
    {
        $content = "app:\n  hello: 'Hello'\n  goodbye: 'Goodbye'\n";
        $tempFile = $this->writeTempFile('messages.yaml', $content);
        $result = $this->extractor->extract($tempFile);

        $this->assertCount(2, $result->messages);
        $this->assertSame('Hello', $result->messages['app.hello']);
        $this->assertSame('Goodbye', $result->messages['app.goodbye']);
    }

    public function test_extract_with_nested_keys(): void
    {
        $content = "app:\n  user:\n    name: 'Name'\n    email: 'Email'\n";
        $tempFile = $this->writeTempFile('messages.yaml', $content);
        $result = $this->extractor->extract($tempFile);

        $this->assertCount(2, $result->messages);
        $this->assertSame('Name', $result->messages['app.user.name']);
        $this->assertSame('Email', $result->messages['app.user.email']);
    }

    public function test_extract_with_deeply_nested_keys(): void
    {
        $content = "app:\n  level1:\n    level2:\n      level3: 'Deep value'\n";
        $tempFile = $this->writeTempFile('messages.yaml', $content);
        $result = $this->extractor->extract($tempFile);

        $this->assertCount(1, $result->messages);
        $this->assertSame('Deep value', $result->messages['app.level1.level2.level3']);
    }

    public function test_extract_handles_single_quotes(): void
    {
        $content = "app:\n  message: 'Hello {name}'\n";
        $tempFile = $this->writeTempFile('messages.yaml', $content);
        $result = $this->extractor->extract($tempFile);

        $this->assertSame('Hello {name}', $result->messages['app.message']);
    }

    public function test_extract_handles_unquoted_values(): void
    {
        $content = "app:\n  message: Hello World\n";
        $tempFile = $this->writeTempFile('messages.yaml', $content);
        $result = $this->extractor->extract($tempFile);

        $this->assertSame('Hello World', $result->messages['app.message']);
    }

    public function test_extract_preserves_comments_in_quoted_strings(): void
    {
        $content = "app:\n  message: 'Hello # not a comment'\n";
        $tempFile = $this->writeTempFile('messages.yaml', $content);
        $result = $this->extractor->extract($tempFile);

        $this->assertSame('Hello # not a comment', $result->messages['app.message']);
    }

    public function test_extract_handles_hash_comments(): void
    {
        $content = "# This is a comment\napp:\n  message: 'Hello'\n";
        $tempFile = $this->writeTempFile('messages.yaml', $content);
        $result = $this->extractor->extract($tempFile);

        $this->assertCount(1, $result->messages);
        $this->assertSame('Hello', $result->messages['app.message']);
    }

    public function test_extract_ignores_empty_lines(): void
    {
        $content = "\napp:\n  message: 'Hello'\n\n";
        $tempFile = $this->writeTempFile('messages.yaml', $content);
        $result = $this->extractor->extract($tempFile);

        $this->assertCount(1, $result->messages);
        $this->assertSame('Hello', $result->messages['app.message']);
    }

    public function test_extract_handles_multiline_values_with_pipe(): void
    {
        $content = "app:\n  message: |\n    Line 1\n    Line 2\n";
        $tempFile = $this->writeTempFile('messages.yaml', $content);
        $result = $this->extractor->extract($tempFile);

        $this->assertCount(0, $result->messages);
    }

    public function test_extract_handles_multiline_values_with_greater_than(): void
    {
        $content = "app:\n  message: >\n    Folded\n    text\n";
        $tempFile = $this->writeTempFile('messages.yaml', $content);
        $result = $this->extractor->extract($tempFile);

        $this->assertCount(0, $result->messages);
    }

    public function test_extract_handles_empty_key(): void
    {
        $content = "app:\n  :\n    message: 'Hello'\n";
        $tempFile = $this->writeTempFile('messages.yaml', $content);
        $result = $this->extractor->extract($tempFile);

        // Empty keys create parent entries in the stack, nested keys are resolved
        $this->assertCount(1, $result->messages);
        $this->assertSame('Hello', $result->messages['app.message']);
    }

    public function test_extract_handles_empty_value(): void
    {
        $content = "app:\n  message:\n";
        $tempFile = $this->writeTempFile('messages.yaml', $content);
        $result = $this->extractor->extract($tempFile);

        $this->assertCount(0, $result->messages);
    }

    public function test_extract_handles_multiple_nesting_levels(): void
    {
        $content = "app:\n  level1:\n    value1: 'Val1'\n  level2:\n    value2: 'Val2'\n";
        $tempFile = $this->writeTempFile('messages.yaml', $content);
        $result = $this->extractor->extract($tempFile);

        $this->assertCount(2, $result->messages);
        $this->assertSame('Val1', $result->messages['app.level1.value1']);
        $this->assertSame('Val2', $result->messages['app.level2.value2']);
    }

    public function test_extract_tracks_line_numbers(): void
    {
        $content = "app:\n  message: 'Hello'\n  other: 'World'\n";
        $tempFile = $this->writeTempFile('messages.yaml', $content);
        $result = $this->extractor->extract($tempFile);

        $this->assertArrayHasKey('app.message', $result->lines);
        $this->assertArrayHasKey('app.other', $result->lines);
        $this->assertIsInt($result->lines['app.message']);
        $this->assertIsInt($result->lines['app.other']);
    }

    public function test_extract_with_empty_file(): void
    {
        $tempFile = $this->writeTempFile('messages.yaml', '');
        $result = $this->extractor->extract($tempFile);

        $this->assertSame([], $result->messages);
        $this->assertSame([], $result->lines);
    }

    public function test_extract_with_whitespace_only(): void
    {
        $tempFile = $this->writeTempFile('messages.yaml', "   \n   \n");
        $result = $this->extractor->extract($tempFile);

        $this->assertSame([], $result->messages);
        $this->assertSame([], $result->lines);
    }

    public function test_extract_unquotes_single_quoted_values(): void
    {
        $content = "app:\n  message: 'Hello'\n";
        $tempFile = $this->writeTempFile('messages.yaml', $content);
        $result = $this->extractor->extract($tempFile);

        $this->assertSame('Hello', $result->messages['app.message']);
    }

    public function test_extract_preserves_unquoted_values(): void
    {
        $content = "app:\n  message: Hello\n";
        $tempFile = $this->writeTempFile('messages.yaml', $content);
        $result = $this->extractor->extract($tempFile);

        $this->assertSame('Hello', $result->messages['app.message']);
    }

    public function test_extract_handles_special_characters_in_values(): void
    {
        $content = "app:\n  message: 'Hello @#$%'\n";
        $tempFile = $this->writeTempFile('messages.yaml', $content);
        $result = $this->extractor->extract($tempFile);

        $this->assertSame('Hello @#$%', $result->messages['app.message']);
    }

    public function test_extract_handles_colon_in_quoted_values(): void
    {
        $content = "app:\n  message: 'Hello: world'\n";
        $tempFile = $this->writeTempFile('messages.yaml', $content);
        $result = $this->extractor->extract($tempFile);

        $this->assertSame('Hello: world', $result->messages['app.message']);
    }

    private function writeTempFile(string $filename, string $content): string
    {
        $tempDir = $this->createTempDir();
        $filepath = $tempDir.'/'.$filename;
        file_put_contents($filepath, $content);

        return $filepath;
    }
}
