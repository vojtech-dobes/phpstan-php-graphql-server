<?php declare(strict_types=1);

namespace Vojtechdobes\TestsShared;

use PHPStan;
use PhpParser;


/**
 * @implements PHPStan\Collectors\Collector<PHPStan\Node\FileNode, bool>
 */
final class DummyCollector implements PHPStan\Collectors\Collector
{

	public function getNodeType(): string
	{
		return PHPStan\Node\FileNode::class;
	}



	public function processNode(PhpParser\Node $node, PHPStan\Analyser\Scope $scope)
	{
		return true;
	}

}
