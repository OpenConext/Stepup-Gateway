<?php

use Symfony\Component\HttpFoundation\Request;

$loader = require __DIR__.'/../app/autoload.php';

$request = Request::createFromGlobals();

$kernel = new AppKernel('prod', false);
$kernel->boot();

$trustedProxies = $kernel->getContainer()->getParameter('trusted_proxies');
Request::setTrustedProxies($trustedProxies, Request::HEADER_X_FORWARDED_ALL);

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
