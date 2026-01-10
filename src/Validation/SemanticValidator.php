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

use IcuParser\Cldr\PluralRules;
use IcuParser\Node\ChoiceNode;
use IcuParser\Node\DurationNode;
use IcuParser\Node\FormattedArgumentNode;
use IcuParser\Node\MessageNode;
use IcuParser\Node\OptionNode;
use IcuParser\Node\OrdinalNode;
use IcuParser\Node\PluralNode;
use IcuParser\Node\PoundNode;
use IcuParser\Node\SelectNode;
use IcuParser\Node\SelectOrdinalNode;
use IcuParser\Node\SimpleArgumentNode;
use IcuParser\Node\SpelloutNode;
use IcuParser\Node\TextNode;
use IcuParser\NodeVisitor\AbstractNodeVisitor;

/**
 * Semantic validator for ICU MessageFormat AST.
 *
 * Validates:
 * - Duplicate and missing options
 * - Empty option messages
 * - Locale-specific plural/ordinal keywords
 * - Named styles
 * - Pattern validation
 */
final class SemanticValidator extends AbstractNodeVisitor
{
    private ValidationResult $result;

    private string $source = '';

    private string $locale = 'en';

    /**
     * @var array<int, array{type: string, name: string}>
     */
    private array $contextStack = [];

    /**
     * @var array<string, bool>
     */
    private array $arguments = [];

    /**
     * @var array<string, bool>
     */
    private array $usedArguments = [];

    public function __construct(
        private readonly NumberPatternValidator $numberValidator = new NumberPatternValidator(),
        private readonly DateTimePatternValidator $dateTimeValidator = new DateTimePatternValidator(),
    ) {}

    public function validate(MessageNode $message, string $source, string $locale = 'en'): ValidationResult
    {
        $this->result = new ValidationResult();
        $this->source = $source;
        $this->locale = $locale;
        $this->contextStack = [];
        $this->arguments = [];
        $this->usedArguments = [];

        $message->accept($this);

        // Validate argument usage
        $this->validateArgumentUsage();

        return $this->result;
    }

    public function visitSelect(SelectNode $node): void
    {
        $this->registerArgument($node->name);
        $this->pushContext('select', $node->name);
        $this->validateOptions($node->options, 'select', $node->name, $node->startPosition);
        parent::visitSelect($node);
        $this->popContext();
    }

    public function visitPlural(PluralNode $node): void
    {
        $this->registerArgument($node->name);
        $this->pushContext('plural', $node->name);
        $this->validateOptions($node->options, 'plural', $node->name, $node->startPosition);
        parent::visitPlural($node);
        $this->popContext();
    }

    public function visitSelectOrdinal(SelectOrdinalNode $node): void
    {
        $this->registerArgument($node->name);
        $this->pushContext('selectordinal', $node->name);
        $this->validateOptions($node->options, 'selectordinal', $node->name, $node->startPosition);
        parent::visitSelectOrdinal($node);
        $this->popContext();
    }

    public function visitChoice(ChoiceNode $node): void
    {
        $this->registerArgument($node->name);
        $this->pushContext('choice', $node->name);
        $this->validateChoiceOptions($node);
        parent::visitChoice($node);
        $this->popContext();
    }

    public function visitSimpleArgument(SimpleArgumentNode $node): void
    {
        $this->registerArgument($node->name);
    }

    public function visitFormattedArgument(FormattedArgumentNode $node): void
    {
        $this->registerArgument($node->name);

        // Validate named styles
        if (null !== $node->style) {
            $this->validateStyle($node);
        }
    }

    public function visitSpellout(SpelloutNode $node): void
    {
        $this->visitFormattedArgument($node);
    }

    public function visitOrdinal(OrdinalNode $node): void
    {
        $this->visitFormattedArgument($node);
    }

    public function visitDuration(DurationNode $node): void
    {
        $this->visitFormattedArgument($node);
    }

    public function visitOption(OptionNode $node): void
    {
        if ($this->isMessageEmpty($node->message)) {
            $context = $this->currentContext();
            $suffix = null === $context
                ? ''
                : sprintf(' in %s argument "%s"', $context['type'], $context['name']);

            $this->addError(
                sprintf('Empty option message for selector "%s"%s.', $node->selector, $suffix),
                $node->startPosition,
                'validator.empty_option',
            );
        }

        parent::visitOption($node);
    }

    /**
     * @param array<int, OptionNode> $options
     */
    private function validateOptions(array $options, string $type, string $name, int $position): void
    {
        $seen = [];
        $hasOther = false;

        foreach ($options as $option) {
            $key = $option->selector;
            if ('other' === $key) {
                $hasOther = true;
            }

            if (isset($seen[$key])) {
                $this->addError(
                    sprintf('Duplicate option "%s" in %s argument "%s".', $key, $type, $name),
                    $option->startPosition,
                    'validator.duplicate_option',
                );

                continue;
            }

            $seen[$key] = true;

            // Validate explicit selectors are numeric for plural/selectordinal
            if ($option->explicit && !in_array($type, ['plural', 'selectordinal'], true)) {
                $this->addError(
                    sprintf('Explicit selectors (=) are only valid for plural and selectordinal arguments, not for "%s".', $type),
                    $option->startPosition,
                    'validator.invalid_explicit_selector',
                );
            }

            // Validate explicit selectors are numeric
            if ($option->explicit && in_array($type, ['plural', 'selectordinal'], true) && null === $option->explicitValue) {
                $this->addError(
                    sprintf('Explicit selectors must be numeric, got "%s".', $option->selector),
                    $option->startPosition,
                    'validator.non_numeric_explicit_selector',
                );
            }

            // Validate keywords for locale
            if (!$option->explicit) {
                $isOrdinal = 'selectordinal' === $type;
                $validKeywords = PluralRules::getCategories($this->locale, $isOrdinal);

                if (!in_array($key, $validKeywords, true)) {
                    $this->addWarning(
                        sprintf('Keyword "%s" is not valid for locale "%s" in %s format. Valid keywords: %s.',
                            $key, $this->locale, $type, implode(', ', $validKeywords)),
                        $option->startPosition,
                        'validator.invalid_keyword_for_locale',
                    );
                }
            }
        }

        if (!$hasOther) {
            $this->addError(
                sprintf('Missing required "other" option in %s argument "%s".', $type, $name),
                $position,
                'validator.missing_other',
            );
        }
    }

    private function validateStyle(FormattedArgumentNode $node): void
    {
        if (null === $node->style) {
            return;
        }

        $style = $node->style;
        $isNamedStyle = $this->isKnownNamedStyle($node);

        // Validate patterns based on format type
        switch ($node->format) {
            case 'number':
                if (!$isNamedStyle && '' !== $style) {
                    $patternResult = $this->numberValidator->validate($style);
                    $this->result->merge($patternResult);
                }

                break;

            case 'date':
            case 'time':
                if (!$isNamedStyle && '' !== $style) {
                    $patternResult = $this->dateTimeValidator->validate($style);
                    $this->result->merge($patternResult);
                }

                break;

            case 'spellout':
            case 'ordinal':
            case 'duration':
                // These can use custom rules, no strict validation
                break;
        }
    }

    private function isKnownNamedStyle(FormattedArgumentNode $node): bool
    {
        if (null === $node->style) {
            return false;
        }

        $style = strtolower($node->style);

        // Validate against known named styles
        $validStyles = match ($node->format) {
            'number' => ['decimal', 'currency', 'percent', 'scientific', 'compact-decimal', 'compact-currency', 'long', 'short'],
            'date' => ['full', 'long', 'medium', 'short'],
            'time' => ['full', 'long', 'medium', 'short'],
            default => [],
        };

        return [] !== $validStyles && in_array($style, $validStyles, true);
    }

    private function validateChoiceOptions(ChoiceNode $node): void
    {
        if ([] === $node->options) {
            $this->addError(
                'Choice argument must have at least one option.',
                $node->startPosition,
                'validator.empty_choice',
            );
        }

        $previousLimit = null;
        foreach ($node->options as $option) {
            if (null !== $previousLimit && $option->limit < $previousLimit) {
                $this->addError(
                    'Choice limits must be in non-decreasing order.',
                    $option->startPosition,
                    'validator.choice_limits_order',
                );
            }

            if ($this->isMessageEmpty($option->message)) {
                $this->addError(
                    sprintf('Empty choice option message for limit "%s".', $option->limit),
                    $option->startPosition,
                    'validator.empty_choice_option',
                );
            }

            $previousLimit = $option->limit;
        }
    }

    private function validateArgumentUsage(): void
    {
        $unused = array_diff_key($this->arguments, $this->usedArguments);

        foreach ($unused as $name => $true) {
            $this->addWarning(
                sprintf('Argument "%s" is defined but never used in the message.', $name),
                0,
                'validator.unused_argument',
            );
        }
    }

    private function isMessageEmpty(MessageNode $message): bool
    {
        if ([] === $message->parts) {
            return true;
        }

        foreach ($message->parts as $part) {
            if ($part instanceof TextNode) {
                if ('' !== trim($part->text)) {
                    return false;
                }

                continue;
            }

            if ($part instanceof PoundNode) {
                return false;
            }

            return false;
        }

        return true;
    }

    private function addError(string $message, int $position, string $code): void
    {
        $this->result->addError(new ValidationError($message, $position, $this->source, $code));
    }

    private function addWarning(string $message, int $position, string $code): void
    {
        $this->result->addWarning(new ValidationError($message, $position, $this->source, $code));
    }

    private function registerArgument(string $name): void
    {
        $this->arguments[$name] = true;
        $this->usedArguments[$name] = true;
    }

    private function pushContext(string $type, string $name): void
    {
        $this->contextStack[] = ['type' => $type, 'name' => $name];
    }

    private function popContext(): void
    {
        array_pop($this->contextStack);
    }

    /**
     * @return array{type: string, name: string}|null
     */
    private function currentContext(): ?array
    {
        if ([] === $this->contextStack) {
            return null;
        }

        return $this->contextStack[array_key_last($this->contextStack)];
    }
}
