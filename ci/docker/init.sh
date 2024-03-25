#!/usr/bin/env bash
cp ../../devconf/stepup/gateway/surfnet_yubikey.yaml.dist ../../devconf/stepup/gateway/surfnet_yubikey.yaml
docker compose pull gateway selenium haproxy ssp mariadb
docker compose up gateway selenium haproxy ssp mariadb -d

docker compose exec -T gateway bash -c '
  cp /var/www/html/devconf/stepup/gateway/surfnet_yubikey.yaml.dist /var/www/html/devconf/stepup/gateway/surfnet_yubikey.yaml && \
  cp /var/www/html/config/legacy/parameters.yaml.dist /var/www/html/config/legacy/parameters.yaml && \
  cp /var/www/html/config/legacy/samlstepupproviders_parameters.yaml.dist /var/www/html/config/legacy/samlstepupproviders_parameters.yaml && \
  cp /var/www/html/config/legacy/global_view_parameters.yaml.dist /var/www/html/config/legacy/global_view_parameters.yaml && \
  composer install --prefer-dist -n -o --no-scripts && \
  composer frontend-install && \global_view_parameters.yaml.dist
  ./bin/console assets:install --env=smoketest --verbose && \
  ./bin/console cache:clear --env=smoketest && \
  chown -R www-data:www-data /var/www/html/var/
'
