<?php declare(strict_types=1);

namespace Vojtechdobes\TestsShared;

use PHPStan;
use Vojtechdobes;


/**
 * @extends PHPStan\Testing\RuleTestCase<Vojtechdobes\PHPStan\GraphQL\CorrespondanceRule>
 */
abstract class AbstractCorrespondanceRuleTest extends PHPStan\Testing\RuleTestCase
{

	final protected function getRule(): PHPStan\Rules\Rule
	{
		return self::getContainer()->getByType(Vojtechdobes\PHPStan\GraphQL\CorrespondanceRule::class);
	}



	final protected function getCollectors(): array
	{
		// rule based on CollectedDataNode won't run without any collector
		return [
			new DummyCollector(),
		];
	}



	final public function testRule(): void
	{
		$this->analyse([__DIR__ . '/DummyCollector.php'], [
			[
				'Type of field Query.invalidStringResolvedAsBool should be string|null but resolver Vojtechdobes\TestsShared\Resolvers\QueryInvalidStringResolvedAsBoolFieldResolver returns bool',
				-1,
			],
			[
				"Arguments array{arg1: string|null} of field Query.invalidArgumentsMismatch aren't contravariant with arguments array{} of resolver Vojtechdobes\TestsShared\Resolvers\QueryInvalidArgumentsMismatchFieldResolver",
				-1,
			],
			[
				'Resolver Vojtechdobes\GraphQL\PropertyFieldResolver of field Query.rootFieldWithParentBasedResolver expects parent to be an object, but parent is resolved to null',
				-1,
			],
			[
				'Resolver Vojtechdobes\TestsShared\Resolvers\PersonParentTypeNameFieldResolver of field PersonParentType.name expects parent to be Vojtechdobes\TestsShared\Resolvers\Person, but parent is resolved to array{}',
				-1,
			],
			[
				'Resolver Vojtechdobes\TestsShared\Resolvers\PersonParentTypeNameFieldResolver of field PersonParentType.name expects parent to be Vojtechdobes\TestsShared\Resolvers\Person, but parent is resolved to Vojtechdobes\TestsShared\Resolvers\Entity',
				-1,
			],
			[
				'Resolver Vojtechdobes\TestsShared\Resolvers\PersonParentTypeNameFieldResolver of field PersonParentType.name expects parent to be Vojtechdobes\TestsShared\Resolvers\Person, but parent is resolved to Vojtechdobes\TestsShared\Resolvers\Thing',
				-1,
			],
			[
				'Resolver Vojtechdobes\TestsShared\Resolvers\EntityParentTypeNameFieldResolver of field EntityParentType.name expects parent to be Vojtechdobes\TestsShared\Resolvers\Entity, but parent is resolved to array{}',
				-1,
			],
		]);
	}



	final public static function getAdditionalConfigFiles(): array
	{
		return [static::getTestConfigFile()];
	}



	abstract public static function getTestConfigFile(): string;

}
