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
}
