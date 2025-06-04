<?php declare(strict_types = 1);

namespace Vojtechdobes\PHPStan\GraphQL;

use Abstergo;
use BackedEnum;
use Nette;
use PHPStan;
use Vojtechdobes;


final class SchemaClassGenerator
{

	public function __construct(
		private readonly string $generatedDir,
	) {}



	public function generateSchemaClass(
		string $schemaName,
		string $validClassName,
		string $invalidClassName,
		Adapter $adapter,
	): void
	{
		try {
			$schema = $adapter->getSchema($schemaName);
		} catch (Vojtechdobes\GraphQL\Exceptions\InvalidSchemaException $e) {
			$this->generateInvalidSchemaClass(
				$invalidClassName,
				$e,
			);

			return;
		}

		Nette\Utils\FileSystem::delete(
			"{$this->generatedDir}/{$invalidClassName}.php",
		);

		$this->generateValidSchemaClass(
			$validClassName,
			$schema,
			$adapter->getFieldResolverProvider($schemaName),
		);
	}



	private function generateInvalidSchemaClass(
		string $invalidClassName,
		Vojtechdobes\GraphQL\Exceptions\InvalidSchemaException $e,
	): void
	{
		$file = new Nette\PhpGenerator\PhpFile();
		$file->setStrictTypes();

		$class = $file->addClass($invalidClassName);

		$class->addProperty('errors')
			->setPublic()
			->setValue(
				array_map(
					static fn ($error) => $error->toResponse(),
					$e->errors,
				),
			);

		Nette\Utils\FileSystem::write(
			"{$this->generatedDir}/{$invalidClassName}.php",
			(string) $file,
		);
	}



	private function generateValidSchemaClass(
		string $className,
		Vojtechdobes\GraphQL\TypeSystem\Schema $schema,
		Vojtechdobes\GraphQL\FieldResolverProvider $fieldResolverProvider,
	): void
	{
		$file = new Nette\PhpGenerator\PhpFile();
		$file->setStrictTypes();

		$class = $file->addClass($className);

		foreach ($schema->rootOperationTypes as $operation => $type) {
			$class->addProperty(sprintf("root__%s__type", $operation))
				->setPublic()
				->setValue($type);
		}

		$fields = [];
		$objectFields = [];

		foreach ($schema->getTypeDefinitions() as $typeDefinition) {
			if (!$typeDefinition instanceof Vojtechdobes\GraphQL\TypeSystem\ObjectTypeDefinition) {
				continue;
			}

			if (str_starts_with($typeDefinition->name, '__')) {
				continue;
			}

			$objectType = $typeDefinition->name;

			foreach ($typeDefinition->fields as $fieldDefinition) {
				$fieldName = $fieldDefinition->name;

				$fields[] = "{$objectType}.{$fieldName}";

				$class->addProperty('field__' . $objectType . '_' . $fieldName . '__phpType')
					->setPublic()
					->addComment('@var ' . self::getPhpType($schema, $fieldDefinition->type));

				$schemaType = [];

				$type = $fieldDefinition->type;

				while ($type !== null) {
					$schemaType[] = match (true) {
						$type instanceof Vojtechdobes\GraphQL\Types\ListType => ':list',
						$type instanceof Vojtechdobes\GraphQL\Types\NamedType => $type->name,
						$type instanceof Vojtechdobes\GraphQL\Types\NonNullType => ':nonNull',
						default => throw new PHPStan\ShouldNotHappenException(),
					};

					$type = $type->getWrappedType();
				}

				$class->addProperty('field__' . $objectType . '_' . $fieldName . '__schemaType')
					->setPublic()
					->setValue($schemaType);

				$class->addProperty('field__' . $objectType . '_' . $fieldName . '__resolverClass')
					->setPublic()
					->addComment('@var ' . (
						$fieldResolverProvider->getFieldResolverClass("{$objectType}.{$fieldName}")
						?? $fieldResolverProvider->getFieldResolverClass($objectType)
					));

				$class->addProperty('field__' . $objectType . '_' . $fieldName . '__arguments')
					->setPublic()
					->addComment('@var ' . sprintf(
						'array{%s}',
						implode(', ', array_map(
							fn ($argumentDefinition) => sprintf(
								'%s: %s',
								$argumentDefinition->name,
								self::getPhpType(
									$schema,
									$argumentDefinition->type,
								),
							),
							$fieldDefinition->argumentDefinitions,
						)),
					));

				$typeDefinition = $schema->getTypeDefinition($fieldDefinition->type->getNamedType());

				if ($typeDefinition instanceof Vojtechdobes\GraphQL\TypeSystem\ObjectTypeDefinition) {
					$objectFields[$typeDefinition->name] ??= [];
					$objectFields[$typeDefinition->name][] = [$objectType, $fieldName];
				}
			}
		}

		$class->addProperty('fields')
			->setPublic()
			->setValue($fields);

		foreach ($objectFields as $objectType => $fieldNames) {
			$class->addProperty('objectType__' . $objectType .'__fields')
				->setPublic()
				->setValue($fieldNames);
		}

		Nette\Utils\FileSystem::write(
			"{$this->generatedDir}/{$className}.php",
			(string) $file,
		);
	}



	private static function getPhpType(
		Vojtechdobes\GraphQL\TypeSystem\Schema $schema,
		Vojtechdobes\GraphQL\Types\Type $type,
	): string
	{
		return match (true) {
			$type instanceof Vojtechdobes\GraphQL\Types\ListType => 'iterable<' . self::getPhpType($schema, $type->itemType) . '>|null',
			$type instanceof Vojtechdobes\GraphQL\Types\NamedType => self::getPhpNamedType($schema, $type),
			$type instanceof Vojtechdobes\GraphQL\Types\NonNullType => substr(self::getPhpType($schema, $type->type), 0, -5), // remove |null
			default => throw new PHPStan\ShouldNotHappenException(),
		};
	}



	private static function getPhpNamedType(
		Vojtechdobes\GraphQL\TypeSystem\Schema $schema,
		Vojtechdobes\GraphQL\Types\NamedType $type,
	): string
	{
		$typeDefinition = $schema->getTypeDefinitionOrNull($type->name);

		if ($typeDefinition === null) {
			throw new PHPStan\ShouldNotHappenException("Type definition '{$type->name}' can't be found");
		}

		if ($typeDefinition instanceof Vojtechdobes\GraphQL\TypeSystem\ScalarTypeDefinition) {
			$scalarFormatter = $schema->scalarImplementationRegistry->getItem($typeDefinition->name);

			$fieldType = '__GraphQL__Scalar<' . $scalarFormatter::class. '>';
		} elseif ($typeDefinition instanceof Vojtechdobes\GraphQL\TypeSystem\EnumTypeDefinition) {
			$enumClass = $schema->getEnumClass($typeDefinition->name);

			if ($enumClass !== null) {
				$fieldType = $enumClass;
			} else {
				$fieldType = implode('|', array_map(
					static fn ($enumValueDefinition) => '"' . $enumValueDefinition->name . '"',
					$typeDefinition->enumValues,
				));
			}
		} elseif ($typeDefinition instanceof Vojtechdobes\GraphQL\TypeSystem\InputObjectTypeDefinition) {
			$inputFieldTypes = [];

			foreach ($typeDefinition->fields as $inputFieldDefinition) {
				$inputFieldTypes[] = sprintf(
					'%s: %s',
					$inputFieldDefinition->name,
					self::getPhpType(
						$schema,
						$inputFieldDefinition->type,
					),
				);
			}

			$fieldType = sprintf(
				'array{%s}',
				implode(', ', $inputFieldTypes),
			);
		} else {
			$fieldType = 'mixed';
		}

		return $fieldType . '|null';
	}

}
