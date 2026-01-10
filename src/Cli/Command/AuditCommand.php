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

use IcuParser\Audit\AuditIssue;
use IcuParser\Bridge\Php\PhpTranslationExtractor;
use IcuParser\Bridge\Twig\TwigTranslationExtractor;
use IcuParser\Cli\ConsoleStyle;
use IcuParser\Cli\Input;
use IcuParser\Cli\Output;
use IcuParser\Exception\IcuParserException;
use IcuParser\Loader\TranslationEntry;
use IcuParser\Loader\TranslationLoader;
use IcuParser\Parser\Parser;
use IcuParser\Runtime\IcuRuntimeInfo;
use IcuParser\Type\ParameterType;
use IcuParser\Type\TypeInferer;
use IcuParser\Usage\TranslationUsage;
use IcuParser\Validation\SemanticValidator;
use IcuParser\Validator\CrossLocaleValidator;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\TwigFilter;

final class AuditCommand implements CommandInterface
{
    private const DEFAULT_DOMAIN = 'messages';

    public function getName(): string
    {
        return 'audit';
    }

    public function getAliases(): array
    {
        return ['icu:audit'];
    }

    public function getDescription(): string
    {
        return 'Audit ICU messages, usage, and locale consistency.';
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

        if ($input->globalOptions->visuals) {
            $style->renderBanner('Audit', $meta, 'Translation health report');
        }

        $defaultLocale = $this->normalizeLocale($runtime->locale);
        $loader = new TranslationLoader([$path], $defaultLocale);
        $entries = [];
        foreach ($loader->scan() as $entry) {
            $entries[] = $entry;
        }

        if ([] === $entries) {
            $output->write($output->warning('No translation messages found.')."\n");
        }

        $issues = [];
        $entriesByDomain = [];
        $typesByDomain = [];
        $parser = new Parser();
        $validator = new SemanticValidator();
        $inferer = new TypeInferer();

        foreach ($entries as $entry) {
            $entriesByDomain[$entry->domain][$entry->id][$entry->locale] = $entry;

            try {
                $ast = $parser->parse($entry->message);
            } catch (IcuParserException $exception) {
                $issues[] = new AuditIssue(
                    'Syntax',
                    sprintf(
                        'ICU syntax error in "%s" (%s): %s',
                        $entry->id,
                        $entry->locale,
                        $exception->getMessage(),
                    ),
                    $entry->file,
                    $entry->line,
                    $exception->getSnippet(),
                    'audit.syntax_error',
                );

                continue;
            }

            $validation = $validator->validate($ast, $entry->message);
            foreach ($validation->getErrors() as $error) {
                $issues[] = new AuditIssue(
                    'Semantic',
                    sprintf(
                        'Semantic error in "%s" (%s): %s',
                        $entry->id,
                        $entry->locale,
                        $error->getMessage(),
                    ),
                    $entry->file,
                    $entry->line,
                    $error->getSnippet(),
                    $error->getErrorCode(),
                );
            }

            $typesByDomain[$entry->domain][$entry->id][$entry->locale] = $inferer->infer($ast)->all();
        }

        $referenceLocale = $this->resolveReferenceLocale($entries, $runtime->locale);
        $crossLocaleIssues = (new CrossLocaleValidator())->validate(
            $entriesByDomain,
            $typesByDomain,
            $referenceLocale,
        );

        foreach ($crossLocaleIssues as $issue) {
            $issues[] = new AuditIssue(
                'Consistency',
                sprintf('"%s" (%s): %s', $issue->id, $issue->locale, $issue->message),
                $issue->file,
                $issue->line,
                null,
                $issue->code,
            );
        }

        $usageIssues = $this->validateUsages(
            $this->collectUsages($path, $issues),
            $entriesByDomain,
            $typesByDomain,
            $referenceLocale,
        );

        $issues = array_merge($issues, $usageIssues);

        $this->renderSummary($style, $output, $entries, $issues, $referenceLocale);
        $this->renderIssues($output, $issues);

        return [] === $issues ? 0 : 1;
    }

    /**
     * @param list<TranslationEntry> $entries
     */
    private function resolveReferenceLocale(array $entries, string $defaultLocale): ?string
    {
        if ('' === $defaultLocale || 'unknown' === $defaultLocale) {
            $defaultLocale = null;
        }

        $locales = [];
        foreach ($entries as $entry) {
            $locales[$entry->locale] = true;
        }

        if ([] === $locales) {
            return null;
        }

        if (null !== $defaultLocale) {
            if (isset($locales[$defaultLocale])) {
                return $defaultLocale;
            }

            $short = strtok($defaultLocale, '_') ?: $defaultLocale;
            if (isset($locales[$short])) {
                return $short;
            }
        }

        return array_key_first($locales);
    }

    private function normalizeLocale(string $locale): string
    {
        $locale = trim($locale);

        if ('' === $locale || 'unknown' === $locale) {
            return 'und';
        }

        return $locale;
    }

    /**
     * @param list<AuditIssue> $issues
     *
     * @return list<TranslationUsage>
     */
    private function collectUsages(string $path, array &$issues): array
    {
        $usages = [];

        $phpExtractor = new PhpTranslationExtractor();
        foreach ($this->collectFiles($path, ['php']) as $file) {
            $usages = array_merge($usages, $phpExtractor->extractFromFile($file));
        }

        $twig = $this->createTwigEnvironment();
        if (null === $twig) {
            return $usages;
        }

        $twigExtractor = new TwigTranslationExtractor($twig);
        foreach ($this->collectFiles($path, ['twig']) as $file) {
            try {
                $usages = array_merge($usages, $twigExtractor->extractFromFile($file));
            } catch (\Throwable $exception) {
                $line = $this->resolveTwigErrorLine($exception);
                $issues[] = new AuditIssue(
                    'Twig',
                    sprintf('Twig parse error: %s', $exception->getMessage()),
                    $file,
                    $line,
                    null,
                    'audit.twig_error',
                );
            }
        }

        return $usages;
    }

    /**
     * @param list<TranslationUsage>                                                    $usages
     * @param array<string, array<string, array<string, TranslationEntry>>>             $entriesByDomain
     * @param array<string, array<string, array<string, array<string, ParameterType>>>> $typesByDomain
     *
     * @return list<AuditIssue>
     */
    private function validateUsages(
        array $usages,
        array $entriesByDomain,
        array $typesByDomain,
        ?string $referenceLocale,
    ): array {
        $issues = [];

        foreach ($usages as $usage) {
            $domain = $usage->domain ?? self::DEFAULT_DOMAIN;
            $entry = $this->resolveEntry($entriesByDomain, $domain, $usage->id, $referenceLocale);

            if (null === $entry) {
                $issues[] = new AuditIssue(
                    'Usage',
                    sprintf('Translation key "%s" not found (domain "%s").', $usage->id, $domain),
                    $usage->file,
                    $usage->line,
                    null,
                    'audit.usage.missing_translation',
                );

                continue;
            }

            $locale = $entry->locale;
            $expected = $typesByDomain[$domain][$usage->id][$locale] ?? [];
            foreach ($expected as $name => $expectedType) {
                if (!isset($usage->parameters[$name])) {
                    $issues[] = new AuditIssue(
                        'Usage',
                        sprintf('Missing parameter "%s" for "%s" (%s).', $name, $usage->id, $domain),
                        $usage->file,
                        $usage->line,
                        null,
                        'audit.usage.missing_parameter',
                    );

                    continue;
                }

                $actualType = $usage->parameters[$name];
                if ($this->isTypeMismatch($expectedType, $actualType)) {
                    $issues[] = new AuditIssue(
                        'Usage',
                        sprintf(
                            'Parameter "%s" expects "%s" but "%s" was passed.',
                            $name,
                            $expectedType->value,
                            $actualType->value,
                        ),
                        $usage->file,
                        $usage->line,
                        null,
                        'audit.usage.type_mismatch',
                    );
                }
            }
        }

        return $issues;
    }

    /**
     * @param list<TranslationEntry> $entries
     * @param list<AuditIssue>       $issues
     */
    private function renderSummary(ConsoleStyle $style, Output $output, array $entries, array $issues, ?string $referenceLocale): void
    {
        $locales = [];
        foreach ($entries as $entry) {
            $locales[$entry->locale] = true;
        }

        $style->renderSection('Summary');

        $rows = [
            'Messages' => $output->info((string) \count($entries)),
            'Locales' => $output->info(implode(', ', array_keys($locales))),
            'Issues' => [] === $issues ? $output->success('0') : $output->error((string) \count($issues)),
        ];

        if (null !== $referenceLocale) {
            $rows['Reference'] = $output->warning($referenceLocale);
        }

        $style->renderKeyValueBlock($rows, 2);
        $output->write("\n");
    }

    /**
     * @param list<AuditIssue> $issues
     */
    private function renderIssues(Output $output, array $issues): void
    {
        if ([] === $issues) {
            $output->write($output->success('No issues found.')."\n");

            return;
        }

        foreach ($issues as $issue) {
            $location = $issue->file;
            if (null !== $issue->line) {
                $location .= ':'.$issue->line;
            }

            $output->write(sprintf(
                "%s %s\n",
                $output->error('['.$issue->category.']'),
                $issue->message,
            ));
            $output->write('  '.$output->dim($location)."\n");

            $this->renderFileSnippet($output, $issue->file, $issue->line);

            if (null !== $issue->snippet && '' !== $issue->snippet) {
                $output->write($issue->snippet."\n");
            }

            $output->write("\n");
        }
    }

    private function renderFileSnippet(Output $output, string $file, ?int $line): void
    {
        if (null === $line || $line < 1) {
            return;
        }

        $lines = @file($file, \FILE_IGNORE_NEW_LINES);
        if (false === $lines || [] === $lines) {
            return;
        }

        $index = $line - 1;
        $start = max(0, $index - 1);
        $end = min(\count($lines) - 1, $index + 1);

        for ($i = $start; $i <= $end; $i++) {
            $number = str_pad((string) ($i + 1), 4, ' ', \STR_PAD_LEFT);
            $prefix = $i === $index ? $output->error('>') : ' ';
            $text = $lines[$i];
            $colored = $i === $index ? $output->bold($text) : $output->dim($text);

            $output->write(sprintf("  %s %s %s\n", $prefix, $output->dim($number), $colored));
        }
    }

    /**
     * @return list<string>
     */
    /**
     * @param list<string> $extensions
     *
     * @return list<string>
     */
    private function collectFiles(string $path, array $extensions): array
    {
        $normalizedExtensions = [];
        foreach ($extensions as $extension) {
            $normalizedExtensions[] = strtolower($extension);
        }
        $files = [];

        if (is_file($path)) {
            $ext = strtolower(pathinfo($path, \PATHINFO_EXTENSION));
            if (\in_array($ext, $normalizedExtensions, true)) {
                return [$path];
            }

            return [];
        }

        if (!is_dir($path)) {
            return [];
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile()) {
                continue;
            }

            $filePath = $file->getPathname();
            if ($this->isExcludedPath($filePath)) {
                continue;
            }

            $ext = strtolower($file->getExtension());
            if (\in_array($ext, $normalizedExtensions, true)) {
                $files[] = $filePath;
            }
        }

        return $files;
    }

    private function isExcludedPath(string $path): bool
    {
        foreach (['/vendor/', '/var/', '/node_modules/', '/.git/', '/.cache/', '/tools/', '/tests/Fixtures/'] as $needle) {
            if (str_contains($path, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function createTwigEnvironment(): ?Environment
    {
        if (!class_exists(Environment::class)) {
            return null;
        }

        $twig = new Environment(new ArrayLoader(), [
            'cache' => false,
            'autoescape' => false,
            'strict_variables' => false,
        ]);

        $twig->addFilter(new TwigFilter('trans', static fn (string $id): string => $id));

        $this->registerTranslationExtension($twig);

        return $twig;
    }

    private function registerTranslationExtension(Environment $twig): void
    {
        if (class_exists(TranslationExtension::class)
            && interface_exists(TranslatorInterface::class)
        ) {
            $translator = new class implements TranslatorInterface {
                /**
                 * @param array<string, mixed> $parameters
                 */
                public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
                {
                    return $id;
                }

                public function getLocale(): string
                {
                    return '';
                }

                public function setLocale(string $locale): void {}
            };

            $twig->addExtension(new TranslationExtension($translator));
        }

    }

    private function resolveTwigErrorLine(\Throwable $exception): ?int
    {
        if (\is_a($exception, 'Twig\\Error\\Error')) {
            $line = $exception->getTemplateLine();

            return $line > 0 ? $line : null;
        }

        return null;
    }

    /**
     * @param array<string, array<string, array<string, TranslationEntry>>> $entriesByDomain
     */
    private function resolveEntry(
        array $entriesByDomain,
        string $domain,
        string $id,
        ?string $referenceLocale,
    ): ?TranslationEntry {
        $entriesByLocale = $entriesByDomain[$domain][$id] ?? null;
        if (null === $entriesByLocale || [] === $entriesByLocale) {
            return null;
        }

        if (null !== $referenceLocale && isset($entriesByLocale[$referenceLocale])) {
            return $entriesByLocale[$referenceLocale];
        }

        return $entriesByLocale[array_key_first($entriesByLocale)] ?? null;
    }

    private function isTypeMismatch(ParameterType $expected, ParameterType $actual): bool
    {
        if (ParameterType::MIXED === $expected || ParameterType::MIXED === $actual) {
            return false;
        }

        return $expected !== $actual;
    }
}
