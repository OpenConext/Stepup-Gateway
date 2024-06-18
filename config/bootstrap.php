<?php

use Symfony\Component\Yaml\Yaml;

require dirname(__DIR__).'/vendor/autoload.php';
$parametersPath = dirname(__DIR__).'/config/openconext/parameters.yaml';
$parameters = Yaml::parseFile($parametersPath);
$parameters = $parameters['parameters'];
$requiredParameters = ['app_env', 'app_debug', 'app_secret'];


// Test if required parameters are set
if (0 !== count(array_diff($requiredParameters, array_keys($parameters)))) {
    throw new RuntimeException(sprintf(
        'Required parameters are not configured, required params are: %s, configure them in %s',
        implode(', ', $requiredParameters),
        $parametersPath
    ));
}

$_SERVER['APP_ENV'] = $parameters['app_env'];
$_SERVER['APP_DEBUG'] = $parameters['app_debug'];
$_SERVER['APP_SECRET'] = $parameters['app_secret'];

// Allow the application environment (dev/test/prod) to change via the APP_ENV environment variable.
if (array_key_exists('APP_ENV', $_ENV)) {
    $_SERVER['APP_ENV'] = $_ENV['APP_ENV'];
}

filter_var($_SERVER['APP_DEBUG'], FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
