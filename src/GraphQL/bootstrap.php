<?php declare(strict_types=1);

/**
 * @var Nette\DI\Container $container
 */

$config = $container->getByType(Vojtechdobes\PHPStan\GraphQL\Config::class);

$schemaClassGenerator = new Vojtechdobes\PHPStan\GraphQL\SchemaClassGenerator(
	$config->generatedDir,
);

foreach ($config->schemas as $schemaName) {
	$schemaClassGenerator->generateSchemaClass(
		$schemaName,
		Vojtechdobes\PHPStan\GraphQL\Helpers::createValidSchemaClassName($schemaName),
		Vojtechdobes\PHPStan\GraphQL\Helpers::createInvalidSchemaClassName($schemaName),
		$container->getByType(Vojtechdobes\PHPStan\GraphQL\Adapter::class),
	);
}
