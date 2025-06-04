<?php declare(strict_types=1);

namespace Vojtechdobes\TestsShared\Resolvers;

use Vojtechdobes;


/**
 * @implements Vojtechdobes\GraphQL\FieldResolver<null, Thing>
 */
final class QueryProviderOfInvalidPersonParentTypeThingFieldResolver implements Vojtechdobes\GraphQL\FieldResolver
{

	public function resolveField(mixed $objectValue, Vojtechdobes\GraphQL\FieldSelection $field): mixed
	{
		return new Thing(name: 'Almond');
	}

}
