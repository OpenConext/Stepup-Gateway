<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^8\\.5 use FILTER_UNSAFE_RAW instead$#',
	'identifier' => 'constant.deprecated',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated function GuzzleHttp\\\\json_decode\\(\\)\\:
json_decode will be removed in guzzlehttp/guzzle\\:8\\.0\\. Use Utils\\:\\:jsonDecode instead\\.$#',
	'identifier' => 'function.deprecated',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/SamlEntity.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
