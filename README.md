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

### Translations
Gateway uses the default symfony translations bundle. To extract / update the translations, run:  
`./bin/extract-translations.sh`  
Then modify and commit the changes.

### Frontend Development

#### Template Preview
For the purpose of testing the frontend templates a preview route is available to view all templates with mock data.

Available in `smoketest` environment only.  
Visit: `https://gateway.dev.openconext.local/preview/`

## Testing

There are three test suites:
1. PHPUnit unit tests. Uses the Symfony "test" environment.
2. PHPUnit Functional tests. Uses the Symfony "functional" environment.
3. Behat Functional tests. These require a running Stepup OpenConext-devconf in test (smoketest) environment 

### PHPUnit unit and functional tests

The PHPUnit tests are tests that run in-process. They need no web server, no database server and no OpenConext-devconf docker composer environment running.

#### PHPUnit unit tests

First install the dependencies:
```
$ composer install
```

Then run the unit tests using:
```
$ composer test
```

#### PHPUnit Functional tests

The functional tests are PHPUnit tests that send SAML messages to the Gateway at the HTTP level, in-process. They need 
no web server, no database server and no devconf Docker running. 

The functional tests run standalone in PHP cli with the `pdo_sqlite` extension as the only extra requirement, used for
creating the gateway database fixture (`tests/Functional/Fixtures/devconf/database.sql`) instead of mariadb.

First install the dependencies:
```
$ composer install
```

and 

```
$ composer frontend-install
```

because the tests do need the compiled web assets.

Run the tests:
```
$ composer test-functional
```
or, without composer:

```
$ ./ci/qa/phpunit-functional
```

Gateway logs of the tests are written to `var/log/functional.log`.

See [docs/FUNCTIONAL_TESTING.md](./docs/FUNCTIONAL_TESTING.md) for details.

### Behat functional tests

These tests need [OpenConext-devconf/stepup](https://github.com/OpenConext/OpenConext-devconf/tree/main/stepup) to be running in TEST mode.

First install the Stepup-Gateway dependencies:
```
$ composer install
```

and

```
$ composer frontend-install
```
 
Checkout `OpenConext-devonf` somewhere or use an existing checkout:

```
$ cd .../OpenConext-devonf/stepup
```

Copy `stepup/.env.test` to `stepup/.env` to put the devconf environment in test (smoketest) mode. This adds additional containers (behat, chrome) for running tests and uses a different set of images for the Stepup services. A different database is also used, so existing data in your devconf is retained when switching to test. 

```
$ cp .env.test .env
```

Stop all running containers before switching to test mode.

```
$ ./stop-dev-env.sh
```

Then start the devconf environment, pointing to the directory with your Stepup-Gateway checkout. This creates a container with your code mounted in the container.

```
$ ./start-dev-env.sh gateway:~/.../OpenConext-stepup/Stepup-Gateway`
```

The gateway behat tests are run from the *gateway* container:

```
docker exec -it stepup-gateway-1 ./vendor/bin/behat --config=ci/qa/behat.yml
```

#### Run a specific test

The composer behat command can now also pipe the arguments to the behat call. So now, for example you can do:

```
$ composer behat tests/features/self-asserted.feature
$ composer behat tests/features/sso.feature:34
$ composer behat -- -vv
$ composer behat -- --stop-on-failure
```

### CI Behat functional tests

The CI on github runs the Behat functional tests. This reuses configuration from OpenConext-devconf, but uses its own compose.yml (`ci/docker/compose.yml`). it also has its own parameters.yaml (`ci/config/parameters.yaml`).

Before you start the CI version, make sure you have: 
1. stopped any containers you started from OpenConext-devconf (`./stop-dev-env.sh`)
2. parameter changes have been applied to `ci/config/parameters.yaml`.


To run the tests like the CI does locally:

From the Stepup-Gateway doc-root:
```bash
$ cd ci/docker
$ ./init.sh
# If this fails, possibly you'll have to make the app/files folder writable for your docker user
$ docker-compose exec -T php-fpm.stepup.example.com bash -c 'composer behat'
```

Stop the CI docker containers before starting devconf again:
```
$ docker compose down
```

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
- [Functional testing](./docs/FUNCTIONAL_TESTING.md) (HTTP-level PHPUnit tests, capturing fixtures from devconf)

Documentation for the Stepup system can be found in the
- [Stepup-Deploy](https://github.com/OpenConext/Stepup-Deploy) repository;
- and in the [wiki](https://github.com/OpenConext/Stepup-Deploy/wiki) of the Stepup-Deploy repository.
