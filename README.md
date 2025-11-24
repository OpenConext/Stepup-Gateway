Step-up Gateway
===============

[![Build status](https://github.com/OpenConext/Stepup-Gateway/actions/workflows/test-integration.yml/badge.svg)](https://github.com/OpenConext/Stepup-Gateway/actions/workflows/test-integration.yml)

 [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/OpenConext/Stepup-Gateway/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/OpenConext/Stepup-Gateway/?branch=main) 
 
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/6204fffb-6333-4f78-9620-5a5bb09dfab2/mini.png)](https://insight.sensiolabs.com/projects/6204fffb-6333-4f78-9620-5a5bb09dfab2)

This component is part of "Step-up Authentication as-a Service". See [Stepup-Deploy](https://github.com/OpenConext/Stepup-Deploy) for an overview and installation instructions for a complete Stepup system, including this component. The requirements and installation instructions below cover this component only.

## Requirements

 * PHP 8.2
 * [Composer](https://getcomposer.org/)
 * A web server (Apache, Nginx)
 * MariaDB 5.5+ (MySQL should work as well)
 * syslog (or change the logging configuration in /app/config/logging.yml)

## Installation

* Clone the repository or download the archive to a directory. 
* Install the dependencies & build frontend by running `composer install && composer frontend-install`.
* Run `composer check` to ensure everything is installed correctly.

The Gateway is configured to only accept connections over SSL. Disable this under `nelmio_security` in `config.yml` or run the web server using a (self-signed) certificate.

## Developer tips

### Mock Yubikey service
If you are not in possession of an actual Yubikey device, using the Mock Yubikey service might prove useful. This
mock service was created for end to end test purposes, but could be utilized in this situation. To use the mock service:

1. Update your `src/Surfnet/StepupGateway/ApiBundle/Resources/config/services.yml`
2. Find the `surfnet_gateway_api.service.yubikey` service
3. Update the service definition to point to this class: `class: Surfnet\StepupGateway\ApiBundle\Tests\TestDouble\Service\YubikeyService` 
4. Do not commit/push this change!

### Running Behat tests
#### .env settings in devconf/stepup

```
APP_ENV=smoketest
STEPUP_VERSION:test

AZUREMFA_PHP_IMAGE=php82-apache2-node20-composer2:latest
DEMOGSSP_PHP_IMAGE=php82-apache2-node20-composer2:latest
#GATEWAY_PHP_IMAGE=php72-apache2-node14-composer2:latest
MIDDLEWARE_PHP_IMAGE=php82-apache2-node20-composer2:latest
RA_PHP_IMAGE=php82-apache2-node20-composer2:latest
SELFSERVICE_PHP_IMAGE=php82-apache2-node20-composer2:latest
TIQR_PHP_IMAGE=php82-apache2-node20-composer2:latest
WEBAUTHN_PHP_IMAGE=php82-apache2-node20-composer2:latest
```

#### Start devconf

`./start-dev-env.sh gateway:../../OpenConext-stepup/Stepup-Gateway`

#### Run the Gateway Behat tests

```
$ docker exec -it stepup-gateway-1 bash
$ composer behat
```            

#### New addition: run a specific test

The composer behat command can now also pipe the arguments to the behat call. So now, for example you can do:

```
$ composer behat tests/features/self-asserted.feature
$ composer behat tests/features/sso.feature:34
$ composer behat -- -vv
$ composer behat -- --stop-on-failure
```

From the doc-root:
```bash
$ cd ci/docker
$ ./init.sh
# If this fails, possibly you'll have to make the app/files folder writable for your docker user
$ docker-compose exec -T php-fpm.stepup.example.com bash -c 'composer behat'
```

Be aware! Make sure any parameter changes are also applied in the `ci/config/parameters.yaml`.

When finished working on behat tests, stop the containers (`docker-compose down`), and restart your stepup-vm.

Having them running simultaneous might cause hostname issues, but your mileage may vary.

## Release strategy
Please read: https://github.com/OpenConext/Stepup-Deploy/wiki/Release-Management fro more information on the release strategy used in Stepup projects.

## Documentation

Documentation specific to this component is located in the [docs](./docs) directory in this repository:
- [Gateway API](./docs/GatewayAPI.md)
- [Gateway State](./docs/GatewayState.md) (diagrams of main flows and user session data)
- [SAML Proxy](./docs/SAMLProxy.md) with:
  - [GSSP](./docs/GSSP.md)
  - [SFO](./docs/SFO.md)
- [SAML Example messages](./docs/ExampleSAMLMessages.md)

Documentation for the Stepup system can be found in the
- [Stepup-Deploy](https://github.com/OpenConext/Stepup-Deploy) repository;
- and in the [wiki](https://github.com/OpenConext/Stepup-Deploy/wiki) of the Stepup-Deploy repository.
