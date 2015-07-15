Step-up Gateway
===============

[![Build Status](https://travis-ci.org/SURFnet/Stepup-Gateway.svg)](https://travis-ci.org/SURFnet/Stepup-Gateway) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/SURFnet/Stepup-Gateway/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/SURFnet/Stepup-Gateway/?branch=develop) [![SensioLabs Insight](https://insight.sensiolabs.com/projects/6204fffb-6333-4f78-9620-5a5bb09dfab2/mini.png)](https://insight.sensiolabs.com/projects/6204fffb-6333-4f78-9620-5a5bb09dfab2)

## Requirements

 * PHP 5.4+
 * [Composer](https://getcomposer.org/)
 * A web server (Apache, Nginx)
 * MariaDB 5.5+ (MySQL should work as well)
 * syslog (or change the logging configuration in /app/config/logging.yml)

## Installation

Clone the repository or download the archive to a directory. Install the dependencies by running `composer install` and fill out the database credentials et cetera.

The Gateway is configured to only accept connections over SSL. Disable this under `nelmio_security` in `config.yml` or run the web server using a (self-signed) certificate.

## Stepup
This component is part of the stepup software an requires other components to function. Please refer to https://github.com/SURFnet/Stepup-Deploy for information for setting up the stepup software.
