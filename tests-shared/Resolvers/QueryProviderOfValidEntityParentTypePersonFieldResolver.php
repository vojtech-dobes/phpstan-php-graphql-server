<?php declare(strict_types=1);

namespace Vojtechdobes\TestsShared\Resolvers;

use Vojtechdobes;


/**
 * @implements Vojtechdobes\GraphQL\FieldResolver<null, Person>
 */
final class QueryProviderOfValidEntityParentTypePersonFieldResolver implements Vojtechdobes\GraphQL\FieldResolver
{

	public function resolveField(mixed $objectValue, Vojtechdobes\GraphQL\FieldSelection $field): mixed
	{
		return new Person(name: 'Alice');
	}

}
