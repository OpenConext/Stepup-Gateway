#!/usr/bin/env bash

docker compose up gateway selenium haproxy ssp mariadb -d

docker-compose exec -T gateway bash -c '
  composer install --prefer-dist -n -o --no-scripts && \
  composer frontend-install && \
  ./bin/console assets:install --env=smoketest --verbose && \
  ./bin/console cache:clear --env=smoketest && \
  chown -R www-data:www-data /var/www/html/var/
'
