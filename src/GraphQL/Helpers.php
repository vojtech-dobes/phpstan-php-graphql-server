<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\GraphQL;

use Exception;
use Nette;


final class Helpers
{

	public static function createValidSchemaClassName(string $schemaName): string
	{
		return sprintf(
			'GraphQLSchemaClass_%s',
			md5($schemaName),
		);
	}



	public static function createInvalidSchemaClassName(string $schemaName): string
	{
		return sprintf(
			'GraphQLSchemaClass_%s_Invalid',
			md5($schemaName),
		);
	}



	public static function normalizeSchema(string $schemaName, Nette\Schema\Context $context): string
	{
		$schemaPath = realpath($schemaName);

		if ($schemaPath === false) {
			throw new Exception("{$schemaName} doesn't exist");
		}

		return $schemaPath;
	}

}
