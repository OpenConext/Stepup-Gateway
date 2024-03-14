<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Generator expects value type Symfony\\\\Component\\\\HttpKernel\\\\Bundle\\\\BundleInterface, object given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Kernel.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Symfony\\\\Component\\\\Config\\\\Definition\\\\Builder\\\\NodeDefinition\\:\\:children\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/ApiBundle/DependencyInjection/Configuration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\ApiBundle\\\\DependencyInjection\\\\SurfnetStepupGatewayApiExtension\\:\\:load\\(\\) has parameter \\$config with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/ApiBundle/DependencyInjection/SurfnetStepupGatewayApiExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\ApiBundle\\\\Dto\\\\YubikeyOtpVerificationResult\\:\\:didOtpVerificationFail\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/ApiBundle/Dto/YubikeyOtpVerificationResult.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\ApiBundle\\\\Dto\\\\YubikeyOtpVerificationResult\\:\\:didPublicIdMatch\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/ApiBundle/Dto/YubikeyOtpVerificationResult.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\ApiBundle\\\\Security\\\\Http\\\\EntryPoint\\\\JsonBasicAuthenticationEntryPoint\\:\\:__construct\\(\\) has parameter \\$realmName with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/ApiBundle/Security/Http/EntryPoint/JsonBasicAuthenticationEntryPoint.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\ApiBundle\\\\Security\\\\Http\\\\EntryPoint\\\\JsonBasicAuthenticationEntryPoint\\:\\:\\$realmName has no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/ApiBundle/Security/Http/EntryPoint/JsonBasicAuthenticationEntryPoint.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\ApiBundle\\\\Service\\\\YubikeyServiceInterface\\:\\:verifyPublicId\\(\\) has parameter \\$secondFactorIdentifier with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/ApiBundle/Service/YubikeyServiceInterface.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\ApiBundle\\\\Sms\\\\MessageBirdMessageResult\\:\\:getRawErrors\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/ApiBundle/Sms/MessageBirdMessageResult.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\ApiBundle\\\\Sms\\\\MessageBirdMessageResult\\:\\:\\$message has no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/ApiBundle/Sms/MessageBirdMessageResult.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\ApiBundle\\\\Sms\\\\SmsAdapterProvider\\:\\:\\$allowedServices has no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/ApiBundle/Sms/SmsAdapterProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\ApiBundle\\\\Sms\\\\SmsMessageResultInterface\\:\\:getRawErrors\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/ApiBundle/Sms/SmsMessageResultInterface.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\ApiBundle\\\\Sms\\\\SpryngMessageResult\\:\\:getRawErrors\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/ApiBundle/Sms/SpryngMessageResult.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\ApiBundle\\\\Sms\\\\SpryngMessageResult\\:\\:\\$message has no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/ApiBundle/Sms/SpryngMessageResult.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_null\\(\\) with string will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/ApiBundle/Sms/SpryngService.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\ApiBundle\\\\Sms\\\\SpryngService\\:\\:\\$route \\(string\\) does not accept string\\|null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/ApiBundle/Sms/SpryngService.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Assert\\:\\:keysAre\\(\\) has parameter \\$array with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Assert.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Assert\\:\\:keysAre\\(\\) has parameter \\$expectedKeys with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Assert.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Assert\\:\\:keysAre\\(\\) has parameter \\$propertyPath with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Assert.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\ExceptionController\\:\\:getPageTitleAndDescription\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/ExceptionController.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method object\\:\\:createResponseFor\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/GatewayController.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method object\\:\\:getParentRequest\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/GatewayController.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method object\\:\\:getResponseContextServiceId\\(\\)\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/GatewayController.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method object\\:\\:handleSsoOn2faCookieStorage\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/GatewayController.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method object\\:\\:notice\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/GatewayController.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method saveXML\\(\\) on DOMDocument\\|null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/GatewayController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\GatewayController\\:\\:getGatewayConsumeAssertionService\\(\\) should return Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Service\\\\Gateway\\\\ConsumeAssertionService but returns object\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/GatewayController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\GatewayController\\:\\:getGatewayFailedResponseService\\(\\) should return Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Service\\\\Gateway\\\\FailedResponseService but returns object\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/GatewayController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\GatewayController\\:\\:getGatewayLoginService\\(\\) should return Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Service\\\\Gateway\\\\LoginService but returns object\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/GatewayController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\GatewayController\\:\\:getGatewayRespondService\\(\\) should return Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Service\\\\Gateway\\\\RespondService but returns object\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/GatewayController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\GatewayController\\:\\:getResponseContext\\(\\) has parameter \\$authenticationMode with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/GatewayController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\GatewayController\\:\\:getResponseContext\\(\\) should return Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\ResponseContext but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/GatewayController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\GatewayController\\:\\:getResponseContext\\(\\) should return Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\ResponseContext but returns object\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/GatewayController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\GatewayController\\:\\:proxySsoAction\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/GatewayController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\GatewayController\\:\\:render\\(\\) has parameter \\$parameters with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/GatewayController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\GatewayController\\:\\:respondAction\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/GatewayController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\GatewayController\\:\\:supportsAuthenticationMode\\(\\) has parameter \\$authenticationMode with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/GatewayController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$originalRequestId of method Surfnet\\\\StepupGateway\\\\SecondFactorOnlyBundle\\\\Adfs\\\\ResponseHelper\\:\\:isAdfsResponse\\(\\) expects string, string\\|null given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/GatewayController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$str of function base64_encode expects string, string\\|false given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/GatewayController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$authenticationMode of method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\GatewayController\\:\\:renderSamlResponse\\(\\) expects string, mixed given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/GatewayController.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/GatewayController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\MetadataController\\:\\:metadataAction\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/MetadataController.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Symfony\\\\Component\\\\Form\\\\FormInterface\\:\\:getClickedButton\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method object\\:\\:findByUuid\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method object\\:\\:forAuthentication\\(\\)\\.$#',
	'count' => 7,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method object\\:\\:getResponseContextServiceId\\(\\)\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method object\\:\\:isGssf\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method object\\:\\:logIntrinsicLoaAuthentication\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method object\\:\\:trans\\(\\)\\.$#',
	'count' => 7,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$secondFactorType on mixed\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method get\\(\\) on Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\ServiceProvider\\|null\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Service\\\\StepUpAuthenticationService referenced with incorrect case\\: Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Service\\\\StepupAuthenticationService\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\SecondFactorController\\:\\:buildCancelAuthenticationForm\\(\\) return type has no value type specified in iterable type Symfony\\\\Component\\\\Form\\\\FormInterface\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\SecondFactorController\\:\\:cancelAuthenticationAction\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\SecondFactorController\\:\\:chooseSecondFactorAction\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\SecondFactorController\\:\\:getAuthenticationLogger\\(\\) should return Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Monolog\\\\Logger\\\\AuthenticationLogger but returns object\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\SecondFactorController\\:\\:getCookieService\\(\\) should return Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Sso2fa\\\\CookieService but returns object\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\SecondFactorController\\:\\:getResponseContext\\(\\) has parameter \\$authenticationMode with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\SecondFactorController\\:\\:getResponseContext\\(\\) should return Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\ResponseContext but return statement is missing\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\SecondFactorController\\:\\:getResponseContext\\(\\) should return Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\ResponseContext but returns object\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\SecondFactorController\\:\\:getSecondFactorService\\(\\) should return Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Service\\\\SecondFactorService but returns object\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\SecondFactorController\\:\\:getStepupService\\(\\) should return Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Service\\\\StepUpAuthenticationService but returns object\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\SecondFactorController\\:\\:gssfVerifiedAction\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\SecondFactorController\\:\\:selectAndRedirectTo\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\SecondFactorController\\:\\:selectAndRedirectTo\\(\\) has parameter \\$authenticationMode with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\SecondFactorController\\:\\:selectSecondFactorForVerificationAction\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\SecondFactorController\\:\\:selectSecondFactorForVerificationAction\\(\\) has parameter \\$authenticationMode with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\SecondFactorController\\:\\:selectSecondFactorForVerificationSfoAction\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\SecondFactorController\\:\\:selectSecondFactorForVerificationSsoAction\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\SecondFactorController\\:\\:supportsAuthenticationMode\\(\\) has parameter \\$authenticationMode with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\SecondFactorController\\:\\:verifyGssfAction\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\SecondFactorController\\:\\:verifySmsSecondFactorAction\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\SecondFactorController\\:\\:verifySmsSecondFactorChallengeAction\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\SecondFactorController\\:\\:verifyYubiKeySecondFactorAction\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @param references unknown parameter\\: \\$authenticationMode$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$authenticationMode of method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\SecondFactorController\\:\\:buildCancelAuthenticationForm\\(\\) expects string, mixed given\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$controller of method Symfony\\\\Bundle\\\\FrameworkBundle\\\\Controller\\\\Controller\\:\\:forward\\(\\) expects string, string\\|null given\\.$#',
	'count' => 5,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$requestId of method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Monolog\\\\Logger\\\\AuthenticationLogger\\:\\:logSecondFactorAuthentication\\(\\) expects string, string\\|null given\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$requestedLoa of method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Service\\\\StepUpAuthenticationService\\:\\:resolveHighestRequiredLoa\\(\\) expects string, string\\|null given\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$secondFactor of method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Controller\\\\SecondFactorController\\:\\:selectAndRedirectTo\\(\\) expects Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\SecondFactor, mixed given\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$secondFactor of method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\ResponseContext\\:\\:saveSelectedSecondFactor\\(\\) expects Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\SecondFactor, Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\SecondFactor\\|null given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$authenticationMode of method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Monolog\\\\Logger\\\\AuthenticationLogger\\:\\:logSecondFactorAuthentication\\(\\) expects string, mixed given\\.$#',
	'count' => 3,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$spConfiguredLoas of method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Service\\\\StepUpAuthenticationService\\:\\:resolveHighestRequiredLoa\\(\\) expects array, mixed given\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$normalizedIdpSho of method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Service\\\\StepUpAuthenticationService\\:\\:resolveHighestRequiredLoa\\(\\) expects string, string\\|null given\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$whitelistService of method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Service\\\\StepUpAuthenticationService\\:\\:determineViableSecondFactors\\(\\) expects Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Service\\\\WhitelistService, object given\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Command\\\\ChooseSecondFactorCommand\\:\\:\\$secondFactors \\(array\\<Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\SecondFactor\\>\\) does not accept Doctrine\\\\Common\\\\Collections\\\\Collection\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Controller/SecondFactorController.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Symfony\\\\Component\\\\Config\\\\Definition\\\\Builder\\\\NodeDefinition\\:\\:children\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/DependencyInjection/Configuration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\DependencyInjection\\\\SurfnetStepupGatewayGatewayExtension\\:\\:load\\(\\) has parameter \\$config with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/DependencyInjection/SurfnetStepupGatewayGatewayExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\DoctrineSamlEntityRepository\\:\\:getIdentityProvider\\(\\) has parameter \\$entityId with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/DoctrineSamlEntityRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\DoctrineSamlEntityRepository\\:\\:getIdentityProvider\\(\\) should return Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\SamlEntity\\|null but returns mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/DoctrineSamlEntityRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\DoctrineSamlEntityRepository\\:\\:getServiceProvider\\(\\) has parameter \\$entityId with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/DoctrineSamlEntityRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\DoctrineSamlEntityRepository\\:\\:getServiceProvider\\(\\) should return Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\SamlEntity\\|null but returns mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/DoctrineSamlEntityRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/DoctrineSecondFactorRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\DoctrineSecondFactorRepository\\:\\:findAllByIdentityNameId\\(\\) has parameter \\$identityNameId with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/DoctrineSecondFactorRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\DoctrineSecondFactorRepository\\:\\:findAllByIdentityNameId\\(\\) should return array\\<Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\SecondFactor\\> but returns mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/DoctrineSecondFactorRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\DoctrineSecondFactorRepository\\:\\:findOneBySecondFactorId\\(\\) should return Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\SecondFactor\\|null but returns object\\|null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/DoctrineSecondFactorRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\DoctrineSecondFactorRepository\\:\\:getAllMatchingFor\\(\\) return type with generic interface Doctrine\\\\Common\\\\Collections\\\\Collection does not specify its types\\: TKey, T$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/DoctrineSecondFactorRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\DoctrineSecondFactorRepository\\:\\:getInstitutionByNameId\\(\\) has parameter \\$identityNameId with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/DoctrineSecondFactorRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\DoctrineSecondFactorRepository\\:\\:\\$secondFactorsById \\(array\\<Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\SecondFactor\\>\\) does not accept array\\<object\\|null\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/DoctrineSecondFactorRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/DoctrineSecondFactorRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$secondFactors in PHPDoc tag @var does not exist\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/DoctrineSecondFactorRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$secondFactorType on mixed\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/EnabledSecondFactorRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\EnabledSecondFactorRepository\\:\\:getAllMatchingFor\\(\\) return type with generic interface Doctrine\\\\Common\\\\Collections\\\\Collection does not specify its types\\: TKey, T$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/EnabledSecondFactorRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\EnabledSecondFactorRepository\\:\\:getInstitutionByNameId\\(\\) has parameter \\$identityNameId with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/EnabledSecondFactorRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\InstitutionConfigurationRepository\\:\\:getInstitutionConfiguration\\(\\) should return Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\InstitutionConfiguration but returns object\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/InstitutionConfigurationRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\SamlEntity\\:\\:decodeConfiguration\\(\\) return type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/SamlEntity.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\SamlEntity\\:\\:decodeConfiguration\\(\\) should return array but returns mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/SamlEntity.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\SamlEntity\\:\\:\\$configuration is never written, only read\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/SamlEntity.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\SamlEntity\\:\\:\\$entityId is never written, only read\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/SamlEntity.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\SamlEntity\\:\\:\\$id is unused\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/SamlEntity.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\SamlEntity\\:\\:\\$type is never written, only read\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/SamlEntity.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\=\\=\\= between bool and \'idp\' will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/SamlEntity.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\=\\=\\= between bool and \'sp\' will always evaluate to false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/SamlEntity.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\SamlEntityRepository\\:\\:getIdentityProvider\\(\\) has parameter \\$entityId with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/SamlEntityRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\SamlEntityRepository\\:\\:getServiceProvider\\(\\) has parameter \\$entityId with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/SamlEntityRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\SecondFactor\\:\\:\\$identityVetted has no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/SecondFactor.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\SecondFactorRepository\\:\\:getAllMatchingFor\\(\\) return type with generic interface Doctrine\\\\Common\\\\Collections\\\\Collection does not specify its types\\: TKey, T$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/SecondFactorRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\SecondFactorRepository\\:\\:getInstitutionByNameId\\(\\) has parameter \\$identityNameId with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/SecondFactorRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Argument of an invalid type mixed supplied for foreach, only iterables are supported\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/ServiceProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\ServiceProvider\\:\\:allowSsoOn2fa\\(\\) should return bool but returns mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/ServiceProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\ServiceProvider\\:\\:allowedToSetSsoCookieOn2fa\\(\\) should return bool but returns mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/ServiceProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\ServiceProvider\\:\\:determineAcsLocation\\(\\) has parameter \\$acsLocationInAuthnRequest with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/ServiceProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\ServiceProvider\\:\\:determineAcsLocation\\(\\) should return string but returns mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/ServiceProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\ServiceProvider\\:\\:determineAcsLocationForAdfs\\(\\) has parameter \\$acsLocationInAuthnRequest with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/ServiceProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function reset expects array\\|object, mixed given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/ServiceProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$pieces of function implode expects array, mixed given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/ServiceProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$str of function preg_quote expects string, mixed given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/ServiceProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$haystack of function in_array expects array, mixed given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/ServiceProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$needle of function strpos expects int\\|string, mixed given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Entity/ServiceProvider.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Exception\\\\AssertionFailedException\\:\\:__construct\\(\\) has parameter \\$code with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Exception/AssertionFailedException.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Exception\\\\AssertionFailedException\\:\\:__construct\\(\\) has parameter \\$constraints with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Exception/AssertionFailedException.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Exception\\\\AssertionFailedException\\:\\:__construct\\(\\) has parameter \\$message with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Exception/AssertionFailedException.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Exception\\\\AssertionFailedException\\:\\:__construct\\(\\) has parameter \\$propertyPath with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Exception/AssertionFailedException.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Exception\\\\AssertionFailedException\\:\\:__construct\\(\\) has parameter \\$value with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Exception/AssertionFailedException.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Exception\\\\AssertionFailedException\\:\\:getConstraints\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Exception/AssertionFailedException.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Exception\\\\AssertionFailedException\\:\\:getPropertyPath\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Exception/AssertionFailedException.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Exception\\\\AssertionFailedException\\:\\:getValue\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Exception/AssertionFailedException.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Exception\\\\AssertionFailedException\\:\\:\\$constraints has no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Exception/AssertionFailedException.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Exception\\\\AssertionFailedException\\:\\:\\$propertyPath has no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Exception/AssertionFailedException.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Exception\\\\AssertionFailedException\\:\\:\\$value has no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Exception/AssertionFailedException.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Exception\\\\InvalidArgumentException\\:\\:invalidType\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Exception/InvalidArgumentException.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Exception\\\\InvalidArgumentException\\:\\:invalidType\\(\\) has parameter \\$expectedType with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Exception/InvalidArgumentException.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Exception\\\\InvalidArgumentException\\:\\:invalidType\\(\\) has parameter \\$parameter with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Exception/InvalidArgumentException.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Exception\\\\InvalidArgumentException\\:\\:invalidType\\(\\) has parameter \\$value with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Exception/InvalidArgumentException.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Form\\\\Type\\\\AnchorType\\:\\:buildView\\(\\) has parameter \\$form with no value type specified in iterable type Symfony\\\\Component\\\\Form\\\\FormInterface\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Form/Type/AnchorType.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getValues\\(\\) on array\\<Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\SecondFactor\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Form/Type/ChooseSecondFactorType.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Monolog\\\\Formatter\\\\GelfMessageToStringFormatter\\:\\:\\$formatter\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Monolog/Formatter/GelfMessageToStringFormatter.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Monolog\\\\Formatter\\\\GelfMessageToStringFormatter\\:\\:format\\(\\) has parameter \\$record with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Monolog/Formatter/GelfMessageToStringFormatter.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Monolog\\\\Formatter\\\\GelfMessageToStringFormatter\\:\\:formatBatch\\(\\) has parameter \\$records with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Monolog/Formatter/GelfMessageToStringFormatter.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$institution on Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\SecondFactor\\|null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Monolog/Logger/AuthenticationLogger.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$secondFactorId on Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\SecondFactor\\|null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Monolog/Logger/AuthenticationLogger.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$secondFactorType on Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\SecondFactor\\|null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Monolog/Logger/AuthenticationLogger.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLoaLevel\\(\\) on Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\SecondFactor\\|null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Monolog/Logger/AuthenticationLogger.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Monolog\\\\Logger\\\\AuthenticationLogger\\:\\:log\\(\\) has parameter \\$context with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Monolog/Logger/AuthenticationLogger.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$loaLevel of method Surfnet\\\\StepupBundle\\\\Service\\\\LoaResolutionService\\:\\:getLoaByLevel\\(\\) expects int, float given\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Monolog/Logger/AuthenticationLogger.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getInResponseTo\\(\\) on SAML2\\\\XML\\\\saml\\\\SubjectConfirmationData\\|null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/AssertionAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\AssertionAdapter\\:\\:\\$assertion has no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/AssertionAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$certificateFile of method SAML2\\\\Certificate\\\\KeyLoader\\:\\:loadCertificateFile\\(\\) expects string, string\\|null given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/AssertionSigningService.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$key of method SAML2\\\\Certificate\\\\PrivateKeyLoader\\:\\:loadPrivateKey\\(\\) expects SAML2\\\\Configuration\\\\PrivateKey, mixed given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/AssertionSigningService.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Exception\\\\UnknownInResponseToException\\:\\:__construct\\(\\) has parameter \\$actual with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/Exception/UnknownInResponseToException.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Exception\\\\UnknownInResponseToException\\:\\:__construct\\(\\) has parameter \\$expected with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/Exception/UnknownInResponseToException.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Proxy\\\\ProxyStateHandler\\:\\:__construct\\(\\) has parameter \\$sessionPath with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/Proxy/ProxyStateHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Proxy\\\\ProxyStateHandler\\:\\:getAssertion\\(\\) should return string\\|null but returns mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/Proxy/ProxyStateHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Proxy\\\\ProxyStateHandler\\:\\:getAuthenticatingIdp\\(\\) should return string\\|null but returns mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/Proxy/ProxyStateHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Proxy\\\\ProxyStateHandler\\:\\:getAuthenticationModeForRequestId\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/Proxy/ProxyStateHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Proxy\\\\ProxyStateHandler\\:\\:getAuthenticationModeForRequestId\\(\\) has parameter \\$requestId with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/Proxy/ProxyStateHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Proxy\\\\ProxyStateHandler\\:\\:getGatewayRequestId\\(\\) should return string\\|null but returns mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/Proxy/ProxyStateHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Proxy\\\\ProxyStateHandler\\:\\:getPreferredLocale\\(\\) should return string\\|null but returns mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/Proxy/ProxyStateHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Proxy\\\\ProxyStateHandler\\:\\:getRelayState\\(\\) should return string\\|null but returns mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/Proxy/ProxyStateHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Proxy\\\\ProxyStateHandler\\:\\:getRequestAssertionConsumerServiceUrl\\(\\) should return string\\|null but returns mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/Proxy/ProxyStateHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Proxy\\\\ProxyStateHandler\\:\\:getRequestId\\(\\) should return string\\|null but returns mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/Proxy/ProxyStateHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Proxy\\\\ProxyStateHandler\\:\\:getRequestServiceProvider\\(\\) should return string\\|null but returns mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/Proxy/ProxyStateHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Proxy\\\\ProxyStateHandler\\:\\:getRequiredLoaIdentifier\\(\\) should return string\\|null but returns mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/Proxy/ProxyStateHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Proxy\\\\ProxyStateHandler\\:\\:getResponseAction\\(\\) should return string\\|null but returns mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/Proxy/ProxyStateHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Proxy\\\\ProxyStateHandler\\:\\:getResponseContextServiceId\\(\\) should return string\\|null but returns mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/Proxy/ProxyStateHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Proxy\\\\ProxyStateHandler\\:\\:getSchacHomeOrganization\\(\\) should return string\\|null but returns mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/Proxy/ProxyStateHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Proxy\\\\ProxyStateHandler\\:\\:getSelectedSecondFactorId\\(\\) should return string\\|null but returns mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/Proxy/ProxyStateHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Proxy\\\\ProxyStateHandler\\:\\:getSsoOn2faCookieFingerprint\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/Proxy/ProxyStateHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Proxy\\\\ProxyStateHandler\\:\\:markAuthenticationModeForRequest\\(\\) has parameter \\$authenticationMode with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/Proxy/ProxyStateHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Proxy\\\\ProxyStateHandler\\:\\:markAuthenticationModeForRequest\\(\\) has parameter \\$requestId with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/Proxy/ProxyStateHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Proxy\\\\ProxyStateHandler\\:\\:saveIdentityNameId\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/Proxy/ProxyStateHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Proxy\\\\ProxyStateHandler\\:\\:set\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/Proxy/ProxyStateHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Proxy\\\\ProxyStateHandler\\:\\:setSchacHomeOrganization\\(\\) has parameter \\$organization with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/Proxy/ProxyStateHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Proxy\\\\ProxyStateHandler\\:\\:setSelectedSecondFactorId\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/Proxy/ProxyStateHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Proxy\\\\ProxyStateHandler\\:\\:setSsoOn2faCookieFingerprint\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/Proxy/ProxyStateHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Proxy\\\\ProxyStateHandler\\:\\:\\$sessionPath has no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/Proxy/ProxyStateHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/ResponseBuilder.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\ResponseBuilder\\:\\:createNewResponse\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/ResponseBuilder.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\ResponseBuilder\\:\\:get\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/ResponseBuilder.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\ResponseBuilder\\:\\:isValidResponseStatus\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/ResponseBuilder.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\ResponseBuilder\\:\\:isValidResponseStatus\\(\\) has parameter \\$status with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/ResponseBuilder.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\ResponseBuilder\\:\\:isValidResponseSubStatus\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/ResponseBuilder.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\ResponseBuilder\\:\\:isValidResponseSubStatus\\(\\) has parameter \\$subStatus with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/ResponseBuilder.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\ResponseBuilder\\:\\:\\$response \\(SAML2\\\\Response\\) does not accept null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/ResponseBuilder.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\ResponseBuilder\\:\\:\\$responseContext \\(Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\ResponseContext\\) does not accept null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/ResponseBuilder.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\ResponseBuilder\\:\\:\\$responseContext is never read, only written\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/ResponseBuilder.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/ResponseBuilder.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method determineAcsLocation\\(\\) on Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\ServiceProvider\\|null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/ResponseContext.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method determineAcsLocationForAdfs\\(\\) on Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\ServiceProvider\\|null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/ResponseContext.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method saveXML\\(\\) on DOMDocument\\|null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/ResponseContext.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @return with type string\\|null is not subtype of native type string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/ResponseContext.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$assertionAsXmlString of method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Proxy\\\\ProxyStateHandler\\:\\:saveAssertion\\(\\) expects string, string\\|false given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/ResponseContext.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$source of method DOMDocument\\:\\:loadXML\\(\\) expects string, string\\|null given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/ResponseContext.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$str of function strtolower expects string, string\\|null given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/ResponseContext.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of method SAML2\\\\XML\\\\saml\\\\NameIDType\\:\\:setValue\\(\\) expects string, string\\|null given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/ResponseContext.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\ResponseContext\\:\\:\\$samlEntityService is never read, only written\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/ResponseContext.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\ResponseContext\\:\\:\\$targetServiceProvider \\(Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\ServiceProvider\\) in isset\\(\\) is not nullable\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/ResponseContext.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\ResponseContext\\:\\:\\$targetServiceProvider is never written, only read\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/ResponseContext.php',
];
$ignoreErrors[] = [
	'message' => '#^Unreachable statement \\- code above always terminates\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Saml/ResponseContext.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$inResponseTo of method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\AssertionAdapter\\:\\:inResponseToMatches\\(\\) expects string, string\\|null given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Service/Gateway/ConsumeAssertionService.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$requestId of method Surfnet\\\\SamlBundle\\\\Monolog\\\\SamlAuthenticationLogger\\:\\:forAuthentication\\(\\) expects string, string\\|null given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Service/Gateway/ConsumeAssertionService.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Service\\\\Gateway\\\\FailedResponseService\\:\\:createResponseFailureResponse\\(\\) has parameter \\$context with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Service/Gateway/FailedResponseService.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$requestId of method Surfnet\\\\SamlBundle\\\\Monolog\\\\SamlAuthenticationLogger\\:\\:forAuthentication\\(\\) expects string, string\\|null given\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Service/Gateway/FailedResponseService.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$loaIdentifier of method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Proxy\\\\ProxyStateHandler\\:\\:setRequiredLoaIdentifier\\(\\) expects string, string\\|null given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Service/Gateway/LoginService.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$relayState of method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Proxy\\\\ProxyStateHandler\\:\\:setRelayState\\(\\) expects string, mixed given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Service/Gateway/LoginService.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLoaLevel\\(\\) on Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\SecondFactor\\|null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Service/Gateway/RespondService.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$loaLevel of method Surfnet\\\\StepupBundle\\\\Service\\\\LoaResolutionService\\:\\:getLoaByLevel\\(\\) expects int, float given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Service/Gateway/RespondService.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$requestId of method Surfnet\\\\SamlBundle\\\\Monolog\\\\SamlAuthenticationLogger\\:\\:forAuthentication\\(\\) expects string, string\\|null given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Service/Gateway/RespondService.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$destination of method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Service\\\\ProxyResponseService\\:\\:createProxyResponse\\(\\) expects string, string\\|null given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Service/Gateway/RespondService.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Service\\\\GlobalViewParameters\\:\\:\\$locales is never read, only written\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Service/GlobalViewParameters.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Service\\\\InstitutionConfigurationService\\:\\:\\$logger\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Service/InstitutionConfigurationService.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Service\\\\InstitutionConfigurationService\\:\\:\\$repository has no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Service/InstitutionConfigurationService.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Service\\\\ProxyResponseService\\:\\:updateNewAssertionWith\\(\\) has parameter \\$eptiNameId with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Service/ProxyResponseService.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Service\\\\ProxyResponseService\\:\\:updateNewAssertionWith\\(\\) has parameter \\$internalCollabPersonId with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Service/ProxyResponseService.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of method SAML2\\\\XML\\\\saml\\\\NameIDType\\:\\:setValue\\(\\) expects string, string\\|null given\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Service/ProxyResponseService.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method saveXML\\(\\) on DOMDocument\\|null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Service/ResponseRenderingService.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Service\\\\ResponseRenderingService\\:\\:renderResponse\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Service/ResponseRenderingService.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Service\\\\ResponseRenderingService\\:\\:renderUnprocessableResponse\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Service/ResponseRenderingService.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$str of function base64_encode expects string, string\\|false given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Service/ResponseRenderingService.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Service\\\\ResponseRenderingService\\:\\:\\$logger has no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Service/ResponseRenderingService.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Service\\\\ResponseRenderingService\\:\\:\\$responseHelper has no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Service/ResponseRenderingService.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Service\\\\SamlEntityService\\:\\:getServiceProvider\\(\\) should return Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\ServiceProvider but returns Surfnet\\\\SamlBundle\\\\Entity\\\\ServiceProvider\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Service/SamlEntityService.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Service\\\\SecondFactorService\\:\\:findByUuid\\(\\) has parameter \\$uuid with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Service/SecondFactorService.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$institution on mixed\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Service/StepUpAuthenticationService.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$secondFactorId on mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Service/StepUpAuthenticationService.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Service\\\\StepUpAuthenticationService\\:\\:determineViableSecondFactors\\(\\) return type with generic interface Doctrine\\\\Common\\\\Collections\\\\Collection does not specify its types\\: TKey, T$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Service/StepUpAuthenticationService.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Service\\\\StepUpAuthenticationService\\:\\:hasNonDefaultSpConfiguredLoas\\(\\) has parameter \\$spConfiguredLoas with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Service/StepUpAuthenticationService.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Service\\\\StepUpAuthenticationService\\:\\:resolveHighestRequiredLoa\\(\\) has parameter \\$spConfiguredLoas with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Service/StepUpAuthenticationService.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Service/StepUpAuthenticationService.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Service\\\\StepUpAuthenticationService\\:\\:\\$yubikeyService \\(Surfnet\\\\StepupGateway\\\\ApiBundle\\\\Service\\\\YubikeyService\\) does not accept Surfnet\\\\StepupGateway\\\\ApiBundle\\\\Service\\\\YubikeyServiceInterface\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Service/StepUpAuthenticationService.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Sso2fa\\\\Crypto\\\\DummyCryptoHelper\\:\\:decrypt\\(\\) should return Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Sso2fa\\\\ValueObject\\\\CookieValue but returns Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Sso2fa\\\\ValueObject\\\\CookieValueInterface\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Sso2fa/Crypto/DummyCryptoHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Instantiated class ParagonIE\\\\Halite\\\\HiddenString not found\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Sso2fa/Crypto/HaliteCryptoHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Sso2fa\\\\Crypto\\\\HaliteCryptoHelper\\:\\:decrypt\\(\\) should return Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Sso2fa\\\\ValueObject\\\\CookieValue but returns Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Sso2fa\\\\ValueObject\\\\CookieValueInterface\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Sso2fa/Crypto/HaliteCryptoHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$keyMaterial of class ParagonIE\\\\Halite\\\\Symmetric\\\\EncryptionKey constructor expects ParagonIE\\\\HiddenString\\\\HiddenString, ParagonIE\\\\Halite\\\\HiddenString given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Sso2fa/Crypto/HaliteCryptoHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$plaintext of static method ParagonIE\\\\Halite\\\\Symmetric\\\\Crypto\\:\\:encrypt\\(\\) expects ParagonIE\\\\HiddenString\\\\HiddenString, ParagonIE\\\\Halite\\\\HiddenString given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Sso2fa/Crypto/HaliteCryptoHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Sso2fa\\\\Crypto\\\\HaliteCryptoHelper\\:\\:\\$encryptionKey has no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Sso2fa/Crypto/HaliteCryptoHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Sso2fa\\\\DateTime\\\\ExpirationHelper\\:\\:\\$cookieLifetime has no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Sso2fa/DateTime/ExpirationHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Sso2fa\\\\DateTime\\\\ExpirationHelper\\:\\:\\$gracePeriod has no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Sso2fa/DateTime/ExpirationHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Sso2fa\\\\Exception\\\\DecryptionFailedException\\:\\:__construct\\(\\) has parameter \\$message with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Sso2fa/Exception/DecryptionFailedException.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Sso2fa\\\\Exception\\\\EncryptionFailedException\\:\\:__construct\\(\\) has parameter \\$message with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Sso2fa/Exception/EncryptionFailedException.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Sso2fa\\\\Http\\\\CookieHelper\\:\\:createCookieWithValue\\(\\) has parameter \\$value with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Sso2fa/Http/CookieHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Sso2fa/Http/CookieHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$cookieData of method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Sso2fa\\\\Crypto\\\\CryptoHelperInterface\\:\\:decrypt\\(\\) expects string, bool\\|float\\|int\\|string\\|null given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Sso2fa/Http/CookieHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$encryptedCookieValue of method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Sso2fa\\\\Http\\\\CookieHelper\\:\\:hashFingerprint\\(\\) expects string, bool\\|float\\|int\\|string\\|null given\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Sso2fa/Http/CookieHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Sso2fa\\\\ValueObject\\\\CookieType\\:\\:\\$type has no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Sso2fa/ValueObject/CookieType.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'authenticationTime\' on mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Sso2fa/ValueObject/CookieValue.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'identityId\' on mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Sso2fa/ValueObject/CookieValue.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'loa\' on mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Sso2fa/ValueObject/CookieValue.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'tokenId\' on mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Sso2fa/ValueObject/CookieValue.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot cast mixed to float\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Sso2fa/ValueObject/CookieValue.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Sso2fa\\\\ValueObject\\\\CookieValue\\:\\:serialize\\(\\) should return string but returns string\\|false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Sso2fa/ValueObject/CookieValue.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Sso2fa\\\\ValueObject\\\\CookieValue\\:\\:\\$authenticationTime has no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Sso2fa/ValueObject/CookieValue.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Sso2fa\\\\ValueObject\\\\CookieValue\\:\\:\\$identityId has no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Sso2fa/ValueObject/CookieValue.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Sso2fa\\\\ValueObject\\\\CookieValue\\:\\:\\$loa has no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Sso2fa/ValueObject/CookieValue.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Sso2fa\\\\ValueObject\\\\CookieValue\\:\\:\\$tokenId has no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Sso2fa/ValueObject/CookieValue.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Twig\\\\Extensions\\\\Extension\\\\SecondFactorType\\:\\:getName\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Twig/Extensions/Extension/SecondFactorType.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Twig\\\\Extensions\\\\Extension\\\\SecondFactorType\\:\\:getSecondFactorTypeLogoByIdentifier\\(\\) has parameter \\$secondFactorType with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Twig/Extensions/Extension/SecondFactorType.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Twig\\\\Extensions\\\\Extension\\\\SecondFactorType\\:\\:translateSecondFactorType\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Twig/Extensions/Extension/SecondFactorType.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Twig\\\\Extensions\\\\Extension\\\\SecondFactorType\\:\\:translateSecondFactorType\\(\\) has parameter \\$secondFactorType with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Twig/Extensions/Extension/SecondFactorType.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Twig\\\\Extensions\\\\Extension\\\\SecondFactorType\\:\\:\\$logoFormat has no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/GatewayBundle/Twig/Extensions/Extension/SecondFactorType.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method object\\:\\:getAuthenticationModeForRequestId\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Controller/SamlProxyController.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method object\\:\\:getDestination\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Controller/SamlProxyController.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method object\\:\\:getResponseContextServiceId\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Controller/SamlProxyController.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method object\\:\\:notice\\(\\)\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Controller/SamlProxyController.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method saveXML\\(\\) on DOMDocument\\|null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Controller/SamlProxyController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Controller\\\\SamlProxyController\\:\\:getGsspConsumeAssertionService\\(\\) should return Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Service\\\\Gateway\\\\ConsumeAssertionService but returns object\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Controller/SamlProxyController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Controller\\\\SamlProxyController\\:\\:getGsspLoginService\\(\\) should return Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Service\\\\Gateway\\\\LoginService but returns object\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Controller/SamlProxyController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Controller\\\\SamlProxyController\\:\\:getGsspSecondFactorVerificationService\\(\\) should return Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Service\\\\Gateway\\\\SecondFactorVerificationService but returns object\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Controller/SamlProxyController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Controller\\\\SamlProxyController\\:\\:getProxyResponseFactory\\(\\) should return Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Saml\\\\ProxyResponseFactory but returns object\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Controller/SamlProxyController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Controller\\\\SamlProxyController\\:\\:getResponseContext\\(\\) has parameter \\$mode with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Controller/SamlProxyController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Controller\\\\SamlProxyController\\:\\:getResponseContext\\(\\) should return Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\ResponseContext but returns object\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Controller/SamlProxyController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Controller\\\\SamlProxyController\\:\\:getServiceProvider\\(\\) should return Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\ServiceProvider but returns Surfnet\\\\SamlBundle\\\\Entity\\\\ServiceProvider\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Controller/SamlProxyController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$id of method Symfony\\\\Bundle\\\\FrameworkBundle\\\\Controller\\\\Controller\\:\\:get\\(\\) expects string, string\\|null given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Controller/SamlProxyController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$originalRequestId of method Surfnet\\\\StepupGateway\\\\SecondFactorOnlyBundle\\\\Adfs\\\\ResponseHelper\\:\\:isAdfsResponse\\(\\) expects string, string\\|null given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Controller/SamlProxyController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$serviceProvider of method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Controller\\\\SamlProxyController\\:\\:getServiceProvider\\(\\) expects string, string\\|null given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Controller/SamlProxyController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$str of function base64_encode expects string, string\\|false given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Controller/SamlProxyController.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$logger of method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\ServiceProvider\\:\\:determineAcsLocation\\(\\) expects Psr\\\\Log\\\\LoggerInterface\\|null, object given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Controller/SamlProxyController.php',
];
$ignoreErrors[] = [
	'message' => '#^Instantiated class Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\DependencyInjection\\\\Compiler\\\\InvalidConfigurationException not found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/DependencyInjection/Compiler/ViewConfigCollectionPass.php',
];
$ignoreErrors[] = [
	'message' => '#^Throwing object of an unknown class Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\DependencyInjection\\\\Compiler\\\\InvalidConfigurationException\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/DependencyInjection/Compiler/ViewConfigCollectionPass.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Symfony\\\\Component\\\\Config\\\\Definition\\\\Builder\\\\NodeDefinition\\:\\:children\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/DependencyInjection/Configuration.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method scalarNode\\(\\) on Symfony\\\\Component\\\\Config\\\\Definition\\\\Builder\\\\NodeParentInterface\\|null\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/DependencyInjection/Configuration.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$rootNode of method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\DependencyInjection\\\\Configuration\\:\\:addProvidersSection\\(\\) expects Symfony\\\\Component\\\\Config\\\\Definition\\\\Builder\\\\ArrayNodeDefinition, Symfony\\\\Component\\\\Config\\\\Definition\\\\Builder\\\\NodeDefinition given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/DependencyInjection/Configuration.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$rootNode of method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\DependencyInjection\\\\Configuration\\:\\:addRoutesSection\\(\\) expects Symfony\\\\Component\\\\Config\\\\Definition\\\\Builder\\\\ArrayNodeDefinition, Symfony\\\\Component\\\\Config\\\\Definition\\\\Builder\\\\NodeDefinition given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/DependencyInjection/Configuration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\DependencyInjection\\\\SurfnetStepupGatewaySamlStepupProviderExtension\\:\\:buildHostedEntityDefinition\\(\\) has parameter \\$configuration with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/DependencyInjection/SurfnetStepupGatewaySamlStepupProviderExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\DependencyInjection\\\\SurfnetStepupGatewaySamlStepupProviderExtension\\:\\:buildHostedEntityDefinition\\(\\) has parameter \\$routes with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/DependencyInjection/SurfnetStepupGatewaySamlStepupProviderExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\DependencyInjection\\\\SurfnetStepupGatewaySamlStepupProviderExtension\\:\\:createHostedDefinitions\\(\\) has parameter \\$configuration with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/DependencyInjection/SurfnetStepupGatewaySamlStepupProviderExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\DependencyInjection\\\\SurfnetStepupGatewaySamlStepupProviderExtension\\:\\:createHostedDefinitions\\(\\) has parameter \\$routes with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/DependencyInjection/SurfnetStepupGatewaySamlStepupProviderExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\DependencyInjection\\\\SurfnetStepupGatewaySamlStepupProviderExtension\\:\\:createMetadataDefinition\\(\\) has parameter \\$configuration with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/DependencyInjection/SurfnetStepupGatewaySamlStepupProviderExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\DependencyInjection\\\\SurfnetStepupGatewaySamlStepupProviderExtension\\:\\:createMetadataDefinition\\(\\) has parameter \\$routes with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/DependencyInjection/SurfnetStepupGatewaySamlStepupProviderExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\DependencyInjection\\\\SurfnetStepupGatewaySamlStepupProviderExtension\\:\\:createRemoteDefinition\\(\\) has parameter \\$configuration with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/DependencyInjection/SurfnetStepupGatewaySamlStepupProviderExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\DependencyInjection\\\\SurfnetStepupGatewaySamlStepupProviderExtension\\:\\:createRouteConfig\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/DependencyInjection/SurfnetStepupGatewaySamlStepupProviderExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\DependencyInjection\\\\SurfnetStepupGatewaySamlStepupProviderExtension\\:\\:createRouteConfig\\(\\) has parameter \\$provider with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/DependencyInjection/SurfnetStepupGatewaySamlStepupProviderExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\DependencyInjection\\\\SurfnetStepupGatewaySamlStepupProviderExtension\\:\\:createRouteConfig\\(\\) has parameter \\$routeName with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/DependencyInjection/SurfnetStepupGatewaySamlStepupProviderExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\DependencyInjection\\\\SurfnetStepupGatewaySamlStepupProviderExtension\\:\\:load\\(\\) has parameter \\$configs with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/DependencyInjection/SurfnetStepupGatewaySamlStepupProviderExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\DependencyInjection\\\\SurfnetStepupGatewaySamlStepupProviderExtension\\:\\:loadProviderConfiguration\\(\\) has parameter \\$configuration with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/DependencyInjection/SurfnetStepupGatewaySamlStepupProviderExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\DependencyInjection\\\\SurfnetStepupGatewaySamlStepupProviderExtension\\:\\:loadProviderConfiguration\\(\\) has parameter \\$provider with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/DependencyInjection/SurfnetStepupGatewaySamlStepupProviderExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\DependencyInjection\\\\SurfnetStepupGatewaySamlStepupProviderExtension\\:\\:loadProviderConfiguration\\(\\) has parameter \\$routes with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/DependencyInjection/SurfnetStepupGatewaySamlStepupProviderExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @return with type Symfony\\\\Component\\\\DependencyInjection\\\\Definition is incompatible with native type void\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/DependencyInjection/SurfnetStepupGatewaySamlStepupProviderExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$boolean of method Symfony\\\\Component\\\\DependencyInjection\\\\Definition\\:\\:setPublic\\(\\) expects bool, string given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/DependencyInjection/SurfnetStepupGatewaySamlStepupProviderExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Exception\\\\InvalidArgumentException\\:\\:invalidType\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Exception/InvalidArgumentException.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Exception\\\\InvalidArgumentException\\:\\:invalidType\\(\\) has parameter \\$expectedType with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Exception/InvalidArgumentException.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Exception\\\\InvalidArgumentException\\:\\:invalidType\\(\\) has parameter \\$parameter with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Exception/InvalidArgumentException.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Exception\\\\InvalidArgumentException\\:\\:invalidType\\(\\) has parameter \\$value with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Exception/InvalidArgumentException.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Exception\\\\UnknownProviderException\\:\\:create\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Exception/UnknownProviderException.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Exception\\\\UnknownProviderException\\:\\:create\\(\\) has parameter \\$unknownProvider with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Exception/UnknownProviderException.php',
];
$ignoreErrors[] = [
	'message' => '#^Unsafe usage of new static\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Exception/UnknownProviderException.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Provider\\\\AllowedServiceProviders\\:\\:__construct\\(\\) has parameter \\$allowed with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Provider/AllowedServiceProviders.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Provider\\\\AllowedServiceProviders\\:\\:\\$allowed type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Provider/AllowedServiceProviders.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$childNodes on DOMElement\\|null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Provider/Metadata.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Provider\\\\Metadata\\:\\:__toString\\(\\) should return string but returns string\\|false\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Provider/Metadata.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Provider\\\\Metadata\\:\\:getRootDomElement\\(\\) should return DOMElement but returns DOMElement\\|null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Provider/Metadata.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always false\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Provider/Metadata.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Provider\\\\Provider\\:\\:__construct\\(\\) has parameter \\$name with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Provider/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var has invalid value \\(\\[\\]Provider\\)\\: Unexpected token "\\[", expected type at offset 16$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Provider/ProviderRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$providerName of method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Provider\\\\ProviderRepository\\:\\:has\\(\\) expects string, mixed given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Provider/ProviderRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\.\\.\\.\\$values of function sprintf expects bool\\|float\\|int\\|string\\|null, mixed given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Provider/ProviderRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Provider\\\\ProviderRepository\\:\\:\\$providers has no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Provider/ProviderRepository.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Provider\\\\ViewConfig\\:\\:__construct\\(\\) has parameter \\$title with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Provider/ViewConfig.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Provider\\\\ViewConfig\\:\\:getTitle\\(\\) should return string but returns mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Provider/ViewConfig.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Provider\\\\ViewConfig\\:\\:getTranslation\\(\\) has parameter \\$translations with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Provider/ViewConfig.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var has invalid value \\(\\)\\: Unexpected token "\\\\n     ", expected type at offset 15$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Provider/ViewConfig.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Provider\\\\ViewConfig\\:\\:\\$requestStack has no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Provider/ViewConfig.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Provider\\\\ViewConfig\\:\\:\\$title type has no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Provider/ViewConfig.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of method SAML2\\\\XML\\\\saml\\\\NameIDType\\:\\:setValue\\(\\) expects string, string\\|null given\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Saml/ProxyResponseFactory.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Saml\\\\ProxyResponseFactory\\:\\:\\$logger is never read, only written\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Saml/ProxyResponseFactory.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Saml\\\\StateHandler\\:\\:__construct\\(\\) has parameter \\$provider with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Saml/StateHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Saml\\\\StateHandler\\:\\:getSubject\\(\\) should return string but returns mixed\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Saml/StateHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Saml\\\\StateHandler\\:\\:markRequestAsSecondFactorVerification\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Saml/StateHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Saml\\\\StateHandler\\:\\:set\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Saml/StateHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$str of function strtolower expects string, mixed given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Saml/StateHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\.\\.\\.\\$values of function sprintf expects bool\\|float\\|int\\|string\\|null, mixed given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Saml/StateHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getValue\\(\\) on SAML2\\\\XML\\\\saml\\\\NameID\\|null\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Service/Gateway/ConsumeAssertionService.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Service\\\\Gateway\\\\ConsumeAssertionService\\:\\:getServiceProvider\\(\\) should return Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\ServiceProvider but returns Surfnet\\\\SamlBundle\\\\Entity\\\\ServiceProvider\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Service/Gateway/ConsumeAssertionService.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$inResponseTo of method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\AssertionAdapter\\:\\:inResponseToMatches\\(\\) expects string, string\\|null given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Service/Gateway/ConsumeAssertionService.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$requestId of method Surfnet\\\\SamlBundle\\\\Monolog\\\\SamlAuthenticationLogger\\:\\:forAuthentication\\(\\) expects string, string\\|null given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Service/Gateway/ConsumeAssertionService.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$serviceProvider of method Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Service\\\\Gateway\\\\ConsumeAssertionService\\:\\:getServiceProvider\\(\\) expects string, string\\|null given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Service/Gateway/ConsumeAssertionService.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\SamlStepupProviderBundle\\\\Service\\\\Gateway\\\\ConsumeAssertionService\\:\\:\\$handledRequestId has no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Service/Gateway/ConsumeAssertionService.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Service/Gateway/LoginService.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$relayState of method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Proxy\\\\ProxyStateHandler\\:\\:setRelayState\\(\\) expects string, mixed given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Service/Gateway/LoginService.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$url of method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Proxy\\\\ProxyStateHandler\\:\\:setRequestAssertionConsumerServiceUrl\\(\\) expects string, string\\|null given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Service/Gateway/LoginService.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\.\\.\\.\\$values of function sprintf expects bool\\|float\\|int\\|string\\|null, mixed given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Service/Gateway/LoginService.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$originalRequestId of method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Proxy\\\\ProxyStateHandler\\:\\:setRequestId\\(\\) expects string, string\\|null given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Service/Gateway/SecondFactorVerificationService.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$requestId of method Surfnet\\\\SamlBundle\\\\Monolog\\\\SamlAuthenticationLogger\\:\\:forAuthentication\\(\\) expects string, string\\|null given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Service/Gateway/SecondFactorVerificationService.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\.\\.\\.\\$values of function sprintf expects bool\\|float\\|int\\|string\\|null, mixed given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SamlStepupProviderBundle/Service/Gateway/SecondFactorVerificationService.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SecondFactorOnlyBundle\\\\Adfs\\\\Exception\\\\AcsLocationNotAllowedException\\:\\:__construct\\(\\) has parameter \\$requestedAcsLocation with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Adfs/Exception/AcsLocationNotAllowedException.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Surfnet\\\\StepupGateway\\\\SecondFactorOnlyBundle\\\\Adfs\\\\RequestHelper\\:\\:\\$requiredParams has no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Adfs/RequestHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$authMethod of static method Surfnet\\\\StepupGateway\\\\SecondFactorOnlyBundle\\\\Adfs\\\\ValueObject\\\\Response\\:\\:fromValues\\(\\) expects string, mixed given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Adfs/ResponseHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$context of static method Surfnet\\\\StepupGateway\\\\SecondFactorOnlyBundle\\\\Adfs\\\\ValueObject\\\\Response\\:\\:fromValues\\(\\) expects string, mixed given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Adfs/ResponseHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\.\\.\\.\\$values of function sprintf expects bool\\|float\\|int\\|string\\|null, mixed given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Adfs/ResponseHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SecondFactorOnlyBundle\\\\Adfs\\\\StateHandler\\:\\:set\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Adfs/StateHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method object\\:\\:notice\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Controller/MetadataController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SecondFactorOnlyBundle\\\\Controller\\\\MetadataController\\:\\:metadataAction\\(\\) has no return type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Controller/MetadataController.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method object\\:\\:forAuthentication\\(\\)\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Controller/SecondFactorOnlyController.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method object\\:\\:getResponseAsXML\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Controller/SecondFactorOnlyController.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method object\\:\\:handleSsoOn2faCookieStorage\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Controller/SecondFactorOnlyController.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method object\\:\\:notice\\(\\)\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Controller/SecondFactorOnlyController.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method object\\:\\:renderResponse\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Controller/SecondFactorOnlyController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SecondFactorOnlyBundle\\\\Controller\\\\SecondFactorOnlyController\\:\\:getResponseContext\\(\\) should return Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\ResponseContext but returns object\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Controller/SecondFactorOnlyController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SecondFactorOnlyBundle\\\\Controller\\\\SecondFactorOnlyController\\:\\:getSecondFactorAdfsService\\(\\) should return Surfnet\\\\StepupGateway\\\\SecondFactorOnlyBundle\\\\Service\\\\Gateway\\\\AdfsService but returns object\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Controller/SecondFactorOnlyController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SecondFactorOnlyBundle\\\\Controller\\\\SecondFactorOnlyController\\:\\:getSecondFactorLoginService\\(\\) should return Surfnet\\\\StepupGateway\\\\SecondFactorOnlyBundle\\\\Service\\\\Gateway\\\\LoginService but returns object\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Controller/SecondFactorOnlyController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SecondFactorOnlyBundle\\\\Controller\\\\SecondFactorOnlyController\\:\\:getSecondFactorRespondService\\(\\) should return Surfnet\\\\StepupGateway\\\\SecondFactorOnlyBundle\\\\Service\\\\Gateway\\\\RespondService but returns object\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Controller/SecondFactorOnlyController.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Symfony\\\\Component\\\\Config\\\\Definition\\\\Builder\\\\NodeDefinition\\:\\:children\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/DependencyInjection/Configuration.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SecondFactorOnlyBundle\\\\DependencyInjection\\\\SurfnetStepupGatewaySecondFactorOnlyExtension\\:\\:load\\(\\) has parameter \\$config with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/DependencyInjection/SurfnetStepupGatewaySecondFactorOnlyExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SecondFactorOnlyBundle\\\\DependencyInjection\\\\SurfnetStepupGatewaySecondFactorOnlyExtension\\:\\:replaceLoaAliasConfig\\(\\) has parameter \\$config with no value type specified in iterable type array\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/DependencyInjection/SurfnetStepupGatewaySecondFactorOnlyExtension.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SecondFactorOnlyBundle\\\\Entity\\\\SecondFactorOnlySamlEntityRepositoryDecorator\\:\\:getIdentityProvider\\(\\) has parameter \\$entityId with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Entity/SecondFactorOnlySamlEntityRepositoryDecorator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SecondFactorOnlyBundle\\\\Entity\\\\SecondFactorOnlySamlEntityRepositoryDecorator\\:\\:getServiceProvider\\(\\) has parameter \\$entityId with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Entity/SecondFactorOnlySamlEntityRepositoryDecorator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SecondFactorOnlyBundle\\\\Saml\\\\ResponseFactory\\:\\:addAuthenticationStatementTo\\(\\) has parameter \\$authnContextClassRef with no type specified\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Saml/ResponseFactory.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of method SAML2\\\\XML\\\\saml\\\\NameIDType\\:\\:setValue\\(\\) expects string, string\\|null given\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Saml/ResponseFactory.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$authnContextClassRef of method Surfnet\\\\StepupGateway\\\\SecondFactorOnlyBundle\\\\Saml\\\\ResponseFactory\\:\\:createNewAssertion\\(\\) expects string, string\\|null given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Saml/ResponseFactory.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$originalRequestId of method Surfnet\\\\StepupGateway\\\\SecondFactorOnlyBundle\\\\Adfs\\\\ResponseHelper\\:\\:isAdfsResponse\\(\\) expects string, string\\|null given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Service/Gateway/AdfsService.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$loaId of method Surfnet\\\\StepupGateway\\\\SecondFactorOnlyBundle\\\\Service\\\\LoaResolutionService\\:\\:resolve\\(\\) expects string, string\\|null given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Service/Gateway/LoginService.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$relayState of method Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Saml\\\\Proxy\\\\ProxyStateHandler\\:\\:setRelayState\\(\\) expects string, mixed given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Service/Gateway/LoginService.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getLoaLevel\\(\\) on Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\SecondFactor\\|null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Service/Gateway/RespondService.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$loa of method Surfnet\\\\StepupGateway\\\\SecondFactorOnlyBundle\\\\Service\\\\LoaAliasLookupService\\:\\:findAliasByLoa\\(\\) expects Surfnet\\\\StepupBundle\\\\Value\\\\Loa, Surfnet\\\\StepupBundle\\\\Value\\\\Loa\\|null given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Service/Gateway/RespondService.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$loaLevel of method Surfnet\\\\StepupBundle\\\\Service\\\\LoaResolutionService\\:\\:getLoaByLevel\\(\\) expects int, float given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Service/Gateway/RespondService.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$requestId of method Surfnet\\\\SamlBundle\\\\Monolog\\\\SamlAuthenticationLogger\\:\\:forAuthentication\\(\\) expects string, string\\|null given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Service/Gateway/RespondService.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$destination of method Surfnet\\\\StepupGateway\\\\SecondFactorOnlyBundle\\\\Saml\\\\ResponseFactory\\:\\:createSecondFactorOnlyResponse\\(\\) expects string, string\\|null given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Service/Gateway/RespondService.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$secondFactor of method Surfnet\\\\StepupGateway\\\\SecondFactorOnlyBundle\\\\Service\\\\Gateway\\\\ResponseValidator\\:\\:validate\\(\\) expects Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\SecondFactor, Surfnet\\\\StepupGateway\\\\GatewayBundle\\\\Entity\\\\SecondFactor\\|null given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Service/Gateway/RespondService.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$authnContextClassRef of method Surfnet\\\\StepupGateway\\\\SecondFactorOnlyBundle\\\\Saml\\\\ResponseFactory\\:\\:createSecondFactorOnlyResponse\\(\\) expects string\\|null, bool\\|string given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Service/Gateway/RespondService.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getValue\\(\\) on SAML2\\\\XML\\\\saml\\\\NameID\\|null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Service/Gateway/ResponseValidator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Surfnet\\\\StepupGateway\\\\SecondFactorOnlyBundle\\\\Service\\\\LoaResolutionService\\:\\:resolve\\(\\) should return string but returns string\\|true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Service/LoaResolutionService.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$loaIdentifier of method Surfnet\\\\StepupBundle\\\\Service\\\\LoaResolutionService\\:\\:hasLoa\\(\\) expects string, string\\|true given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/../../src/Surfnet/StepupGateway/SecondFactorOnlyBundle/Service/LoaResolutionService.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
