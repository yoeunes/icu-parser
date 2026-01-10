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

use IcuParser\Validation\DateTimePatternValidator;
use PHPUnit\Framework\TestCase;

final class DateTimePatternValidatorTest extends TestCase
{
    private DateTimePatternValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new DateTimePatternValidator();
    }

    public function test_validates_simple_date_pattern(): void
    {
        $result = $this->validator->validate('yyyy-MM-dd');

        $this->assertFalse($result->hasErrors());
    }

    public function test_validates_full_date_pattern(): void
    {
        $result = $this->validator->validate('EEEE, MMMM d, y');

        $this->assertFalse($result->hasErrors());
    }

    public function test_validates_time_pattern(): void
    {
        $result = $this->validator->validate('HH:mm:ss');

        $this->assertFalse($result->hasErrors());
    }

    public function test_validates_datetime_pattern(): void
    {
        $result = $this->validator->validate("yyyy-MM-dd'T'HH:mm:ss");

        $this->assertFalse($result->hasErrors());
    }

    public function test_warns_about_day_year_month_conflict(): void
    {
        $result = $this->validator->validate('DDD yyyy MM');

        $this->assertFalse($result->hasErrors());
        $this->assertTrue($result->hasWarnings());
    }

    public function test_warns_about_week_year_calendar_conflict(): void
    {
        $result = $this->validator->validate('YYYY-MM-dd');

        $this->assertFalse($result->hasErrors());
        $this->assertTrue($result->hasWarnings());
    }

    public function test_validates_quoted_literals(): void
    {
        $result = $this->validator->validate("'Date:' yyyy-MM-dd");

        $this->assertFalse($result->hasErrors());
    }

    public function test_validates_empty_pattern(): void
    {
        $result = $this->validator->validate('');

        $this->assertFalse($result->hasErrors());
    }

    public function test_validates_short_time(): void
    {
        $result = $this->validator->validate('h:mm a');

        $this->assertFalse($result->hasErrors());
    }
}
