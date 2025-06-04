<?php declare(strict_types = 1);

namespace Vojtechdobes\Tests;

use Vojtechdobes;


final class CustomAdapter implements Vojtechdobes\PHPStan\GraphQL\Adapter
{

	/**
	 * @throws Vojtechdobes\GraphQL\Exceptions\CannotGenerateCachedSchemaException
	 */
	public function getSchema(string $schemaName): Vojtechdobes\GraphQL\TypeSystem\Schema
	{
		$schemaLoader = new Vojtechdobes\GraphQL\SchemaLoader(
			autoReload: true,
			tempDir: __DIR__ . '/../tests-temp',
		);

		return $schemaLoader->loadSchema(
			schemaPath: __DIR__ . '/../tests-shared/schema.graphqls',
			enumClasses: [],
			scalarImplementations: [],
		);
	}



	public function getFieldResolverProvider(string $schemaName): Vojtechdobes\GraphQL\FieldResolverProvider
	{
		return new Vojtechdobes\GraphQL\StaticFieldResolverProvider([
			'Query.validNonNullString' => new Vojtechdobes\TestsShared\Resolvers\QueryValidNonNullStringFieldResolver(),
			'Query.invalidStringResolvedAsBool' => new Vojtechdobes\TestsShared\Resolvers\QueryInvalidStringResolvedAsBoolFieldResolver(),
			'Query.invalidArgumentsMismatch' => new Vojtechdobes\TestsShared\Resolvers\QueryInvalidArgumentsMismatchFieldResolver(),
			'Query.validDeferred' => new Vojtechdobes\TestsShared\Resolvers\QueryValidDeferredFieldResolver(),

			'Query.arrayType' => new Vojtechdobes\TestsShared\Resolvers\QueryArrayTypeFieldResolver(),
			'ArrayType' => new Vojtechdobes\GraphQL\ArrayFieldResolver(),

			'Query.objectType' => new Vojtechdobes\TestsShared\Resolvers\QueryObjectTypeFieldResolver(),
			'ObjectType.withGetter' => new Vojtechdobes\GraphQL\GetterFieldResolver(),
			'ObjectType.withProperty' => new Vojtechdobes\GraphQL\PropertyFieldResolver(),

			'Query.providerOfInvalidPersonParentTypeArray' => new Vojtechdobes\TestsShared\Resolvers\QueryProviderOfInvalidPersonParentTypeArrayFieldResolver(),
			'Query.providerOfInvalidPersonParentTypeEntity' => new Vojtechdobes\TestsShared\Resolvers\QueryProviderOfInvalidPersonParentTypeEntityFieldResolver(),
			'Query.providerOfInvalidPersonParentTypeThing' => new Vojtechdobes\TestsShared\Resolvers\QueryProviderOfInvalidPersonParentTypeThingFieldResolver(),
			'Query.providerOfValidPersonParentTypePerson' => new Vojtechdobes\TestsShared\Resolvers\QueryProviderOfValidPersonParentTypePersonFieldResolver(),

			'Query.providerOfInvalidEntityParentTypeArray' => new Vojtechdobes\TestsShared\Resolvers\QueryProviderOfInvalidEntityParentTypeArrayFieldResolver(),
			'Query.providerOfValidEntityParentTypeEntity' => new Vojtechdobes\TestsShared\Resolvers\QueryProviderOfValidEntityParentTypeEntityFieldResolver(),
			'Query.providerOfValidEntityParentTypePerson' => new Vojtechdobes\TestsShared\Resolvers\QueryProviderOfValidEntityParentTypePersonFieldResolver(),
			'Query.providerOfValidEntityParentTypeThing' => new Vojtechdobes\TestsShared\Resolvers\QueryProviderOfValidEntityParentTypeThingFieldResolver(),

			'PersonParentType.name' => new Vojtechdobes\TestsShared\Resolvers\PersonParentTypeNameFieldResolver(),
			'EntityParentType.name' => new Vojtechdobes\TestsShared\Resolvers\EntityParentTypeNameFieldResolver(),
		]);
	}

}
