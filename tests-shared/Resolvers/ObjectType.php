<?php declare(strict_types=1);

namespace Vojtechdobes\TestsShared\Resolvers;


final class ObjectType
{

	public function __construct(
		public readonly string $withProperty = 'Property',
	) {}



	public function getWithGetter(): string
	{
		return 'Getter';
	}

}
