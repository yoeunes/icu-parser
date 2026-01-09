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

namespace IcuParser\Type;

/**
 * Holds inferred parameter types.
 */
final class TypeMap implements \JsonSerializable
{
    /**
     * @var array<string, ParameterType>
     */
    private array $types = [];

    public function add(string $name, ParameterType $type): void
    {
        if (!isset($this->types[$name])) {
            $this->types[$name] = $type;

            return;
        }

        $existing = $this->types[$name];
        if (ParameterType::MIXED === $existing || $existing === $type) {
            return;
        }

        $this->types[$name] = ParameterType::MIXED;
    }

    /**
     * @return array<string, ParameterType>
     */
    public function all(): array
    {
        return $this->types;
    }

    public function get(string $name): ?ParameterType
    {
        return $this->types[$name] ?? null;
    }

    public function jsonSerialize(): array
    {
        $payload = [];
        foreach ($this->types as $name => $type) {
            $payload[$name] = $type->value;
        }

        return $payload;
    }
}
