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

namespace IcuParser\Validation;

final class ValidationResult implements \JsonSerializable
{
    /**
     * @var array<int, ValidationError>
     */
    private array $errors = [];

    /**
     * @var array<int, ValidationError>
     */
    private array $warnings = [];

    public function addError(ValidationError $error): void
    {
        $this->errors[] = $error;
    }

    public function addWarning(ValidationError $warning): void
    {
        $this->warnings[] = $warning;
    }

    /**
     * @return array<int, ValidationError>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return array<int, ValidationError>
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function hasErrors(): bool
    {
        return [] !== $this->errors;
    }

    public function hasWarnings(): bool
    {
        return [] !== $this->warnings;
    }

    public function count(): int
    {
        return \count($this->errors);
    }

    public function merge(ValidationResult $other): void
    {
        foreach ($other->errors as $error) {
            $this->errors[] = $error;
        }

        foreach ($other->warnings as $warning) {
            $this->warnings[] = $warning;
        }
    }

    /**
     * @return array<int, array{message: string, position: int|null, snippet: string, code: string|null}>
     */
    public function jsonSerialize(): array
    {
        return array_map(
            static fn (ValidationError $error) => $error->jsonSerialize(),
            $this->errors,
        );
    }
}
