<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	// identifier: function.deprecated
	'message' => '#^Call to deprecated function GuzzleHttp\\\\json_decode\\(\\)\\:
json_decode will be removed in guzzlehttp/guzzle\\:8\\.0\\. Use Utils\\:\\:jsonDecode instead\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/SamlEntity.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
