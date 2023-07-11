<?php

use App\Kernel;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__).'/config/bootstrap.php';

if ($_SERVER['APP_DEBUG']) {
    umask(0000);

    Debug::enable();
}

if ($trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? false) {
    Request::setTrustedProxies(explode(',', $trustedProxies), Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST);
}

if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? false) {
    Request::setTrustedHosts([$trustedHosts]);
}

// To run behat tests in smoketest mode, the app env needs to be 'dev' or 'test'
// and the user agent needs to be that of the behat guzzle client.
$isTestOrDev = ($_SERVER['APP_ENV'] === 'dev' || $_SERVER['APP_ENV'] === 'test');
if ($isTestOrDev && $_SERVER['HTTP_USER_AGENT'] === 'Symfony BrowserKit') {
    $_SERVER['APP_ENV'] = 'smoketest';
}

$kernel = new Kernel('smoketest', (bool) $_SERVER['APP_DEBUG']);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
