#!/usr/bin/env bash
uid=$(id -u)
gid=$(id -g)

printf "UID=${uid}\nGID=${gid}\nCOMPOSE_PROJECT_NAME=gateway" > .env

docker-compose up -d

docker-compose exec -T php-fpm.stepup.example.com bash -c '
  cp ./ci/config/*.yml ./app/config/
  cp ./ci/certificates/* ./app/
  composer install --prefer-dist -n -o --no-scripts && \
  composer frontend-install && \
  composer distribution-bundle-scripts && \
  ./app/console assets:install --env=test --verbose
'
