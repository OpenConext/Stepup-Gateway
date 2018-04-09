Step-up Gateway
===============

[![Build Status](https://travis-ci.org/OpenConext/Stepup-Gateway.svg)](https://travis-ci.org/OpenConext/Stepup-Gateway) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/OpenConext/Stepup-Gateway/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/OpenConext/Stepup-Gateway/?branch=develop) [![SensioLabsInsight](https://insight.sensiolabs.com/projects/6204fffb-6333-4f78-9620-5a5bb09dfab2/mini.png)](https://insight.sensiolabs.com/projects/6204fffb-6333-4f78-9620-5a5bb09dfab2)

This component is part of "Step-up Authentication as-a Service". See [Stepup-Deploy](https://github.com/OpenConext/Stepup-Deploy) for an overview and installation instructions for a complete Stepup system, including this component. The requirements and installation instructions below cover this component only.

## Requirements

 * PHP 5.6 (Note that we test on 7.0 but do not run or support it officially)
 * [Composer](https://getcomposer.org/)
 * A web server (Apache, Nginx)
 * MariaDB 5.5+ (MySQL should work as well)
 * syslog (or change the logging configuration in /app/config/logging.yml)

## Installation

Clone the repository or download the archive to a directory. Install the dependencies by running `composer install` and fill out the database credentials et cetera.

Make sure to run database migrations for u2f using `app/console u2f:migrations:migrate`.

Run `app/console mopa:bootstrap:symlink:less` to configure Bootstrap symlinks.

The Gateway is configured to only accept connections over SSL. Disable this under `nelmio_security` in `config.yml` or run the web server using a (self-signed) certificate.

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
