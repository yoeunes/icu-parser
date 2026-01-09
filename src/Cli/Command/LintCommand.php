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

namespace IcuParser\Cli\Command;

use IcuParser\Cli\ConsoleStyle;
use IcuParser\Cli\Input;
use IcuParser\Cli\Output;
use IcuParser\Exception\IcuParserException;
use IcuParser\Parser\Parser;
use IcuParser\Runtime\IcuRuntimeInfo;

final class LintCommand implements CommandInterface
{
    private const EXTENSIONS = ['yml', 'yaml', 'xlf', 'xliff'];

    public function getName(): string
    {
        return 'lint';
    }

    public function getAliases(): array
    {
        return [];
    }

    public function getDescription(): string
    {
        return 'Validate ICU messages in YAML and XLIFF files.';
    }

    public function run(Input $input, Output $output): int
    {
        $path = $input->args[0] ?? getcwd();
        if (false === $path || !file_exists($path)) {
            $output->write($output->error('Error: Path not found.')."\n");

            return 1;
        }

        $style = new ConsoleStyle($output, true);
        $runtime = IcuRuntimeInfo::detect();
        $meta = [
            'Intl' => $output->warning($runtime->intlVersion),
            'ICU' => $output->warning($runtime->icuVersion),
            'Locale' => $output->warning($runtime->locale),
        ];

        if ($input->globalOptions->banner) {
            $style->renderBanner('Lint', $meta, 'ICU MessageFormat linting');
        }

        $files = $this->findFiles($path);
        if ([] === $files) {
            $output->write($output->warning('No YAML or XLIFF files found.')."\n");

            return 0;
        }

        $parser = new Parser();
        $issues = 0;
        $checked = 0;

        foreach ($files as $file) {
            $messages = $this->extractMessages($file);
            foreach ($messages as $message) {
                $checked++;
                try {
                    $parser->parse($message['value']);
                } catch (IcuParserException $exception) {
                    $issues++;
                    $location = $file;
                    if (null !== $message['line']) {
                        $location .= ':'.$message['line'];
                    }
                    $output->write($output->error('Error').': '.$location.' - '.$exception->getMessage()."\n");
                    $snippet = $exception->getSnippet();
                    if (null !== $snippet && '' !== $snippet) {
                        $output->write($snippet."\n");
                    }
                }
            }
        }

        $summary = sprintf(
            'Checked %d message(s) in %d file(s).',
            $checked,
            \count($files),
        );

        if (0 === $issues) {
            $output->write($output->success($summary.' No issues found.')."\n");

            return 0;
        }

        $output->write($output->error($summary.' '.$issues.' issue(s) found.')."\n");

        return 1;
    }

    /**
     * @return array<int, string>
     */
    private function findFiles(string $path): array
    {
        $files = [];

        if (is_file($path)) {
            return $this->isSupportedFile($path) ? [$path] : [];
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $filePath = $file->getPathname();
            if ($this->isSupportedFile($filePath)) {
                $files[] = $filePath;
            }
        }

        sort($files);

        return $files;
    }

    private function isSupportedFile(string $file): bool
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        return in_array($extension, self::EXTENSIONS, true);
    }

    /**
     * @return array<int, array{value: string, line: int|null}>
     */
    private function extractMessages(string $file): array
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $contents = file_get_contents($file);
        if (false === $contents) {
            return [];
        }

        return match ($extension) {
            'yml', 'yaml' => $this->extractYamlMessages($contents),
            'xlf', 'xliff' => $this->extractXliffMessages($contents),
            default => [],
        };
    }

    /**
     * @return array<int, array{value: string, line: int|null}>
     */
    private function extractYamlMessages(string $contents): array
    {
        $messages = [];
        $lines = preg_split('/\R/', $contents) ?: [];

        foreach ($lines as $index => $line) {
            $trimmed = ltrim($line);
            if ('' === $trimmed || str_starts_with($trimmed, '#')) {
                continue;
            }

            if (!str_contains($line, ':')) {
                continue;
            }

            [$key, $value] = explode(':', $line, 2);
            $value = trim($value);
            if ('' === $value || '|' === $value[0] || '>' === $value[0]) {
                continue;
            }

            if (str_contains($value, ' #')) {
                $value = trim(strstr($value, ' #', true) ?: $value);
            }

            if ((str_starts_with($value, '"') && str_ends_with($value, '"'))
                || (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            if ('' !== $value) {
                $messages[] = [
                    'value' => $value,
                    'line' => $index + 1,
                ];
            }
        }

        return $messages;
    }

    /**
     * @return array<int, array{value: string, line: int|null}>
     */
    private function extractXliffMessages(string $contents): array
    {
        $messages = [];

        $dom = new \DOMDocument();
        $loaded = @$dom->loadXML($contents);
        if (false === $loaded) {
            return $messages;
        }

        foreach (['source', 'target'] as $tag) {
            foreach ($dom->getElementsByTagName($tag) as $node) {
                $value = trim($node->textContent);
                if ('' !== $value) {
                    $messages[] = [
                        'value' => $value,
                        'line' => null,
                    ];
                }
            }
        }

        return $messages;
    }
}
