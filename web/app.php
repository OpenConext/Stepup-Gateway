<?php
use Symfony\Component\HttpFoundation\Request;

$loader = require __DIR__.'/../app/autoload.php';
$request = Request::createFromGlobals();

$env = $_SERVER['APP_ENV'] ? $_SERVER['prod'] :
$kernel = new AppKernel($_SERVER['APP_ENV'], true);
$kernel->boot();

$trustedProxies = $kernel->getContainer()->getParameter('trusted_proxies');
Request::setTrustedProxies($trustedProxies, Request::HEADER_X_FORWARDED_ALL);

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
