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

use IcuParser\Validation\NumberPatternValidator;
use PHPUnit\Framework\TestCase;

final class NumberPatternValidatorTest extends TestCase
{
    private NumberPatternValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new NumberPatternValidator();
    }

    public function test_validates_simple_decimal_pattern(): void
    {
        $result = $this->validator->validate('#,##0.##');

        $this->assertFalse($result->hasErrors());
    }

    public function test_validates_currency_pattern(): void
    {
        $result = $this->validator->validate('¤#,##0.00');

        $this->assertFalse($result->hasErrors());
    }

    public function test_validates_percent_pattern(): void
    {
        $result = $this->validator->validate('#%');

        $this->assertFalse($result->hasErrors());
    }

    public function test_validates_scientific_pattern(): void
    {
        $result = $this->validator->validate('0.###E0');

        $this->assertFalse($result->hasErrors());
    }

    public function test_validates_negative_subpattern(): void
    {
        $result = $this->validator->validate('#,##0.00;(#,##0.00)');

        $this->assertFalse($result->hasErrors());
    }

    public function test_rejects_multiple_decimal_points(): void
    {
        $result = $this->validator->validate('0.00.0');

        $this->assertTrue($result->hasErrors());
        $errors = $result->getErrors();
        $this->assertStringContainsString('more than one decimal point', $errors[0]->message);
    }

    public function test_rejects_multiple_exponents(): void
    {
        $result = $this->validator->validate('0.00E0E0');

        $this->assertTrue($result->hasErrors());
        $errors = $result->getErrors();
        $this->assertStringContainsString('more than one exponent', $errors[0]->message);
    }

    public function test_rejects_unterminated_quote(): void
    {
        $result = $this->validator->validate("0'bad");

        $this->assertTrue($result->hasErrors());
        $errors = $result->getErrors();
        $this->assertStringContainsString('Unterminated quoted literal', $errors[0]->message);
    }

    public function test_rejects_pattern_without_digit_placeholder(): void
    {
        $result = $this->validator->validate('foo');

        $this->assertTrue($result->hasErrors());
        $errors = $result->getErrors();
        $this->assertStringContainsString('must contain at least one digit placeholder', $errors[0]->message);
    }

    public function test_rejects_too_many_subpatterns(): void
    {
        $result = $this->validator->validate('0.00;0.00;0.00');

        $this->assertTrue($result->hasErrors());
        $errors = $result->getErrors();
        $this->assertStringContainsString('at most 2 sub-patterns', $errors[0]->message);
    }

    public function test_validates_empty_pattern(): void
    {
        $result = $this->validator->validate('');

        $this->assertFalse($result->hasErrors());
    }
}
