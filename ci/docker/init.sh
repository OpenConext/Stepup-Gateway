#!/usr/bin/env bash
cp ../../devconf/stepup/gateway/surfnet_yubikey.yaml.dist ../../devconf/stepup/gateway/surfnet_yubikey.yaml
docker compose pull gateway selenium haproxy ssp mariadb
docker compose up gateway selenium haproxy ssp mariadb -d

docker compose exec -T gateway bash -c '
  cp /var/www/html/devconf/stepup/gateway/surfnet_yubikey.yaml.dist /var/www/html/devconf/stepup/gateway/surfnet_yubikey.yaml && \
  composer install --prefer-dist -n -o --no-scripts && \
  composer frontend-install && \
  ./bin/console assets:install --env=smoketest --verbose && \
  ./bin/console cache:clear --env=smoketest && \
  chown -R www-data:www-data /var/www/html/var/
'
