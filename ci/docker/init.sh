#!/usr/bin/env bash
cp ../../devconf/stepup/gateway/surfnet_yubikey.yaml.dist ../../devconf/stepup/gateway/surfnet_yubikey.yaml
echo "pulling the images"
docker compose pull gateway selenium haproxy ssp mariadb azuremfa chrome
echo "starting the images"
docker compose up gateway selenium haproxy ssp mariadb azuremfa chrome -d
echo "intialising the environment"
docker compose exec -T gateway bash -c '
  cp /var/www/html/devconf/stepup/gateway/surfnet_yubikey.yaml.dist /var/www/html/devconf/stepup/gateway/surfnet_yubikey.yaml && \
  cp /var/www/html/config/openconext/parameters.yaml.dist /var/www/html/config/openconext/parameters.yaml && \
  cp /var/www/html/config/openconext/samlstepupproviders_parameters.yaml.dist /var/www/html/config/openconext/samlstepupproviders_parameters.yaml && \
  cp /var/www/html/config/openconext/global_view_parameters.yaml.dist /var/www/html/config/openconext/global_view_parameters.yaml && \
  composer install --prefer-dist -n -o --no-scripts && \
  composer frontend-install && \global_view_parameters.yaml.dist
  ./bin/console assets:install --env=smoketest --verbose && \
  ./bin/console cache:clear --env=smoketest && \
  mkdir /var/www/html/var/cache/smoketest/sessions && \
  chown -R www-data /var/www/html/var/
'
