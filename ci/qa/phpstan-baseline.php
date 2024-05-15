<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	// identifier: function.deprecated
	'message' => '#^Call to deprecated function GuzzleHttp\\\\json_decode\\(\\)\\:
json_decode will be removed in guzzlehttp/guzzle\\:8\\.0\\. Use Utils\\:\\:jsonDecode instead\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/SamlEntity.php',
];
$ignoreErrors[] = [
	// identifier: parameter.requiredAfterOptional
	'message' => '#^Deprecated in PHP 8\\.0\\: Required parameter \\$value follows optional parameter \\$propertyPath\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Exception/AssertionFailedException.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
