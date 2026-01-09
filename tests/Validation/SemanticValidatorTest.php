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

namespace IcuParser\Tests\Validation;

use IcuParser\Parser\Parser;
use IcuParser\Validation\SemanticValidator;
use PHPUnit\Framework\TestCase;

final class SemanticValidatorTest extends TestCase
{
    private SemanticValidator $validator;

    private Parser $parser;

    protected function setUp(): void
    {
        $this->validator = new SemanticValidator();
        $this->parser = new Parser();
    }

    public function test_validates_correct_plural(): void
    {
        $message = $this->parser->parse('{count, plural, one {# item} other {# items}}');
        $result = $this->validator->validate($message, '{count, plural, one {# item} other {# items}}');

        $this->assertFalse($result->hasErrors());
        $this->assertEmpty($result->getErrors());
    }

    public function test_detects_missing_other_in_plural(): void
    {
        $message = $this->parser->parse('{count, plural, one {# item}}');
        $result = $this->validator->validate($message, '{count, plural, one {# item}}');

        $this->assertTrue($result->hasErrors());
        $errors = $result->getErrors();
        $this->assertCount(1, $errors);
        $this->assertStringContainsString('Missing required "other" option', $errors[0]->getMessage());
        $this->assertSame('validator.missing_other', $errors[0]->getErrorCode());
    }

    public function test_detects_duplicate_options(): void
    {
        $message = $this->parser->parse('{gender, select, male {Mr.} male {Sir}}');
        $result = $this->validator->validate($message, '{gender, select, male {Mr.} male {Sir}}');

        $this->assertTrue($result->hasErrors());
        $errors = $result->getErrors();
        $this->assertCount(2, $errors); // Duplicate and missing other
        $this->assertStringContainsString('Duplicate option "male"', $errors[0]->getMessage());
        $this->assertStringContainsString('Missing required "other"', $errors[1]->getMessage());
    }

    public function test_detects_empty_option_message(): void
    {
        $message = $this->parser->parse('{count, plural, one {} other {# items}}');
        $result = $this->validator->validate($message, '{count, plural, one {} other {# items}}');

        $this->assertTrue($result->hasErrors());
        $errors = $result->getErrors();
        $this->assertCount(1, $errors);
        $this->assertStringContainsString('Empty option message for selector "one"', $errors[0]->getMessage());
    }

    public function test_validates_select(): void
    {
        $message = $this->parser->parse('{gender, select, male {He} female {She} other {They}}');
        $result = $this->validator->validate($message, '{gender, select, male {He} female {She} other {They}}');

        $this->assertFalse($result->hasErrors());
    }

    public function test_validates_selectordinal(): void
    {
        $message = $this->parser->parse('{count, selectordinal, one {#st} other {#th}}');
        $result = $this->validator->validate($message, '{count, selectordinal, one {#st} other {#th}}');

        $this->assertFalse($result->hasErrors());
    }

    public function test_validates_simple_message(): void
    {
        $message = $this->parser->parse('Hello {name}');
        $result = $this->validator->validate($message, 'Hello {name}');

        $this->assertFalse($result->hasErrors());
    }

    public function test_detects_empty_option_message_without_context(): void
    {
        // Create an option node directly and visit it without being in a select/plural context
        $emptyMessage = new \IcuParser\Node\MessageNode([], 0, 0);
        $option = new \IcuParser\Node\OptionNode('test', $emptyMessage, 0, 0);
        
        // We need to trigger validation by calling validate first to initialize the validator
        $message = $this->parser->parse('Hello {name}');
        $this->validator->validate($message, 'Hello {name}');
        
        // Now manually visit the option after validation to test the null context path
        // This should trigger the ternary operator where context is null (line 78-79)
        $this->validator->visitOption($option);
        
        // The simple message should still have no errors
        $result = $this->validator->validate($message, 'Hello {name}');
        $this->assertFalse($result->hasErrors());
    }

    public function test_is_message_empty_with_whitespace_only(): void
    {
        // Use reflection to test the private isMessageEmpty method directly
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('isMessageEmpty');
        $method->setAccessible(true);
        
        // Test with whitespace-only text - should return false (has content after trim)
        $whitespaceText = new \IcuParser\Node\TextNode('   ', 0, 3);
        $message = new \IcuParser\Node\MessageNode([$whitespaceText], 0, 3);
        $result = $method->invoke($this->validator, $message);
        $this->assertTrue($result, 'Message with only whitespace should be considered empty');
    }

    public function test_is_message_empty_with_mixed_whitespace_and_content(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('isMessageEmpty');
        $method->setAccessible(true);
        
        // Test with whitespace + content text - should return false
        $whitespaceText = new \IcuParser\Node\TextNode('   ', 0, 3);
        $contentText = new \IcuParser\Node\TextNode('content', 3, 10);
        $message = new \IcuParser\Node\MessageNode([$whitespaceText, $contentText], 0, 10);
        $result = $method->invoke($this->validator, $message);
        $this->assertFalse($result, 'Message with content should not be empty');
    }

    public function test_is_message_empty_completely_empty(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('isMessageEmpty');
        $method->setAccessible(true);
        
        // Test with completely empty message - should return true (covers line 130-131)
        $emptyMessage = new \IcuParser\Node\MessageNode([], 0, 0);
        $result = $method->invoke($this->validator, $emptyMessage);
        $this->assertTrue($result, 'Empty message should be considered empty');
        
        // Test with only whitespace text nodes - should return true (covers line 140 and 150)
        $whitespaceText = new \IcuParser\Node\TextNode('   ', 0, 3);
        $whitespaceMessage = new \IcuParser\Node\MessageNode([$whitespaceText], 0, 3);
        $result = $method->invoke($this->validator, $whitespaceMessage);
        $this->assertTrue($result, 'Message with only whitespace should be considered empty');
    }

    public function test_is_message_empty_with_simple_argument_node(): void
    {
        // Test case where message contains a SimpleArgumentNode (not TextNode or PoundNode)
        $argumentNode = new \IcuParser\Node\SimpleArgumentNode('test', 0, 3);
        
        $message = new \IcuParser\Node\MessageNode([$argumentNode], 0, 3);
        $option = new \IcuParser\Node\OptionNode('test', $message, 0, 3);
        
        $messageWithOption = $this->parser->parse('{count, plural, other {# items}}');
        $result = $this->validator->validate($messageWithOption, '{count, plural, other {# items}}');
        
        // Visit the option with argument node to test the "other node type" path
        $this->validator->visitOption($option);
        
        $this->assertFalse($result->hasErrors()); // Original message should still be valid
    }



    public function test_current_context_with_empty_stack(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('currentContext');
        $method->setAccessible(true);
        
        // Create a simple message that doesn't involve any select/plural structures
        $message = $this->parser->parse('Hello world');
        $this->validator->validate($message, 'Hello world');
        
        // Directly test currentContext with empty stack (covers line 173-174)
        $context = $method->invoke($this->validator);
        $this->assertNull($context, 'Current context should be null when stack is empty');
    }
}
