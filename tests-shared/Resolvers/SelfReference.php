<?php declare(strict_types=1);

namespace Vojtechdobes\TestsShared\Resolvers;


final class SelfReference
{

	public function __construct(
		public readonly ?SelfReference $internalSelfReference,
	) {}

}
