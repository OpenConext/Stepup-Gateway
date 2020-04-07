<?php
use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\Request;

require __DIR__ . '/../app/autoload.php';
if (PHP_VERSION_ID < 70000) {
    include_once __DIR__ . '/../app/bootstrap.php.cache';
}

Debug::enable();

$env = $_SERVER['APP_ENV'] ? $_SERVER['APP_ENV'] : 'dev';

$kernel = new AppKernel($env, true);
if (PHP_VERSION_ID < 70000) {
    $kernel->loadClassCache();
}

$request = Request::createFromGlobals();

$kernel->boot();

$trustedProxies = $kernel->getContainer()->getParameter('trusted_proxies');
Request::setTrustedProxies($trustedProxies, Request::HEADER_X_FORWARDED_ALL);

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
