<?php

use Symfony\Component\HttpFoundation\Request;

$loader = require __DIR__.'/../app/autoload.php';

$request = Request::createFromGlobals();

if (!isset($_SERVER['APP_ENV'])) {
    $kernel = new AppKernel('prod', false);
} else {
    // Read the env from APP_ENV
    $env = $_SERVER['APP_ENV'];

    // Enable debug mode if not prod mode
    $debug = false;
    if ($env != 'prod') {
        $debug = true;
    }
    $kernel = new AppKernel($env, $debug);
}
$kernel->boot();
$trustedProxies = $kernel->getContainer()->getParameter('trusted_proxies');
Request::setTrustedProxies($trustedProxies, Request::HEADER_X_FORWARDED_ALL);

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
