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

use IcuParser\Loader\CompositeTranslationExtractor;
use IcuParser\Loader\TranslationExtraction;
use IcuParser\Loader\TranslationExtractorInterface;
use PHPUnit\Framework\TestCase;

final class CompositeTranslationExtractorTest extends TestCase
{
    private CompositeTranslationExtractor $extractor;

    protected function setUp(): void
    {
        $mockExtractor1 = $this->createMockExtractor('.yaml', ['test' => 'value']);
        $mockExtractor2 = $this->createMockExtractor('.xlf', ['test' => 'value']);

        $this->extractor = new CompositeTranslationExtractor([$mockExtractor1, $mockExtractor2]);
    }

    public function test_supports_returns_true_when_any_extractor_supports(): void
    {
        $this->assertTrue($this->extractor->supports('test.yaml'));
        $this->assertTrue($this->extractor->supports('test.xlf'));
    }

    public function test_supports_returns_false_when_no_extractor_supports(): void
    {
        $this->assertFalse($this->extractor->supports('test.json'));
        $this->assertFalse($this->extractor->supports('test.php'));
    }

    public function test_extract_delegates_to_supporting_extractor(): void
    {
        $mockExtractor1 = $this->createMockExtractor('.yaml', ['key1' => 'value1']);
        $mockExtractor2 = $this->createMockExtractor('.xlf', ['key2' => 'value2']);

        $extractor = new CompositeTranslationExtractor([$mockExtractor1, $mockExtractor2]);

        $result = $extractor->extract('test.yaml');
        $this->assertSame(['key1' => 'value1'], $result->messages);

        $result = $extractor->extract('test.xlf');
        $this->assertSame(['key2' => 'value2'], $result->messages);
    }

    public function test_extract_returns_empty_extraction_when_no_extractor_supports(): void
    {
        $mockExtractor = $this->createMockExtractor('.yaml', ['test' => 'value']);
        $extractor = new CompositeTranslationExtractor([$mockExtractor]);

        $result = $extractor->extract('test.json');

        $this->assertSame([], $result->messages);
        $this->assertSame([], $result->lines);
    }

    public function test_supports_checks_extractors_in_order(): void
    {
        $mockExtractor1 = $this->createMockExtractor('.yaml', []);
        $mockExtractor2 = $this->createMockExtractor('.xlf', []);

        $extractor = new CompositeTranslationExtractor([$mockExtractor1, $mockExtractor2]);

        $this->assertTrue($extractor->supports('test.yaml'));
    }

    public function test_can_handle_empty_extractor_list(): void
    {
        $extractor = new CompositeTranslationExtractor([]);

        $this->assertFalse($extractor->supports('test.yaml'));
        $this->assertFalse($extractor->supports('test.xlf'));

        $result = $extractor->extract('test.yaml');
        $this->assertSame([], $result->messages);
        $this->assertSame([], $result->lines);
    }

    public function test_supports_returns_true_on_first_match(): void
    {
        $mockExtractor1 = $this->createMockExtractor('.yaml', []);
        $mockExtractor2 = $this->createMockExtractor('.yaml', []);

        $extractor = new CompositeTranslationExtractor([$mockExtractor1, $mockExtractor2]);

        $this->assertTrue($extractor->supports('test.yaml'));
    }

    /**
     * @param array<string, string> $messages
     */
    private function createMockExtractor(string $extension, array $messages): TranslationExtractorInterface
    {
        $mock = $this->createMock(TranslationExtractorInterface::class);
        $mock->method('supports')
            ->willReturnCallback(fn (string $path): bool => str_ends_with($path, $extension));
        $mock->method('extract')
            ->willReturn(new TranslationExtraction($messages));

        return $mock;
    }
}
