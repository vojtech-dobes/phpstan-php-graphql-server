<?php declare(strict_types=1);

namespace Vojtechdobes\Tests;

use Vojtechdobes;


final class CorrespondanceRuleTest extends Vojtechdobes\TestsShared\AbstractCorrespondanceRuleTest
{

	public static function getTestConfigFile(): string
	{
		return __DIR__ . '/CorrespondanceRuleTest.extension.neon';
	}

}
