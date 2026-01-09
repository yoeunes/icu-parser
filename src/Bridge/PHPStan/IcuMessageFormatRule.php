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

namespace IcuParser\Bridge\PHPStan;

use IcuParser\Catalog\Catalog;
use IcuParser\Exception\IcuParserException;
use IcuParser\Loader\TranslationLoader;
use IcuParser\Parser\Parser;
use IcuParser\Type\ParameterType;
use IcuParser\Type\TypeInferer;
use IcuParser\Validation\SemanticValidator;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @implements Rule<MethodCall>
 */
final class IcuMessageFormatRule implements Rule
{
    public const IDENTIFIER_TRANSLATION_MISSING = 'icu.translation.missing';
    public const IDENTIFIER_SYNTAX_ERROR = 'icu.syntax.error';
    public const IDENTIFIER_SEMANTIC_ERROR = 'icu.semantic.error';
    public const IDENTIFIER_PARAMETER_MISSING = 'icu.parameter.missing';
    public const IDENTIFIER_PARAMETER_TYPE = 'icu.parameter.type';

    private readonly Parser $parser;

    private readonly TypeInferer $typeInferer;

    private readonly SemanticValidator $validator;

    /**
     * @var list<string>
     */
    private readonly array $paths;

    private ?Catalog $catalog = null;

    /**
     * @var array<string, array{error: string|null, types: array<string, ParameterType>, semantic: array<string>}>
     */
    private array $analysisCache = [];

    /**
     * @param array<int, string> $paths
     */
    public function __construct(
        private readonly bool $enabled = true,
        array $paths = [],
        private readonly ?string $defaultLocale = null,
        private readonly string $defaultDomain = 'messages',
        private readonly ?string $cacheDir = null,
    ) {
        $this->paths = $this->normalizePaths($paths);
        $this->parser = new Parser();
        $this->typeInferer = new TypeInferer();
        $this->validator = new SemanticValidator();
    }

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @return array<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->enabled) {
            return [];
        }

        if (!$node instanceof MethodCall) {
            return [];
        }

        if (!$node->name instanceof Identifier) {
            return [];
        }

        if ('trans' !== strtolower($node->name->toString())) {
            return [];
        }

        if (!$this->isTranslatorCall($node, $scope)) {
            return [];
        }

        $args = $node->getArgs();
        if (!isset($args[0])) {
            return [];
        }

        $idValues = $this->resolveConstantStrings($args[0]->value, $scope);
        if ([] === $idValues) {
            return [];
        }

        $domain = $this->resolveOptionalStringArg($args[2]->value ?? null, $scope);
        if ($this->isDynamicExplicitArg($args[2]->value ?? null, $scope)) {
            return [];
        }

        $locale = $this->resolveOptionalStringArg($args[3]->value ?? null, $scope);
        if ($this->isDynamicExplicitArg($args[3]->value ?? null, $scope)) {
            return [];
        }

        $parameters = $this->resolveParameterTypes($args[1]->value ?? null, $scope);
        $catalog = $this->getCatalog();
        if (null === $catalog) {
            return [];
        }

        $errors = [];
        foreach ($idValues as $id) {
            $message = $catalog->getMessage($id, $locale, $domain);
            if (null === $message) {
                $errors[] = RuleErrorBuilder::message(sprintf("Translation key '%s' not found.", $id))
                    ->identifier(self::IDENTIFIER_TRANSLATION_MISSING)
                    ->line($node->getLine())
                    ->build();

                continue;
            }

            $analysis = $this->analyzeMessage($message);
            if (null !== $analysis['error']) {
                $errors[] = RuleErrorBuilder::message(sprintf("ICU Syntax Error in '%s': %s", $id, $analysis['error']))
                    ->identifier(self::IDENTIFIER_SYNTAX_ERROR)
                    ->line($node->getLine())
                    ->build();

                continue;
            }

            foreach ($analysis['semantic'] as $semanticError) {
                $errors[] = RuleErrorBuilder::message(sprintf("ICU Syntax Error in '%s': %s", $id, $semanticError))
                    ->identifier(self::IDENTIFIER_SEMANTIC_ERROR)
                    ->line($node->getLine())
                    ->build();
            }

            foreach ($analysis['types'] as $name => $expectedType) {
                if (!isset($parameters[$name])) {
                    $errors[] = RuleErrorBuilder::message(sprintf("Missing parameter '%s' for message '%s'.", $name, $id))
                        ->identifier(self::IDENTIFIER_PARAMETER_MISSING)
                        ->line($node->getLine())
                        ->build();

                    continue;
                }

                $actualType = $parameters[$name];
                if (ParameterType::MIXED === $expectedType || ParameterType::MIXED === $actualType) {
                    continue;
                }

                if ($expectedType !== $actualType) {
                    $errors[] = RuleErrorBuilder::message(sprintf(
                        "Parameter '%s' expects '%s', but '%s' was passed.",
                        $name,
                        $expectedType->value,
                        $actualType->value,
                    ))
                        ->identifier(self::IDENTIFIER_PARAMETER_TYPE)
                        ->line($node->getLine())
                        ->build();
                }
            }
        }

        return $errors;
    }

    private function isTranslatorCall(MethodCall $node, Scope $scope): bool
    {
        if (!interface_exists(TranslatorInterface::class)) {
            return false;
        }

        $type = $scope->getType($node->var);

        return (new ObjectType(TranslatorInterface::class))->isSuperTypeOf($type)->yes();
    }

    /**
     * @return array<int, string>
     */
    private function resolveConstantStrings(Expr $expr, Scope $scope): array
    {
        $values = [];
        foreach ($scope->getType($expr)->getConstantStrings() as $constantString) {
            $values[] = $constantString->getValue();
        }

        return $values;
    }

    private function resolveOptionalStringArg(?Expr $expr, Scope $scope): ?string
    {
        if (null === $expr) {
            return null;
        }

        $type = $scope->getType($expr);
        if ($type->isNull()->yes()) {
            return null;
        }

        $constants = $type->getConstantStrings();
        if (1 !== \count($constants)) {
            return null;
        }

        return $constants[0]->getValue();
    }

    private function isDynamicExplicitArg(?Expr $expr, Scope $scope): bool
    {
        if (null === $expr) {
            return false;
        }

        $type = $scope->getType($expr);
        if ($type->isNull()->yes()) {
            return false;
        }

        return [] === $type->getConstantStrings();
    }

    /**
     * @return array<string, ParameterType>
     */
    private function resolveParameterTypes(?Expr $expr, Scope $scope): array
    {
        if (null === $expr) {
            return [];
        }

        if ($expr instanceof Array_) {
            return $this->resolveArrayLiteral($expr, $scope);
        }

        $type = $scope->getType($expr);
        $constantArrays = $type->getConstantArrays();
        if (1 !== \count($constantArrays)) {
            return [];
        }

        return $this->resolveConstantArray($constantArrays[0]);
    }

    /**
     * @return array<string, ParameterType>
     */
    private function resolveArrayLiteral(Array_ $array, Scope $scope): array
    {
        $resolved = [];

        foreach ($array->items as $item) {
            if (!$item instanceof ArrayItem || null === $item->key) {
                continue;
            }

            $key = $this->resolveArrayKey($item->key, $scope);
            if (null === $key) {
                continue;
            }

            $resolved[$key] = $this->mapType($scope->getType($item->value));
        }

        return $resolved;
    }

    /**
     * @return array<string, ParameterType>
     */
    private function resolveConstantArray(ConstantArrayType $arrayType): array
    {
        $resolved = [];
        $keys = $arrayType->getKeyTypes();
        $values = $arrayType->getValueTypes();

        foreach ($keys as $index => $keyType) {
            $constants = $keyType->getConstantStrings();
            if (1 !== \count($constants)) {
                continue;
            }

            $valueType = $values[$index] ?? null;
            if (!$valueType instanceof Type) {
                continue;
            }

            $resolved[$constants[0]->getValue()] = $this->mapType($valueType);
        }

        return $resolved;
    }

    private function resolveArrayKey(Expr $expr, Scope $scope): ?string
    {
        if ($expr instanceof String_) {
            return $expr->value;
        }

        $constants = $scope->getType($expr)->getConstantStrings();
        if (1 !== \count($constants)) {
            return null;
        }

        return $constants[0]->getValue();
    }

    private function mapType(Type $type): ParameterType
    {
        if ($type->isInteger()->yes() || $type->isFloat()->yes()) {
            return ParameterType::NUMBER;
        }

        $dateTimeType = new ObjectType(\DateTimeInterface::class);
        if ($dateTimeType->isSuperTypeOf($type)->yes()) {
            return ParameterType::DATETIME;
        }

        if ($type->isString()->yes()) {
            return ParameterType::STRING;
        }

        return ParameterType::MIXED;
    }

    /**
     * @return array{error: string|null, types: array<string, ParameterType>, semantic: array<string>}
     */
    private function analyzeMessage(string $message): array
    {
        if (isset($this->analysisCache[$message])) {
            return $this->analysisCache[$message];
        }

        try {
            $ast = $this->parser->parse($message);
        } catch (IcuParserException $exception) {
            return $this->analysisCache[$message] = [
                'error' => $exception->getMessage(),
                'types' => [],
                'semantic' => [],
            ];
        }

        $validation = $this->validator->validate($ast, $message);
        $semantic = [];
        foreach ($validation->getErrors() as $error) {
            $semantic[] = $error->getMessage();
        }

        $types = [];
        foreach ($this->typeInferer->infer($ast)->all() as $name => $type) {
            $types[$name] = $type;
        }

        return $this->analysisCache[$message] = [
            'error' => null,
            'types' => $types,
            'semantic' => $semantic,
        ];
    }

    private function getCatalog(): ?Catalog
    {
        if ([] === $this->paths) {
            return null;
        }

        if (null !== $this->catalog) {
            return $this->catalog;
        }

        $loader = new TranslationLoader(
            $this->paths,
            $this->defaultLocale,
            $this->cacheDir,
            defaultDomain: $this->defaultDomain,
        );

        $this->catalog = $loader->loadCatalog();

        return $this->catalog;
    }

    /**
     * @param array<int, string> $paths
     *
     * @return list<string>
     */
    private function normalizePaths(array $paths): array
    {
        $normalized = [];
        foreach ($paths as $path) {
            if (!\is_string($path) || '' === $path) {
                continue;
            }

            $normalized[] = $path;
        }

        return array_values(array_unique($normalized));
    }
}
