<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\GraphQL;

use Vojtechdobes;


interface Adapter
{

	/**
	 * @throws Vojtechdobes\GraphQL\Exceptions\InvalidSchemaException
	 */
	function getSchema(string $schemaName): Vojtechdobes\GraphQL\TypeSystem\Schema;



	function getFieldResolverProvider(string $schemaName): Vojtechdobes\GraphQL\FieldResolverProvider;

}
