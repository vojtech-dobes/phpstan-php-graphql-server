<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\GraphQL;

use PHPStan;
use Vojtechdobes;


final class SchemaServiceOraculum
{

	public function __construct(
		private readonly PHPStan\Reflection\ClassReflection $schemaReflection,
	) {}



	public function getRootOperationType(Vojtechdobes\GraphQL\OperationType $operationType): string
	{
		return $this->schemaReflection
			->getNativeProperty('root__' . $operationType->value . '__type')
			->getNativeReflection()
			->getDefaultValue();
	}



	/**
	 * @return list<string>
	 */
	public function listFields(): array
	{
		return $this->schemaReflection
			->getNativeProperty('fields')
			->getNativeReflection()
			->getDefaultValue();
	}



	/**
	 * @return list<array{string, string}>
	 */
	public function listFieldsResolvedToObjectType(string $objectType): array
	{
		$propertyName = 'objectType__' . $objectType . '__fields';

		if ($this->schemaReflection->hasProperty($propertyName) === false) {
			return [];
		}

		return $this->schemaReflection
			->getNativeProperty($propertyName)
			->getNativeReflection()
			->getDefaultValue();
	}



	/**
	 * @return list<string>
	 */
	public function getFieldSchemaType(string $objectType, string $fieldName): array
	{
		$propertyName = 'field__' . $objectType . '_' . $fieldName . '__schemaType';

		if ($this->schemaReflection->hasProperty($propertyName) === false) {
			return [];
		}

		return $this->schemaReflection
			->getNativeProperty($propertyName)
			->getNativeReflection()
			->getDefaultValue();
	}



	public function getFieldResolverType(string $objectType, string $fieldName): PHPStan\Type\ObjectType
	{
		return $this->schemaReflection
			->getNativeProperty('field__' . $objectType . '_' . $fieldName . '__resolverClass')
			->getPhpDocType();
	}



	public function getFieldPhpType(string $objectType, string $fieldName): PHPStan\Type\Type
	{
		return $this->resolveEncodedPhpType(
			$this->schemaReflection
				->getNativeProperty('field__' . $objectType . '_' . $fieldName . '__phpType')
				->getPhpDocType(),
		);
	}



	public function getFieldArgumentsType(string $objectType, string $fieldName): PHPStan\Type\Type
	{
		return $this->resolveEncodedPhpType(
			$this->schemaReflection
				->getNativeProperty('field__' . $objectType . '_' . $fieldName . '__arguments')
				->getPhpDocType(),
		);
	}



	private function resolveEncodedPhpType(PHPStan\Type\Type $encodedType): PHPStan\Type\Type
	{
		return PHPStan\Type\TypeTraverser::map(
			$encodedType,
			static function (PHPStan\Type\Type $type, callable $traverse): PHPStan\Type\Type {
				if ($type instanceof PHPStan\Type\Generic\GenericObjectType && $type->getClassName() === '__GraphQL__Scalar') {
					return $type
						->getTypes()[0]
						->getTemplateType(Vojtechdobes\GraphQL\ScalarImplementation::class, 'TValue');
				}

				return $traverse($type);
			},
		);
	}

}
