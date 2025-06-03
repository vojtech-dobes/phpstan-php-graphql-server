<?php declare(strict_types=1);

namespace Vojtechdobes\TestsShared\Resolvers;

use Vojtechdobes;


/**
 * @implements Vojtechdobes\GraphQL\FieldResolver<null, Vojtechdobes\GraphQL\Deferred<string>>
 */
final class QueryValidDeferredFieldResolver implements Vojtechdobes\GraphQL\FieldResolver
{

	public function resolveField(mixed $objectValue, Vojtechdobes\GraphQL\FieldSelection $field): mixed
	{
		return new Vojtechdobes\GraphQL\Deferred(static fn () => 'David');
	}

}
