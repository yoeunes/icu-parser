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

namespace IcuParser\Tests\Support;

use PHPUnit\Framework\TestCase;

abstract class FilesystemTestCase extends TestCase
{
    private ?string $tempDir = null;

    protected function tearDown(): void
    {
        $this->removeTempDir();
    }

    protected function createTempDir(): string
    {
        if (null !== $this->tempDir) {
            return $this->tempDir;
        }

        $baseDir = dirname(__DIR__).'/Runtime';
        if (!is_dir($baseDir) && !mkdir($baseDir, 0o775, true) && !is_dir($baseDir)) {
            throw new \RuntimeException(sprintf('Unable to create base temp directory at "%s".', $baseDir));
        }

        $this->tempDir = $baseDir.'/icu-parser-'.uniqid('', true);
        if (!mkdir($this->tempDir, 0o775, true) && !is_dir($this->tempDir)) {
            throw new \RuntimeException(sprintf('Unable to create temp directory at "%s".', $this->tempDir));
        }

        return $this->tempDir;
    }

    protected function writeFile(string $relativePath, string $contents): string
    {
        $path = $this->createTempDir().'/'.$relativePath;
        $directory = \dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0o775, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create directory "%s".', $directory));
        }

        if (false === file_put_contents($path, $contents)) {
            throw new \RuntimeException(sprintf('Unable to write file "%s".', $path));
        }

        return $path;
    }

    private function removeTempDir(): void
    {
        if (null === $this->tempDir || !is_dir($this->tempDir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->tempDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            if (!$item instanceof \SplFileInfo) {
                continue;
            }

            if ($item->isDir()) {
                @rmdir($item->getPathname());

                continue;
            }

            @unlink($item->getPathname());
        }

        @rmdir($this->tempDir);
        $this->tempDir = null;
    }
}
