<?php declare(strict_types=1);

namespace Vojtechdobes\TestsShared\Resolvers;


final class Person
{

	public function __construct(
		public readonly string $name,
	) {}

}
