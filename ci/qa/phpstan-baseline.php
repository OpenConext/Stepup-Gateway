<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Attribute class Surfnet\\\\StepupGateway\\\\ApiBundle\\\\Controller\\\\Route does not exist\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/ApiBundle/Controller/SmsController.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to deprecated function GuzzleHttp\\\\json_decode\\(\\)\\:
json_decode will be removed in guzzlehttp/guzzle\\:8\\.0\\. Use Utils\\:\\:jsonDecode instead\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/SamlEntity.php',
];
$ignoreErrors[] = [
	'message' => '#^Deprecated in PHP 8\\.0\\: Required parameter \\$value follows optional parameter \\$propertyPath\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Exception/AssertionFailedException.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Controller\\\\SamlProxyController\\:\\:getServiceProvider\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Controller/SamlProxyController.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
