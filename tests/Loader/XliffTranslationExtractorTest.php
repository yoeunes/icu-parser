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

use IcuParser\Loader\XliffTranslationExtractor;
use IcuParser\Tests\Support\FilesystemTestCase;

final class XliffTranslationExtractorTest extends FilesystemTestCase
{
    private XliffTranslationExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new XliffTranslationExtractor();
    }

    public function test_supports_xlf_files(): void
    {
        $this->assertTrue($this->extractor->supports('messages.xlf'));
        $this->assertTrue($this->extractor->supports('path/to/messages.xlf'));
    }

    public function test_supports_xliff_files(): void
    {
        $this->assertTrue($this->extractor->supports('messages.xliff'));
        $this->assertTrue($this->extractor->supports('path/to/messages.xliff'));
    }

    public function test_supports_case_insensitive(): void
    {
        $this->assertTrue($this->extractor->supports('messages.XLF'));
        $this->assertTrue($this->extractor->supports('messages.XLIFF'));
    }

    public function test_does_not_support_other_extensions(): void
    {
        $this->assertFalse($this->extractor->supports('messages.yaml'));
        $this->assertFalse($this->extractor->supports('messages.php'));
        $this->assertFalse($this->extractor->supports('messages.json'));
        $this->assertFalse($this->extractor->supports('messages.xml'));
        $this->assertFalse($this->extractor->supports('messages'));
    }

    public function test_extract_nonexistent_file_returns_empty_extraction(): void
    {
        $result = $this->extractor->extract('/nonexistent/file.xlf');

        $this->assertSame([], $result->messages);
        $this->assertSame([], $result->lines);
    }

    public function test_extract_invalid_xml_returns_empty_extraction(): void
    {
        $tempFile = $this->createTempFile('invalid.xlf', '<?xml version="1.0"?><invalid>');

        $result = $this->extractor->extract($tempFile);

        $this->assertSame([], $result->messages);
        $this->assertSame([], $result->lines);
    }

    public function test_extract_valid_xliff_12_with_trans_units(): void
    {
        $content = '<?xml version="1.0" encoding="utf-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
  <file source-language="en" target-language="en" datatype="plaintext">
    <body>
      <trans-unit id="app.hello">
        <source>Hello World</source>
        <target>Hello {name}</target>
      </trans-unit>
      <trans-unit id="app.count" xml:space="preserve">
        <source>{count, plural, one {# item} other {# items}}</source>
        <target>{count, plural, one {# item} other {# items}}</target>
      </trans-unit>
    </body>
  </file>
</xliff>';

        $tempFile = $this->createTempFile('messages.xlf', $content);
        $result = $this->extractor->extract($tempFile);

        $this->assertCount(2, $result->messages);
        $this->assertSame('Hello {name}', $result->messages['app.hello']);
        $this->assertSame('{count, plural, one {# item} other {# items}}', $result->messages['app.count']);
    }

    public function test_extract_xliff_20_with_units(): void
    {
        $content = '<?xml version="1.0" encoding="utf-8"?>
<xliff version="2.0" xmlns="urn:oasis:names:tc:xliff:document:2.0" srcLang="en">
  <file id="f1">
    <unit id="app.greeting">
      <segment>
        <source>Hello</source>
        <target>Hello {name}</target>
      </segment>
    </unit>
  </file>
</xliff>';

        $tempFile = $this->createTempFile('messages.xliff', $content);
        $result = $this->extractor->extract($tempFile);

        $this->assertCount(1, $result->messages);
        $this->assertSame('Hello {name}', $result->messages['app.greeting']);
    }

    public function test_extract_uses_target_over_source(): void
    {
        $content = '<?xml version="1.0" encoding="utf-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
  <file source-language="en" target-language="en" datatype="plaintext">
    <body>
      <trans-unit id="test.message">
        <source>Source message</source>
        <target>Target message with {param}</target>
      </trans-unit>
    </body>
  </file>
</xliff>';

        $tempFile = $this->createTempFile('messages.xlf', $content);
        $result = $this->extractor->extract($tempFile);

        $this->assertSame('Target message with {param}', $result->messages['test.message']);
    }

    public function test_extract_falls_back_to_source_when_target_missing(): void
    {
        $content = '<?xml version="1.0" encoding="utf-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
  <file source-language="en" target-language="en" datatype="plaintext">
    <body>
      <trans-unit id="test.message">
        <source>Source message with {param}</source>
      </trans-unit>
    </body>
  </file>
</xliff>';

        $tempFile = $this->createTempFile('messages.xlf', $content);
        $result = $this->extractor->extract($tempFile);

        $this->assertSame('Source message with {param}', $result->messages['test.message']);
    }

    public function test_extract_uses_resname_attribute_when_id_missing(): void
    {
        $content = '<?xml version="1.0" encoding="utf-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
  <file source-language="en" target-language="en" datatype="plaintext">
    <body>
      <trans-unit resname="test.resname">
        <source>Message with resname</source>
      </trans-unit>
    </body>
  </file>
</xliff>';

        $tempFile = $this->createTempFile('messages.xlf', $content);
        $result = $this->extractor->extract($tempFile);

        $this->assertSame('Message with resname', $result->messages['test.resname']);
    }

    public function test_extract_prioritizes_id_over_resname(): void
    {
        $content = '<?xml version="1.0" encoding="utf-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
  <file source-language="en" target-language="en" datatype="plaintext">
    <body>
      <trans-unit id="test.id" resname="test.resname">
        <source>Message with both id and resname</source>
      </trans-unit>
    </body>
  </file>
</xliff>';

        $tempFile = $this->createTempFile('messages.xlf', $content);
        $result = $this->extractor->extract($tempFile);

        $this->assertSame('Message with both id and resname', $result->messages['test.id']);
        $this->assertArrayNotHasKey('test.resname', $result->messages);
    }

    public function test_extract_skips_units_without_id_or_resname(): void
    {
        $content = '<?xml version="1.0" encoding="utf-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
  <file source-language="en" target-language="en" datatype="plaintext">
    <body>
      <trans-unit>
        <source>Message without id</source>
      </trans-unit>
      <trans-unit id="valid.message">
        <source>Valid message</source>
      </trans-unit>
    </body>
  </file>
</xliff>';

        $tempFile = $this->createTempFile('messages.xlf', $content);
        $result = $this->extractor->extract($tempFile);

        $this->assertCount(1, $result->messages);
        $this->assertSame('Valid message', $result->messages['valid.message']);
    }

    public function test_extract_skips_empty_messages(): void
    {
        $content = '<?xml version="1.0" encoding="utf-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
  <file source-language="en" target-language="en" datatype="plaintext">
    <body>
      <trans-unit id="empty.message">
        <source></source>
      </trans-unit>
      <trans-unit id="whitespace.message">
        <source>   </source>
      </trans-unit>
      <trans-unit id="valid.message">
        <source>Valid message</source>
      </trans-unit>
    </body>
  </file>
</xliff>';

        $tempFile = $this->createTempFile('messages.xlf', $content);
        $result = $this->extractor->extract($tempFile);

        $this->assertCount(1, $result->messages);
        $this->assertSame('Valid message', $result->messages['valid.message']);
    }

    public function test_extract_handles_line_numbers(): void
    {
        $content = '<?xml version="1.0" encoding="utf-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
  <file source-language="en" target-language="en" datatype="plaintext">
    <body>
      <trans-unit id="test.message">
        <source>Test message</source>
      </trans-unit>
    </body>
  </file>
</xliff>';

        $tempFile = $this->createTempFile('messages.xlf', $content);
        $result = $this->extractor->extract($tempFile);

        $this->assertArrayHasKey('test.message', $result->lines);
        $this->assertIsInt($result->lines['test.message']);
        $this->assertGreaterThan(0, $result->lines['test.message']);
    }

    public function test_extract_handles_mixed_trans_units_and_units(): void
    {
        $content = '<?xml version="1.0" encoding="utf-8"?>
<xliff version="2.0" xmlns="urn:oasis:names:tc:xliff:document:2.0" srcLang="en">
  <file id="f1">
    <unit id="unit.message">
      <segment>
        <source>Unit message</source>
        <target>Unit {param}</target>
      </segment>
    </unit>
  </file>
</xliff>';

        $tempFile = $this->createTempFile('messages.xliff', $content);
        $result = $this->extractor->extract($tempFile);

        $this->assertCount(1, $result->messages);
        $this->assertSame('Unit {param}', $result->messages['unit.message']);
    }

    public function test_extract_with_namespaced_xliff(): void
    {
        $content = '<?xml version="1.0" encoding="utf-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
  <file source-language="en" target-language="en" datatype="plaintext">
    <body>
      <trans-unit id="namespaced.message">
        <source>Namespaced {test}</source>
      </trans-unit>
    </body>
  </file>
</xliff>';

        $tempFile = $this->createTempFile('messages.xlf', $content);
        $result = $this->extractor->extract($tempFile);

        $this->assertSame('Namespaced {test}', $result->messages['namespaced.message']);
    }

    private function createTempFile(string $filename, string $content): string
    {
        $tempDir = $this->createTempDir();
        $filepath = $tempDir.'/'.$filename;
        file_put_contents($filepath, $content);

        return $filepath;
    }
}
