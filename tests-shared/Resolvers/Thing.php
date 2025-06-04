<?php declare(strict_types=1);

namespace Vojtechdobes\TestsShared\Resolvers;


final class Thing implements Entity
{

	public function __construct(
		public readonly string $name,
	) {}

}
