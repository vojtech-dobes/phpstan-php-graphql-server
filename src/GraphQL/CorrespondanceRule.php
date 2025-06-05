<?php declare(strict_types = 1);

namespace Vojtechdobes\PHPStan\GraphQL;

use PHPStan;
use PhpParser;
use Vojtechdobes;


/**
 * @implements PHPStan\Rules\Rule<PHPStan\Node\CollectedDataNode>
 */
final class CorrespondanceRule implements PHPStan\Rules\Rule
{

	public function __construct(
		private readonly Config $config,
		private readonly PHPStan\Reflection\ReflectionProvider $reflectionProvider,
	) {}



	public function getNodeType(): string
	{
		return PHPStan\Node\CollectedDataNode::class;
	}



	public function processNode(PhpParser\Node $node, PHPStan\Analyser\Scope $scope): array
	{
		$result = [];

		foreach ($this->config->schemas as $schemaName) {
			$validClassName = Helpers::createValidSchemaClassName($schemaName);
			$invalidClassName = Helpers::createInvalidSchemaClassName($schemaName);

			// following check bypasses PHPStan\Testing\RuleTestCase
			// not being able to discover file generated on-the-fly
			if (
				@class_exists($invalidClassName) === false
				&& is_file($invalidClassNameFile = ($this->config->generatedDir . '/' . $invalidClassName . '.php'))
			) {
				require_once $invalidClassNameFile;
			}

			if ($this->reflectionProvider->hasClass($invalidClassName)) {
				$ruleError = PHPStan\Rules\RuleErrorBuilder::message("GraphQL schema isn't valid")
					->identifier('graphql.schemaInvalid')
					->file($schemaName)
					->line(0)
					->nonIgnorable();

				$errors = $this->reflectionProvider
					->getClass($invalidClassName)
					->getNativeProperty('errors')
					->getNativeReflection()
					->getDefaultValue();

				foreach ($errors as $error) {
					$ruleError = $ruleError->addTip($error['message']);
				}

				$result[] = $ruleError->build();

				continue;
			}

			$schemaServiceOraculum = $this->createSchemaServiceOraculum($validClassName);

			$fields = $schemaServiceOraculum->listFields();

			foreach ($fields as $field) {
				[$objectType, $fieldName] = explode('.', $field);

				$schemaType = $schemaServiceOraculum->getFieldSchemaType($objectType, $fieldName);

				if ($schemaType[count($schemaType) - 1] === $schemaServiceOraculum->getRootOperationType(Vojtechdobes\GraphQL\OperationType::Query)) {
					continue;
				}

				$resolverClassType = $schemaServiceOraculum->getFieldResolverType($objectType, $fieldName);

				$expectedArgumentsType = $schemaServiceOraculum->getFieldArgumentsType($objectType, $fieldName);
				$actualArgumentsType = $resolverClassType->getTemplateType(Vojtechdobes\GraphQL\FieldResolver::class, 'TArguments');

				if (
					$actualArgumentsType->isNull()->yes() === false // misconfigured generics are already reported in native rule
					&& $expectedArgumentsType->isSuperTypeOf($actualArgumentsType)->yes() === false
				) {
					$message = sprintf(
						"Arguments %s of field %s aren't contravariant with arguments %s of resolver %s",
						$expectedArgumentsType->describe(PHPStan\Type\VerbosityLevel::precise()),
						$field,
						$actualArgumentsType->describe(PHPStan\Type\VerbosityLevel::precise()),
						$resolverClassType->getClassName(),
					);

					$result[] = PHPStan\Rules\RuleErrorBuilder::message($message)
						->identifier('graphql.argumentsMismatch')
						->file($schemaName)
						->build();
				}

				$result = [
					...$result,
					...$this->listFieldResolvedValueErrors($scope, $schemaServiceOraculum, $schemaName, $objectType, $fieldName, $resolverClassType),
				];
			}
		}

		return $result;
	}



	private function createSchemaServiceOraculum(string $validClassName): SchemaServiceOraculum
	{
		// following check bypasses PHPStan\Testing\RuleTestCase
		// not being able to discover file generated on-the-fly
		if (
			@class_exists($validClassName) === false
			&& is_file($validClassNameFile = ($this->config->generatedDir . '/' . $validClassName . '.php'))
		) {
			require_once $validClassNameFile;
		}

		return new SchemaServiceOraculum(
			$this->reflectionProvider->getClass($validClassName),
		);
	}



	/**
	 * @return list<PHPStan\Rules\IdentifierRuleError>
	 */
	private function listFieldResolvedValueErrors(
		PHPStan\Analyser\Scope $scope,
		SchemaServiceOraculum $schemaServiceOraculum,
		string $schemaName,
		string $objectType,
		string $fieldName,
		PHPStan\Type\ObjectType $resolverClassType,
	): array
	{
		$result = [];

		$expectedPhpType = $schemaServiceOraculum->getFieldPhpType($objectType, $fieldName);

		[$actualPhpTypes, $errors] = $this->listFieldResolvedValueTypes(
			$scope,
			$schemaServiceOraculum,
			$objectType,
			$fieldName,
			$resolverClassType,
		);

		foreach ($errors as $error) {
			$result[] = PHPStan\Rules\RuleErrorBuilder::message($error)
				->identifier('graphql.typeMismatch')
				->file($schemaName)
				->build();
		}

		foreach ($actualPhpTypes as $actualPhpType) {
			if ($expectedPhpType->isSuperTypeOf($actualPhpType)->yes() === false) {
				$message = sprintf(
					"Type of field %s should be %s but resolver %s returns %s",
					"{$objectType}.{$fieldName}",
					$expectedPhpType->describe(PHPStan\Type\VerbosityLevel::precise()),
					$resolverClassType->describe(PHPStan\Type\VerbosityLevel::precise()),
					$actualPhpType->describe(PHPStan\Type\VerbosityLevel::precise()),
				);

				$result[] = PHPStan\Rules\RuleErrorBuilder::message($message)
					->identifier('graphql.typeMismatch')
					->file($schemaName)
					->build();
			}
		}

		return $result;
	}



	/**
	 * @return array{list<PHPStan\Type\Type>, list<string>}
	 */
	private function listFieldResolvedValueTypes(
		PHPStan\Analyser\Scope $scope,
		SchemaServiceOraculum $schemaServiceOraculum,
		string $objectType,
		string $fieldName,
		PHPStan\Type\ObjectType $resolverClassType,
	): array
	{
		$errors = [];
		$types = [];

		if ($resolverClassType->getClassName() === Vojtechdobes\GraphQL\ArrayFieldResolver::class) {
			$offsetType = new PHPStan\Type\Constant\ConstantStringType($fieldName);

			foreach ($this->listObjectTypeResolvedValueTypes($scope, $schemaServiceOraculum, $objectType) as $parentType) {
				if ($parentType->isOffsetAccessible()->yes() === false) {
					$errors[] = sprintf(
						"Resolver %s of field %s expects parent to have array access, but parent is resolved to %s",
						$resolverClassType->describe(PHPStan\Type\VerbosityLevel::precise()),
						"{$objectType}.{$fieldName}",
						$parentType->describe(PHPStan\Type\VerbosityLevel::precise()),
					);
				} elseif ($parentType->hasOffsetValueType($offsetType)->yes() === false) {
					$errors[] = sprintf(
						"Resolver %s of field %s expects parent to have offset '%s', but parent is resolved to %s",
						$resolverClassType->describe(PHPStan\Type\VerbosityLevel::precise()),
						"{$objectType}.{$fieldName}",
						$fieldName,
						$parentType->describe(PHPStan\Type\VerbosityLevel::precise()),
					);
				} else {
					$types[] = $parentType->getOffsetValueType($offsetType);
				}
			}
		} elseif ($resolverClassType->getClassName() === Vojtechdobes\GraphQL\GetterFieldResolver::class) {
			$methodName = 'get' . ucfirst($fieldName);

			foreach ($this->listObjectTypeResolvedValueTypes($scope, $schemaServiceOraculum, $objectType) as $parentType) {
				if ($parentType->isObject()->yes() === false) {
					$errors[] = sprintf(
						"Resolver %s of field %s expects parent to be an object, but parent is resolved to %s",
						$resolverClassType->describe(PHPStan\Type\VerbosityLevel::precise()),
						"{$objectType}.{$fieldName}",
						$parentType->describe(PHPStan\Type\VerbosityLevel::precise()),
					);
				} elseif ($parentType->hasMethod($methodName)->yes() === false) {
					$errors[] = sprintf(
						"Resolver %s of field %s expects parent to have method %s(), but method %s::%s() doesn't exist",
						$resolverClassType->describe(PHPStan\Type\VerbosityLevel::precise()),
						"{$objectType}.{$fieldName}",
						$methodName,
						$parentType->describe(PHPStan\Type\VerbosityLevel::precise()),
						$methodName,
					);
				} else {
					$types[] = PHPStan\Type\TypeCombinator::union(...array_map(
						static fn ($variant) => $variant->getReturnType(),
						$parentType->getMethod($methodName, $scope)->getVariants(),
					));
				}
			}
		} elseif ($resolverClassType->getClassName() === Vojtechdobes\GraphQL\PropertyFieldResolver::class) {
			foreach ($this->listObjectTypeResolvedValueTypes($scope, $schemaServiceOraculum, $objectType) as $parentType) {
				if ($parentType->isObject()->yes() === false) {
					$errors[] = sprintf(
						"Resolver %s of field %s expects parent to be an object, but parent is resolved to %s",
						$resolverClassType->describe(PHPStan\Type\VerbosityLevel::precise()),
						"{$objectType}.{$fieldName}",
						$parentType->describe(PHPStan\Type\VerbosityLevel::precise()),
					);
				} elseif ($parentType->hasProperty($fieldName)->yes() === false) {
					$errors[] = sprintf(
						"Resolver %s of field %s expects parent to have property \$%s, but property %s::\$%s doesn't exist",
						$resolverClassType->describe(PHPStan\Type\VerbosityLevel::precise()),
						"{$objectType}.{$fieldName}",
						$fieldName,
						$parentType->describe(PHPStan\Type\VerbosityLevel::precise()),
						$fieldName,
					);
				} else {
					$types[] = $parentType->getProperty($fieldName, $scope)->getReadableType();
				}
			}
		} else {
			$expectedParentType = $resolverClassType->getTemplateType(Vojtechdobes\GraphQL\FieldResolver::class, 'TObjectValue');

			foreach ($this->listObjectTypeResolvedValueTypes($scope, $schemaServiceOraculum, $objectType) as $parentType) {
				if ($expectedParentType->isSuperTypeOf($parentType)->yes() === false) {
					$errors[] = sprintf(
						"Resolver %s of field %s expects parent to be %s, but parent is resolved to %s",
						$resolverClassType->describe(PHPStan\Type\VerbosityLevel::precise()),
						"{$objectType}.{$fieldName}",
						$expectedParentType->describe(PHPStan\Type\VerbosityLevel::precise()),
						$parentType->describe(PHPStan\Type\VerbosityLevel::precise()),
					);
				}
			}

			if ($errors === []) {
				$types[] = $resolverClassType->getTemplateType(Vojtechdobes\GraphQL\FieldResolver::class, 'TResolvedValue');
			}
		}

		return [
			array_map(
				fn ($type) => $this->stripAwayDeferred($type),
				$types,
			),
			$errors,
		];
	}



	/**
	 * @return list<PHPStan\Type\Type>
	 */
	private function listObjectTypeResolvedValueTypes(
		PHPStan\Analyser\Scope $scope,
		SchemaServiceOraculum $schemaServiceOraculum,
		string $objectType,
	): array
	{
		$result = [];

		if ($objectType === $schemaServiceOraculum->getRootOperationType(Vojtechdobes\GraphQL\OperationType::Query)) {
			return [new PHPStan\Type\NullType()];
		}

		foreach ($schemaServiceOraculum->listFieldsResolvedToObjectType($objectType) as [$parentObjectType, $parentFieldName]) {
			[$parentTypes] = $this->listFieldResolvedValueTypes(
				$scope,
				$schemaServiceOraculum,
				$parentObjectType,
				$parentFieldName,
				$schemaServiceOraculum->getFieldResolverType($parentObjectType, $parentFieldName),
			);

			foreach ($parentTypes as $parentType) {
				$parentType = $this->unwrapType(
					$schemaServiceOraculum,
					$parentObjectType,
					$parentFieldName,
					$this->stripAwayDeferred($parentType),
				);

				$result[$parentType->describe(PHPStan\Type\VerbosityLevel::precise())] = $parentType;
			}
		}

		return array_values($result);
	}



	private function stripAwayDeferred(
		PHPStan\Type\Type $type,
	): PHPStan\Type\Type
	{
		return PHPStan\Type\TypeTraverser::map(
			$type,
			function (PHPStan\Type\Type $type, callable $traverse): PHPStan\Type\Type {
				if ($type instanceof PHPStan\Type\Generic\GenericObjectType && $type->getClassName() === Vojtechdobes\GraphQL\Deferred::class) {
					return $type->getTemplateType(Vojtechdobes\GraphQL\Deferred::class, 'TValue');
				}

				return $traverse($type);
			},
		);
	}



	private function unwrapType(
		SchemaServiceOraculum $schemaServiceOraculum,
		string $objectType,
		string $fieldName,
		PHPStan\Type\Type $type,
	): PHPStan\Type\Type
	{
		$schemaType = $schemaServiceOraculum->getFieldSchemaType($objectType, $fieldName);

		foreach ($schemaType as $schemaTypeLevel) {
			$type = PHPStan\Type\TypeCombinator::removeNull(
				match ($schemaTypeLevel) {
					':list' => $type->getIterableValueType(),
					default => $type,
				},
			);
		}

		return $type;
	}

}
