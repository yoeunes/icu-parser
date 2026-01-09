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

use IcuParser\Exception\VisualContextTrait;

final class ValidationError implements \JsonSerializable
{
    use VisualContextTrait;

    public function __construct(
        private readonly string $description,
        public readonly ?int $position,
        ?string $source = null,
        public readonly ?string $errorCode = null,
    ) {
        $this->initializeContext($position, $source);
    }

    public function getMessage(): string
    {
        return $this->description;
    }

    public function getSnippet(): string
    {
        return $this->getVisualSnippet();
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * @return array{message: string, position: int|null, snippet: string, code: string|null}
     */
    public function jsonSerialize(): array
    {
        return [
            'message' => $this->description,
            'position' => $this->position,
            'snippet' => $this->getVisualSnippet(),
            'code' => $this->errorCode,
        ];
    }
}
