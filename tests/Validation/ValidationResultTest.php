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

use IcuParser\Validation\ValidationError;
use IcuParser\Validation\ValidationResult;
use PHPUnit\Framework\TestCase;

final class ValidationResultTest extends TestCase
{
    public function test_add_and_get_errors(): void
    {
        $result = new ValidationResult();
        $error = new ValidationError('Test error', 10, 'source', 'code');

        $result->addError($error);

        $this->assertSame([$error], $result->getErrors());
        $this->assertTrue($result->hasErrors());
    }

    public function test_count(): void
    {
        $result = new ValidationResult();
        $result->addError(new ValidationError('Error 1', null));
        $result->addError(new ValidationError('Error 2', null));

        $this->assertSame(2, $result->count());
    }

    public function test_merge(): void
    {
        $result1 = new ValidationResult();
        $result1->addError(new ValidationError('Error 1', null));

        $result2 = new ValidationResult();
        $result2->addError(new ValidationError('Error 2', null));

        $result1->merge($result2);

        $this->assertSame(2, $result1->count());
    }

    public function test_json_serialize(): void
    {
        $result = new ValidationResult();
        $error = new ValidationError('Test error', 10, 'source', 'code');
        $result->addError($error);

        $json = $result->jsonSerialize();

        $this->assertCount(1, $json);
        $this->assertSame([
            'message' => 'Test error',
            'position' => 10,
            'snippet' => $error->getSnippet(),
            'code' => 'code',
        ], $json[0]);
    }
}
