<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\GraphQL;

use Exception;


final class Config
{

	/** @var list<string> */
	public readonly array $schemas;



	/**
	 * @param list<string> $schemas
	 * @throws Exception
	 */
	public function __construct(
		public readonly string $generatedDir,
		array $schemas,
	)
	{
		$this->schemas = array_map(
			static function ($schema): string {
				$schemaFile = realpath($schema);

				if ($schemaFile === false) {
					throw new Exception("$schema doesn't exist");
				}

				return $schemaFile;
			},
			$schemas,
		);
	}

}
