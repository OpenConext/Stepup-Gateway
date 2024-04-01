<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Surfnet\\\\SamlBundle\\\\Entity\\\\ServiceProvider\\:\\:determineAcsLocation\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Controller/SamlProxyController.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Proxy\\\\ProxyStateHandler\\:\\:setSubject\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Service/Gateway/SecondFactorVerificationService.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
